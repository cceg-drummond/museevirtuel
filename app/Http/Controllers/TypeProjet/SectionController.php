<?php

namespace App\Http\Controllers\TypeProjet;

use App\Enums\TypeSection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commun\ReorderRequest;
use App\Http\Requests\TypeProjet\SaveTypeProjetSectionRequest;
use App\Models\Cours;
use App\Models\TypeProjet;
use App\Models\TypeProjetSection;
use App\Services\OrdreElements;
use Illuminate\Http\RedirectResponse;

class SectionController extends Controller
{
    /**
     * Ajoute une section au type de projet.
     *
     * Les données sont validées par SaveTypeProjetSectionRequest.
     */
    public function store(SaveTypeProjetSectionRequest $request, Cours $cours, TypeProjet $typeProjet, OrdreElements $ordres): RedirectResponse
    {
        $this->authorize('update', $cours);
        abort_if($typeProjet->cours_id !== $cours->id, 404);

        $data = $request->validated();

        $ordre = $ordres->prochain($typeProjet->sections());

        $typeProjet->sections()->create([
            'label' => $data['label'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? TypeSection::Texte->value,
            'ordre' => $ordre,
        ]);

        return back()->with('success', 'Section ajoutée.');
    }

    /**
     * Met à jour le label et la description d'une section.
     *
     * Les données sont validées par SaveTypeProjetSectionRequest.
     */
    public function update(SaveTypeProjetSectionRequest $request, Cours $cours, TypeProjet $typeProjet, TypeProjetSection $section): RedirectResponse
    {
        $this->authorize('update', $cours);
        abort_if($typeProjet->cours_id !== $cours->id, 404);
        abort_if($section->type_projet_id !== $typeProjet->id, 404);

        $data = $request->validated();

        $section->update($data);

        return back()->with('success', 'Section mise à jour.');
    }

    /**
     * Réordonne les sections d'un type de projet.
     *
     * Reçoit un tableau d'IDs dans l'ordre désiré.
     */
    public function reorder(ReorderRequest $request, Cours $cours, TypeProjet $typeProjet, OrdreElements $ordres): RedirectResponse
    {
        $this->authorize('update', $cours);
        abort_if($typeProjet->cours_id !== $cours->id, 404);

        $ordres->reordonner($typeProjet->sections(), $request->ids());

        return back();
    }

    /**
     * Supprime une section du type de projet.
     */
    public function destroy(Cours $cours, TypeProjet $typeProjet, TypeProjetSection $section, OrdreElements $ordres): RedirectResponse
    {
        $this->authorize('update', $cours);
        abort_if($typeProjet->cours_id !== $cours->id, 404);
        abort_if($section->type_projet_id !== $typeProjet->id, 404);

        $ordres->supprimer($section, $typeProjet->sections());

        return back()->with('success', 'Section supprimée.');
    }
}
