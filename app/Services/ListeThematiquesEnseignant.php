<?php

namespace App\Services;

use App\Models\Thematique;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Liste les thématiques visibles par un enseignant.
 *
 */
readonly class ListeThematiquesEnseignant
{
    /**
     * Retourne les thématiques visibles par l'enseignant, triées par nom, avec leur auteur.
     *
     * @return Collection<int, Thematique>
     */
    public function pour(User $user): Collection
    {
        return Thematique::visiblesPar($user)
            ->with('enseignant:id,prenom,nom')
            ->orderBy('nom')
            ->get();
    }
}
