<?php

namespace App\Http\Controllers\Cours;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commun\ReorderRequest;
use App\Http\Requests\Cours\SaveCoursReferenceRequest;
use App\Models\Cours;
use App\Models\CoursReference;
use App\Services\OrdreElements;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CoursReferenceController extends Controller
{
    /**
     * Ajoute une référence bibliographique à un cours.
     *
     * Réservé à l'enseignant du cours et aux admins.
     * Les données sont validées par SaveCoursReferenceRequest.
     */
    public function store(SaveCoursReferenceRequest $request, Cours $cours, OrdreElements $ordres): RedirectResponse
    {
        Gate::authorize('update', $cours);

        $validated = $request->validated();

        $ordre = $ordres->prochain($cours->references());

        $cours->references()->create([
            'nom' => $validated['nom'],
            'url' => $validated['url'] ?? null,
            'ordre' => $ordre,
        ]);

        return back()->with('success', 'Référence ajoutée.');
    }

    /**
     * Met à jour une référence bibliographique.
     *
     * Réservé à l'enseignant du cours et aux admins.
     * Les données sont validées par SaveCoursReferenceRequest.
     */
    public function update(SaveCoursReferenceRequest $request, Cours $cours, CoursReference $reference): RedirectResponse
    {
        Gate::authorize('update', $cours);
        abort_if($reference->cours_id !== $cours->id, 404);

        $validated = $request->validated();

        $reference->update($validated);

        return back()->with('success', 'Référence mise à jour.');
    }

    /**
     * Supprime une référence bibliographique et renumérotise les suivantes.
     *
     * Réservé à l'enseignant du cours et aux admins.
     */
    public function destroy(Cours $cours, CoursReference $reference, OrdreElements $ordres): RedirectResponse
    {
        Gate::authorize('update', $cours);
        abort_if($reference->cours_id !== $cours->id, 404);

        $ordres->supprimer($reference, $cours->references());

        return back()->with('success', 'Référence supprimée.');
    }

    /**
     * Réordonne les références bibliographiques d'un cours.
     *
     * Reçoit un tableau d'IDs dans l'ordre désiré.
     */
    public function reorder(ReorderRequest $request, Cours $cours, OrdreElements $ordres): RedirectResponse
    {
        Gate::authorize('update', $cours);

        $ordres->reordonner($cours->references(), $request->ids());

        return back();
    }
}
