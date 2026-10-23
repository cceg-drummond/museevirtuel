<?php

namespace App\Http\Controllers\Groupe;

use App\Concerns\ValidatesGroupeChain;
use App\Http\Controllers\Controller;
use App\Models\Classe;
use App\Models\Cours;
use App\Models\Groupe;
use App\Models\Thematique;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Thématiques d'un groupe.
 */
class GroupeThematiqueController extends Controller
{
    use ValidatesGroupeChain;

    /**
     * Remplace complètement les thématiques du groupe (sync).
     *
     * @throws AuthorizationException
     */
    public function update(Request $request, Cours $cours, Classe $classe, Groupe $groupe): RedirectResponse
    {
        $this->verifierChaine($cours, $classe, $groupe);
        $groupe->load('classe.cours');
        $this->authorize('manageThematiques', $groupe);

        $validated = $request->validate([
            'thematiques' => ['array', 'max:3'],
            'thematiques.*' => ['integer', 'exists:thematiques,id'],
        ]);

        $thematiquesValides = Thematique::visiblesPar($cours->enseignant)
            ->whereIn('id', $validated['thematiques'] ?? [])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        if (count($thematiquesValides) < 1) {
            return back()->withErrors(['thematiques' => 'Le groupe doit avoir au moins 1 thématique valide.']);
        }

        DB::transaction(function () use ($thematiquesValides, $groupe) {
            $groupe->thematiques()->sync($thematiquesValides);
        });

        return back()->with('success', __('groupe.thematiques_updated'));
    }
}
