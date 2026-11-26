<?php

namespace App\Services;

use App\Models\Classe;
use App\Models\Cours;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Liste les cours d'un enseignant avec leurs compteurs (classes, groupes, étudiants, types de projet).
 *
 * Partagée par le tableau de bord et la page Cours de l'enseignant.
 */
readonly class ListeCoursEnseignant
{
    /**
     * Retourne les cours de l'enseignant triés par nom, avec leurs compteurs.
     *
     * @return Collection<int, Cours>
     */
    public function pour(User $user): Collection
    {
        $cours = $user->cours()
            ->withCount(['classes', 'typesProjets'])
            ->with(['classes' => fn ($q) => $q->withCount('groupes')->with('etudiants:id')])
            ->orderBy('nom_cours')
            ->get();

        $cours->each(function (Cours $cours): void {
            // Un étudiant inscrit dans plusieurs classes du même cours ne compte qu'une fois
            $etudiantsIds = $cours->classes
                ->flatMap(fn (Classe $classe) => $classe->etudiants->pluck('id'))
                ->unique();

            $cours->setAttribute('etudiants_count', $etudiantsIds->count());
            $cours->setAttribute('groupes_count', $cours->classes->sum('groupes_count'));
            $cours->unsetRelation('classes');
        });

        return $cours;
    }
}
