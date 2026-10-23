<?php

namespace App\Http\Controllers\Groupe;

use App\Concerns\ValidatesGroupeChain;
use App\Http\Controllers\Controller;
use App\Models\Classe;
use App\Models\Cours;
use App\Models\Groupe;
use App\Models\GroupeNote;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Contrôleur de gestion des notes collaboratives d'un groupe.
 */
class GroupeNoteController extends Controller
{
    use ValidatesGroupeChain;

    /**
     * Ajoute une note collaborative au groupe.
     *
     * @throws AuthorizationException
     */
    public function store(Request $request, Cours $cours, Classe $classe, Groupe $groupe): RedirectResponse
    {
        $this->verifierChaine($cours, $classe, $groupe);
        $groupe->load('classe.cours');
        $this->authorize('addNote', $groupe);

        $validated = $request->validate([
            'contenu' => ['required', 'string', 'max:2000'],
        ]);

        GroupeNote::create([
            'groupe_id' => $groupe->id,
            'user_id' => auth()->id(),
            'contenu' => $validated['contenu'],
        ]);

        return back()->with('success', __('groupe.note_created'));
    }

    /**
     * Supprime une note du groupe.
     *
     * @throws HttpException si l'utilisateur n'est pas l'auteur
     */
    public function destroy(Cours $cours, Classe $classe, Groupe $groupe, GroupeNote $note): RedirectResponse
    {
        $this->verifierChaine($cours, $classe, $groupe, $note);
        abort_if($note->user_id !== auth()->id(), 403);

        $note->delete();

        return back()->with('success', __('groupe.note_deleted'));
    }
}
