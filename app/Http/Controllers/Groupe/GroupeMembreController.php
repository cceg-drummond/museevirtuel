<?php

namespace App\Http\Controllers\Groupe;

use App\Concerns\ValidatesGroupeChain;
use App\Http\Controllers\Controller;
use App\Http\Requests\Groupe\UpdateGroupeMembreRequest;
use App\Models\Classe;
use App\Models\Cours;
use App\Models\Groupe;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Contrôleur de gestion des membres d'un groupe.
 */
class GroupeMembreController extends Controller
{
    use ValidatesGroupeChain;

    /**
     * Ajoute ou retire des membres du groupe (créateur uniquement).
     *
     * Données validées par UpdateGroupeMembreRequest.
     *
     * @throws AuthorizationException
     */
    public function update(UpdateGroupeMembreRequest $request, Cours $cours, Classe $classe, Groupe $groupe): RedirectResponse
    {
        $this->verifierChaine($cours, $classe, $groupe);
        $groupe->load('classe.cours');
        $this->authorize('manageMembers', $groupe);

        $validated = $request->validated();

        $user = auth()->user();

        // Pré-calculer les changements avant transaction pour pouvoir valider la taille
        $aAjouter = [];
        if (! empty($validated['ajouter'])) {
            $aAjouter = $classe->etudiants()
                ->whereIn('users.id', $validated['ajouter'])
                ->pluck('users.id')
                ->map(fn ($id) => (int) $id)
                ->toArray();

            // Exclure les étudiants déjà dans un autre groupe de la même classe
            $aAjouter = array_values(array_diff($aAjouter, $classe->etudiantsDejaGroupes($groupe->id)));
        }

        $aRetirer = array_values(array_diff(
            array_map('intval', $validated['retirer'] ?? []),
            [(int) $user->id]
        ));

        $membresCourants = $groupe->membres()->pluck('users.id')->map(fn ($id) => (int) $id)->toArray();
        $nouveauxMembres = array_values(array_unique(array_diff(
            array_merge($membresCourants, $aAjouter),
            $aRetirer,
        )));
        $totalMembres = count($nouveauxMembres);

        if ($totalMembres < 2) {
            return back()->withErrors(['membres' => 'Le groupe doit avoir au moins 2 membres.']);
        }

        // Vérifier les contraintes de taille d'équipe du cours
        if ($cours->taille_equipe_min !== null || $cours->taille_equipe_max !== null) {
            if ($cours->taille_equipe_min !== null && $totalMembres < $cours->taille_equipe_min) {
                return back()->withErrors(['membres' => "Le groupe doit avoir au moins {$cours->taille_equipe_min} membre(s)."]);
            }

            if ($cours->taille_equipe_max !== null && $totalMembres > $cours->taille_equipe_max) {
                return back()->withErrors(['membres' => "Le groupe ne peut pas avoir plus de {$cours->taille_equipe_max} membre(s)."]);
            }
        }

        DB::transaction(function () use ($aAjouter, $aRetirer, $groupe) {
            if (! empty($aAjouter)) {
                $groupe->membres()->syncWithoutDetaching($aAjouter);
            }

            if (! empty($aRetirer)) {
                $groupe->membres()->detach($aRetirer);
            }
        });

        return back()->with('success', __('groupe.members_updated'));
    }
}
