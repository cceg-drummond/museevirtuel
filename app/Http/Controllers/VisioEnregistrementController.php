<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Cours;
use App\Models\Groupe;
use App\Models\VisioConference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Enregistrement vidéo d'une visioconférence (dépôt et diffusion).
 */
class VisioEnregistrementController extends Controller
{
    /**
     * Enregistre le fichier vidéo d'une rencontre terminée.
     *
     * Remplace l'ancien fichier s'il en existe déjà un.
     * Accessible uniquement à l'enseignant du cours.
     *
     * @throws HttpException si la visio n'appartient pas au cours
     */
    public function store(Request $request, Cours $cours, VisioConference $visio): RedirectResponse
    {
        $this->authorize('update', $cours);
        abort_if($visio->cours_id !== $cours->id, 404);

        $request->validate([
            'recording' => ['required', 'file', 'mimes:mp4,mov,webm,avi', 'max:1048576'],
        ]);

        // Supprimer l'ancien fichier avant de le remplacer
        if ($visio->recording_path) {
            Storage::delete($visio->recording_path);
        }

        $extension = $request->file('recording')->getClientOriginalExtension();
        $path = $request->file('recording')->storeAs(
            "visio-recordings/{$visio->id}",
            "recording.{$extension}"
        );

        $visio->update(['recording_path' => $path]);

        return back()->with('success', __('visio.recording_saved'));
    }

    /**
     * Diffuse le fichier d'enregistrement en streaming sécurisé.
     *
     * Supporte les range requests pour permettre la navigation dans la vidéo.
     * Accessible à l'enseignant du cours et aux membres de ses groupes.
     *
     * @throws HttpException si non autorisé ou fichier introuvable
     */
    public function show(Cours $cours, VisioConference $visio): BinaryFileResponse
    {
        abort_if($visio->cours_id !== $cours->id, 404);
        abort_if(! $visio->recording_path, 404);

        $user = auth()->user();

        // Autoriser l'enseignant, les admins, et les membres de n'importe quel groupe du cours
        /**
         * Il faudra retirer la portion $user->isEnseignant()
         * && en production.
         */
        $estAutorise = ($user->isEnseignant() && $cours->enseignant_id === $user->id)
            || $user->isAdmin()
            || Classe::where('cours_id', $cours->id)
                ->whereHas('groupes', fn ($q) => $q
                    ->whereHas('membres', fn ($q2) => $q2->where('users.id', $user->id))
                )
                ->exists();

        abort_unless($estAutorise, 403);

        $filePath = Storage::path($visio->recording_path);
        abort_if(! file_exists($filePath), 404);

        return response()->file($filePath);
    }
}
