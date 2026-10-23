<?php

namespace App\Http\Controllers\Groupe;

use App\Concerns\ValidatesGroupeChain;
use App\Http\Controllers\Controller;
use App\Jobs\TranscrireVideo;
use App\Models\Classe;
use App\Models\Cours;
use App\Models\Groupe;
use App\Models\GroupeVideo;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GroupeVideoController extends Controller
{
    use ValidatesGroupeChain;

    /**
     * Affiche la liste des vidéos du groupe.
     *
     * @throws AuthorizationException
     */
    public function index(Cours $cours, Classe $classe, Groupe $groupe): Response
    {
        $this->verifierChaine($cours, $classe, $groupe);
        $groupe->load('classe.cours');
        $this->authorize('view', $groupe);

        $user = auth()->user();
        $estEnseignant = $user->isEnseignant()
            && $cours->enseignant_id === $user->id;

        // Même logique de visibilité que GroupeController::show() :
        // publié pour tous, brouillons/archivés pour enseignant/admin,
        // ses propres brouillons pour l'étudiant.
        $videos = $groupe->videos()
            ->where(function ($q) use ($user, $estEnseignant) {
                $q->where('statut', 'publié');

                if ($estEnseignant || $user->isAdmin()) {
                    $q->orWhereIn('statut', ['brouillon', 'archivé']);
                } else {
                    $q->orWhere(fn ($q2) => $q2->where('statut', 'brouillon')->where('user_id', $user->id));
                }
            })
            ->with('auteur:id,prenom,nom')
            ->get();

        return Inertia::render('Groupe/Videos/Index', [
            'cours' => $cours,
            'classe' => $classe,
            'groupe' => $groupe,
            'videos' => $videos,
        ]);
    }

    /**
     * Uploade une vidéo et l'associe au groupe en statut brouillon.
     *
     * Limite : 500 Mo. Formats : mp4, webm, mov, avi, mkv.
     *
     * @throws AuthorizationException
     */
    public function store(Request $request, Cours $cours, Classe $classe, Groupe $groupe): RedirectResponse
    {
        $this->verifierChaine($cours, $classe, $groupe);
        $groupe->load('classe.cours');
        $this->authorize('create', [GroupeVideo::class, $groupe]);

        $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'fichier' => [
                'required',
                'file',
                'max:2097152', // 2 Go en kilo-octets
                'mimes:mp4,webm,mov,avi,mkv',
            ],
        ]);

        // Stockage privé (hors webroot) : l'accès passe par l'endpoint stream() authentifié.
        $file = $request->file('fichier');
        $directory = "medias/groupes/{$groupe->id}/videos";
        $filename = Str::uuid().'.'.strtolower($file->getClientOriginalExtension());

        Storage::disk('local')->putFileAs($directory, $file, $filename);

        $video = $groupe->videos()->create([
            'user_id' => auth()->id(),
            'titre' => $request->input('titre'),
            'description' => $request->input('description'),
            'nom_original' => $file->getClientOriginalName(),
            'file_path' => "{$directory}/{$filename}",
            'taille' => $file->getSize(),
            'statut' => 'brouillon',
            'transcription_statut' => GroupeVideo::TRANSCRIPTION_EN_ATTENTE,
        ]);

        // Lance la transcription dès l'upload — pas besoin d'attendre
        // un traitement FFmpeg puisque la vidéo n'est pas encore éditée.
        TranscrireVideo::dispatch($video);

        return back()->with('success', __('video.uploaded'));
    }

    /**
     * Affiche une vidéo et son éditeur de timeline.
     *
     * @throws AuthorizationException
     */
    public function show(Cours $cours, Classe $classe, Groupe $groupe, GroupeVideo $video): Response
    {
        $this->verifierChaine($cours, $classe, $groupe, $video);
        $video->load('auteur:id,prenom,nom', 'groupe.classe.cours');
        $this->authorize('view', $video);

        $user = auth()->user();
        $estEnseignant = $user->isEnseignant()
            && $groupe->classe->cours->enseignant_id === $user->id;

        // Seules les vidéos visibles par l'utilisateur courant sont proposées au jumelage.
        // Un étudiant ne doit pas voir les brouillons de ses coéquipiers ici.
        $autresVideos = GroupeVideo::where('groupe_id', $groupe->id)
            ->where('id', '!=', $video->id)
            ->where(function ($q) use ($user, $estEnseignant) {
                $q->where('statut', 'publié');

                if ($estEnseignant || $user->isAdmin()) {
                    $q->orWhereIn('statut', ['brouillon', 'archivé']);
                } else {
                    $q->orWhere(fn ($q2) => $q2->where('statut', 'brouillon')->where('user_id', $user->id));
                }
            })
            ->where(function ($q) {
                $q->whereNull('traitement_statut')
                    ->orWhere('traitement_statut', GroupeVideo::TRAITEMENT_TERMINE);
            })
            ->orderByDesc('created_at')
            ->get(['id', 'titre', 'duree', 'thumbnail_path']);

        $video->load('chapitres');

        return Inertia::render('Groupe/Videos/Show', [
            'cours' => $cours,
            'classe' => $classe,
            'groupe' => $groupe,
            'video' => $video,
            'autresVideos' => $autresVideos,
            'peutTranscrire' => auth()->user()->can('transcrire', $video),
            'peutModifierTranscription' => auth()->user()->can('modifierTranscription', $video),
            'peutGererChapitres' => auth()->user()->can('gererChapitres', $video),
        ]);
    }

    /**
     * Met à jour le titre et la description d'une vidéo.
     *
     * @throws AuthorizationException
     */
    public function update(Request $request, Cours $cours, Classe $classe, Groupe $groupe, GroupeVideo $video): RedirectResponse
    {
        $this->verifierChaine($cours, $classe, $groupe, $video);
        $video->load('groupe.classe.cours');
        $this->authorize('update', $video);

        $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $video->update($request->only('titre', 'description'));

        return back()->with('success', __('video.updated'));
    }

    /**
     * Supprime la vidéo et son fichier physique.
     *
     * @throws AuthorizationException
     */
    public function destroy(Cours $cours, Classe $classe, Groupe $groupe, GroupeVideo $video): RedirectResponse
    {
        $this->verifierChaine($cours, $classe, $groupe, $video);
        $video->load('groupe.classe.cours');
        $this->authorize('delete', $video);

        $video->deleteWithFileAndThumbnail();

        return back()->with('success', __('video.deleted'));
    }

    /**
     * Retourne le statut de traitement actuel (endpoint de polling).
     *
     * Inclut aussi transcription_statut et transcription pour éviter
     * un second endpoint dédié.
     *
     * @throws AuthorizationException
     */
    public function statut(Cours $cours, Classe $classe, Groupe $groupe, GroupeVideo $video): JsonResponse
    {
        $this->verifierChaine($cours, $classe, $groupe, $video);
        $video->load('groupe.classe.cours');
        $this->authorize('view', $video);

        return response()->json([
            'traitement_statut' => $video->traitement_statut,
            'duree' => $video->duree,
            'thumbnail_url' => $video->thumbnail_url,
            'transcription_statut' => $video->transcription_statut,
            'transcription' => $video->transcription,
            'transcription_segments' => $video->transcription_segments,
        ]);
    }
}
