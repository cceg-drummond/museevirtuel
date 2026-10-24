<?php

namespace App\Http\Controllers\Cours;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderRequest;
use App\Models\Cours;
use App\Models\CoursLienEntrevue;
use App\Services\OrdreElements;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CoursLienEntrevueController extends Controller
{
    /**
     * Ajoute un lien d'entrevue au cours.
     *
     * Accessible aux enseignants et admins (authorize sur le cours).
     */
    public function store(Request $request, Cours $cours, OrdreElements $ordres): RedirectResponse
    {
        $this->authorize('update', $cours);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:2048'],
        ]);

        $ordre = $ordres->prochain($cours->liensEntrevue());

        $cours->liensEntrevue()->create([
            'label' => $data['label'],
            'url' => $data['url'],
            'ordre' => $ordre,
        ]);

        return back()->with('success', __('liens_entrevue.added'));
    }

    /**
     * Met à jour un lien d'entrevue existant.
     */
    public function update(
        Request $request,
        Cours $cours,
        CoursLienEntrevue $lien,
    ): RedirectResponse {
        $this->authorize('update', $cours);
        abort_if($lien->cours_id !== $cours->id, 404);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:2048'],
        ]);

        $lien->update($data);

        return back()->with('success', __('liens_entrevue.updated'));
    }

    /**
     * Réordonne les liens d'entrevue du cours.
     *
     * Reçoit un tableau d'IDs dans l'ordre désiré.
     */
    public function reorder(ReorderRequest $request, Cours $cours, OrdreElements $ordres): RedirectResponse
    {
        $this->authorize('update', $cours);

        $ordres->reordonner($cours->liensEntrevue(), $request->ids());

        return back();
    }

    /**
     * Supprime un lien d'entrevue et renumérote les restants.
     */
    public function destroy(Cours $cours, CoursLienEntrevue $lien, OrdreElements $ordres): RedirectResponse
    {
        $this->authorize('update', $cours);
        abort_if($lien->cours_id !== $cours->id, 404);

        $ordres->supprimer($lien, $cours->liensEntrevue());

        return back()->with('success', __('liens_entrevue.deleted'));
    }
}
