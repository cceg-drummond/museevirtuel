<?php

namespace App\Http\Controllers;

use App\Models\EtudiantReference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EtudiantReferenceController extends Controller
{
    /**
     * Ajoute manuellement une référence bibliographique à la liste personnelle de l'étudiant.
     *
     * L'ordre est calculé automatiquement (max existant + 1).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'titre' => ['required', 'string', 'max:500'],
            'url' => ['nullable', 'url', 'max:500'],
        ]);

        $user = auth()->user();

        $ordre = $user->etudiantReferences()->max('ordre') + 1;

        $user->etudiantReferences()->create([
            'titre' => $validated['titre'],
            'url' => $validated['url'] ?? null,
            'ordre' => $ordre,
        ]);

        return back()->with('success', 'Référence ajoutée.');
    }

    /**
     * Supprime une référence personnelle de l'étudiant authentifié.
     *
     * Vérifie que la référence appartient bien à l'étudiant avant de supprimer.
     */
    public function destroy(EtudiantReference $reference): RedirectResponse
    {
        abort_if($reference->user_id !== auth()->id(), 403);

        $reference->delete();

        return back()->with('success', 'Référence supprimée.');
    }
}
