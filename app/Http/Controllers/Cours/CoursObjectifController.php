<?php

namespace App\Http\Controllers\Cours;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commun\ReorderRequest;
use App\Http\Requests\Cours\SaveCoursObjectifRequest;
use App\Models\Cours;
use App\Models\CoursObjectif;
use App\Services\OrdreElements;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CoursObjectifController extends Controller
{
    /**
     * Ajoute un objectif pédagogique à un cours.
     *
     * Réservé à l'enseignant du cours et aux admins.
     * Les données sont validées par SaveCoursObjectifRequest.
     */
    public function store(SaveCoursObjectifRequest $request, Cours $cours, OrdreElements $ordres): RedirectResponse
    {
        Gate::authorize('update', $cours);

        $validated = $request->validated();

        $ordre = $ordres->prochain($cours->objectifs());

        $cours->objectifs()->create([
            'contenu' => $validated['contenu'],
            'ordre' => $ordre,
        ]);

        return back()->with('success', 'Objectif ajouté.');
    }

    /**
     * Met à jour le contenu d'un objectif pédagogique.
     *
     * Réservé à l'enseignant du cours et aux admins.
     * Les données sont validées par SaveCoursObjectifRequest.
     */
    public function update(SaveCoursObjectifRequest $request, Cours $cours, CoursObjectif $objectif): RedirectResponse
    {
        Gate::authorize('update', $cours);
        abort_if($objectif->cours_id !== $cours->id, 404);

        $validated = $request->validated();

        $objectif->update($validated);

        return back()->with('success', 'Objectif mis à jour.');
    }

    /**
     * Supprime un objectif pédagogique et renumérotise les suivants.
     *
     * Réservé à l'enseignant du cours et aux admins.
     */
    public function destroy(Cours $cours, CoursObjectif $objectif, OrdreElements $ordres): RedirectResponse
    {
        Gate::authorize('update', $cours);
        abort_if($objectif->cours_id !== $cours->id, 404);

        $ordres->supprimer($objectif, $cours->objectifs());

        return back()->with('success', 'Objectif supprimé.');
    }

    /**
     * Réordonne les objectifs d'un cours.
     *
     * Reçoit un tableau d'IDs dans l'ordre désiré.
     */
    public function reorder(ReorderRequest $request, Cours $cours, OrdreElements $ordres): RedirectResponse
    {
        Gate::authorize('update', $cours);

        $ordres->reordonner($cours->objectifs(), $request->ids());

        return back();
    }
}
