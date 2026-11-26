<?php

namespace App\Services;

use App\Models\Groupe;
use App\Models\ProjetRecherche;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Liste tous les projets de recherche des groupes d'un enseignant, pour la page Projets.
 */
readonly class ListeProjetsEnseignant
{
    /**
     * Retourne les projets de l'enseignant.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function ObtenirProjets(User $user): Collection
    {
        $projets = ProjetRecherche::whereHas('groupe.classe.cours', fn ($q) => $q->where('enseignant_id', $user->id))
            ->with([
                'typeProjet:id,nom,date_remise',
                'groupe:id,classe_id',
                'groupe.classe:id,cours_id,numero,nom',
                'groupe.classe.cours:id,nom_cours,code,groupe',
            ])
            ->orderByDesc('updated_at')
            ->get(['id', 'groupe_id', 'type_projet_id', 'titre_projet', 'date_remise', 'remis_le', 'updated_at']);

        $numeros = Groupe::numerosParClasses($projets->pluck('groupe.classe_id'));

        return $projets->map(fn (ProjetRecherche $projet) => [
            'id' => $projet->id,
            'titre_projet' => $projet->titre_projet,
            'statut' => $projet->statutActuel()->value,
            'remis_le' => $projet->remis_le?->toIso8601String(),
            'date_remise' => ($projet->typeProjet?->date_remise ?? $projet->date_remise)?->toIso8601String(),
            'type_projet' => $projet->typeProjet->only('id', 'nom'),
            'groupe' => ['id' => $projet->groupe->id, 'numero' => $numeros[$projet->groupe->id]],
            'classe' => $projet->groupe->classe->only('id', 'numero', 'nom'),
            'cours' => $projet->groupe->classe->cours->only('id', 'nom_cours', 'code', 'groupe'),
        ])->values();
    }
}
