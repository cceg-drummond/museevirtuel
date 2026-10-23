<?php

namespace App\Http\Controllers\Groupe;

use App\Concerns\ValidatesGroupeChain;
use App\Http\Controllers\Controller;
use App\Jobs\TranscrireVideo;
use App\Models\Classe;
use App\Models\Cours;
use App\Models\Groupe;
use App\Models\GroupeVideo;
use App\Services\ImportTranscriptionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Transcription d'une vidéo de groupe : génération Whisper, correction manuelle et import de fichier.
 */
class GroupeVideoTranscriptionController extends Controller
{
    use ValidatesGroupeChain;

    /**
     * Dispatche un Job de transcription Whisper pour la vidéo.
     *
     * Ne redispatche pas si une transcription est déjà en cours ou en attente,
     * pour éviter les doubles soumissions accidentelles.
     *
     * @throws AuthorizationException
     */
    public function store(Cours $cours, Classe $classe, Groupe $groupe, GroupeVideo $video): RedirectResponse
    {
        $this->verifierChaine($cours, $classe, $groupe, $video);
        $video->load('groupe.classe.cours');
        $this->authorize('transcrire', $video);

        // On ne redémarre pas une transcription déjà en cours.
        if ($video->isBeingTranscribed()) {
            return back()->with('info', __('video.transcription_already_running'));
        }

        // La re-génération écrase les corrections manuelles — réinitialiser le flag.
        $video->update([
            'transcription_statut' => GroupeVideo::TRANSCRIPTION_EN_ATTENTE,
            'transcription_modifiee' => false,
        ]);
        TranscrireVideo::dispatch($video);

        return back()->with('success', __('video.transcription_started'));
    }

    /**
     * Sauvegarde les segments de transcription édités manuellement.
     *
     * Reconstruit le texte plat à partir des segments modifiés et pose
     * le flag transcription_modifiee = true pour avertir avant une re-génération Whisper.
     *
     * @throws AuthorizationException
     */
    public function update(
        Request $request,
        Cours $cours,
        Classe $classe,
        Groupe $groupe,
        GroupeVideo $video,
    ): RedirectResponse {
        $this->verifierChaine($cours, $classe, $groupe, $video);
        $video->load('groupe.classe.cours');
        $this->authorize('modifierTranscription', $video);

        $validated = $request->validate([
            // max:500 évite le dépassement de la colonne TEXT (65 535 octets) en production MySQL.
            'segments' => ['required', 'array', 'min:1', 'max:500'],
            'segments.*.start' => ['required', 'numeric', 'min:0'],
            'segments.*.end' => ['required', 'numeric', 'gt:segments.*.start'],
            'segments.*.text' => ['required', 'string', 'max:2000'],
        ]);

        // Reconstruit le texte plat à partir des segments modifiés.
        $transcription = implode(' ', array_column($validated['segments'], 'text'));

        $video->update([
            'transcription' => $transcription,
            'transcription_segments' => $validated['segments'],
            'transcription_modifiee' => true,
        ]);

        return back()->with('success', 'Transcription mise à jour.');
    }

    /**
     * Importe une transcription depuis un fichier .txt, .srt ou .vtt.
     *
     * Délègue le parsing à ImportTranscriptionService, puis écrase
     * la transcription existante. Pose transcription_modifiee = true.
     *
     * @throws AuthorizationException
     */
    public function importer(
        Request $request,
        Cours $cours,
        Classe $classe,
        Groupe $groupe,
        GroupeVideo $video,
        ImportTranscriptionService $service,
    ): RedirectResponse {
        $this->verifierChaine($cours, $classe, $groupe, $video);
        $video->load('groupe.classe.cours');
        $this->authorize('modifierTranscription', $video);

        $request->validate([
            'fichier' => ['required', 'file', 'max:2048', 'mimes:txt,srt,vtt'],
        ]);

        $resultat = $service->importer($request->file('fichier'));

        $video->update([
            'transcription' => $resultat['transcription'],
            'transcription_segments' => $resultat['segments'],
            'transcription_statut' => GroupeVideo::TRANSCRIPTION_TERMINEE,
            'transcription_modifiee' => true,
        ]);

        return back()->with('success', 'Transcription importée avec succès.');
    }
}
