<?php

namespace App\Http\Controllers\Groupe;

use App\Concerns\ValidatesGroupeChain;
use App\Http\Controllers\Controller;
use App\Models\Classe;
use App\Models\Cours;
use App\Models\Groupe;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Controleur de gestion des assignations de témoin (personne âgée) d'un groupe.
 */
class GroupeTemoinController extends Controller
{
    use ValidatesGroupeChain;

    /**
     * Assigne ou désassigne un témoin (personne âgée) au groupe.
     *
     * @throws AuthorizationException
     */
    public function __invoke(Request $request, Cours $cours, Classe $classe, Groupe $groupe): RedirectResponse
    {
        $this->verifierChaine($cours, $classe, $groupe);
        $groupe->load('classe.cours');
        $this->authorize('assignerTemoin', $groupe);

        $validated = $request->validate([
            'personne_agee_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', 'personne_agee')->where('statut', 'actif'),
            ],
        ]);

        $groupe->update(['personne_agee_id' => $validated['personne_agee_id']]);

        return back()->with('success', 'Témoin mis à jour.');
    }
}
