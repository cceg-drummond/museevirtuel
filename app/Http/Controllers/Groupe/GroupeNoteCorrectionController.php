<?php

namespace App\Http\Controllers\Groupe;

use App\Concerns\ValidatesGroupeChain;
use App\Http\Controllers\Controller;
use App\Models\Classe;
use App\Models\Cours;
use App\Models\Groupe;
use App\Models\GroupeNote;
use App\Models\GroupeNoteCorrection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Contrôleur de gestion des corrections inline de l'enseignant sur les notes d'un groupe.
 */
class GroupeNoteCorrectionController extends Controller
{
    use ValidatesGroupeChain;

    /**
     * Crée ou met à jour une correction inline sur une note (enseignant uniquement).
     *
     * @throws HttpException si l'utilisateur n'est pas l'enseignant du cours
     */
    public function upsert(Request $request, Cours $cours, Classe $classe, Groupe $groupe, GroupeNote $note): RedirectResponse
    {
        $this->verifierChaine($cours, $classe, $groupe);
        $this->autoriserCorrectionNote($cours, $groupe, $note);

        $validated = $request->validate([
            'commentaire_id' => ['required', 'string', 'max:36'],
            'contenu' => ['required', 'string', 'max:1000'],
            'note_html' => ['required', 'string'],
        ]);

        $note->update(['contenu' => $validated['note_html']]);

        GroupeNoteCorrection::updateOrCreate(
            ['note_id' => $note->id, 'commentaire_id' => $validated['commentaire_id']],
            ['contenu' => $validated['contenu'], 'user_id' => auth()->id()]
        );

        return back()->with('success', 'Correction enregistrée.');
    }

    /**
     * Supprime une correction inline et met à jour le HTML de la note.
     *
     * @throws HttpException
     */
    public function destroy(Request $request, Cours $cours, Classe $classe, Groupe $groupe,
        GroupeNote $note, GroupeNoteCorrection $correction): RedirectResponse
    {
        $this->verifierChaine($cours, $classe, $groupe);
        $this->autoriserCorrectionNote($cours, $groupe, $note);

        abort_if($correction->note_id !== $note->id, 404);

        $validated = $request->validate([
            'note_html' => ['required', 'string'],
        ]);

        $note->update(['contenu' => $validated['note_html']]);
        $correction->delete();

        return back()->with('success', 'Correction supprimée.');
    }

    /**
     * Vérifie que l'utilisateur courant peut corriger les notes de ce groupe.
     *
     * @throws HttpException
     */
    private function autoriserCorrectionNote(Cours $cours, Groupe $groupe, GroupeNote $note): void
    {
        abort_unless(
            $cours->enseignant_id === auth()->id() || auth()->user()->role === 'admin',
            403
        );

        abort_if($note->groupe_id !== $groupe->id, 404);
    }
}
