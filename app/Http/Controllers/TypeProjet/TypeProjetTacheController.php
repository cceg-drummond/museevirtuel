<?php

namespace App\Http\Controllers\TypeProjet;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderRequest;
use App\Models\Cours;
use App\Models\TypeProjet;
use App\Models\TypeProjetTache;
use App\Services\OrdreElements;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TypeProjetTacheController extends Controller
{
    /**
     * Ajoute une tâche dans un TypeProjet de type 'tache'.
     *
     * Accessible aux enseignants et admins uniquement (authorize sur le cours).
     */
    public function store(
        Request $request,
        Cours $cours,
        TypeProjet $typeProjet,
        OrdreElements $ordres,
    ): RedirectResponse {
        $this->authorize('update', $cours);
        abort_if($typeProjet->cours_id !== $cours->id, 404);

        $data = $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $ordre = $ordres->prochain($typeProjet->taches());

        $typeProjet->taches()->create([
            'titre' => $data['titre'],
            'description' => $data['description'] ?? null,
            'ordre' => $ordre,
        ]);

        return back()->with('success', __('taches.added'));
    }

    /**
     * Met à jour le titre ou la description d'une tâche.
     */
    public function update(
        Request $request,
        Cours $cours,
        TypeProjet $typeProjet,
        TypeProjetTache $tache,
    ): RedirectResponse {
        $this->authorize('update', $cours);
        abort_if($typeProjet->cours_id !== $cours->id, 404);
        abort_if($tache->type_projet_id !== $typeProjet->id, 404);

        $data = $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $tache->update($data);

        return back()->with('success', __('taches.updated'));
    }

    /**
     * Réordonne les tâches d'un TypeProjet.
     *
     * Reçoit un tableau d'IDs dans l'ordre désiré.
     */
    public function reorder(
        ReorderRequest $request,
        Cours $cours,
        TypeProjet $typeProjet,
        OrdreElements $ordres,
    ): RedirectResponse {
        $this->authorize('update', $cours);
        abort_if($typeProjet->cours_id !== $cours->id, 404);

        $ordres->reordonner($typeProjet->taches(), $request->ids());

        return back();
    }

    /**
     * Supprime une tâche et renumérote les tâches restantes.
     */
    public function destroy(
        Cours $cours,
        TypeProjet $typeProjet,
        TypeProjetTache $tache,
        OrdreElements $ordres,
    ): RedirectResponse {
        $this->authorize('update', $cours);
        abort_if($typeProjet->cours_id !== $cours->id, 404);
        abort_if($tache->type_projet_id !== $typeProjet->id, 404);

        $ordres->supprimer($tache, $typeProjet->taches());

        return back()->with('success', __('taches.deleted'));
    }
}
