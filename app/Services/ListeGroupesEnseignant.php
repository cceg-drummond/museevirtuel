<?php

namespace App\Services;

use App\Models\Groupe;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Liste tous les groupes des cours d'un enseignant, pour la page Groupes.
 */
readonly class ListeGroupesEnseignant
{
    /**
     * Retourne les groupes de l'enseignant triés par cours, classe puis numéro,
     * avec leur témoin jumelé et leurs nombres de membres et de projets.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function pour(User $user): Collection
    {
        $groupes = Groupe::whereHas('classe.cours', fn ($q) => $q->where('enseignant_id', $user->id))
            ->with(['classe:id,cours_id,numero,nom', 'classe.cours:id,nom_cours,code,groupe', 'temoin:id,prenom,nom'])
            ->withCount(['membres', 'projets'])
            ->orderBy('id')
            ->get();

        // Même numérotation que Groupe::getNumeroAttribute (rang par id dans la classe), sans une requête par groupe
        $numeros = [];
        $groupes->groupBy('classe_id')->each(function (Collection $groupesClasse) use (&$numeros): void {
            $groupesClasse->values()->each(function (Groupe $groupe, int $rang) use (&$numeros): void {
                $numeros[$groupe->id] = $rang + 1;
            });
        });

        return $groupes
            ->sortBy([
                fn (Groupe $a, Groupe $b) => strcmp($a->classe->cours->nom_cours, $b->classe->cours->nom_cours),
                fn (Groupe $a, Groupe $b) => $a->classe->numero <=> $b->classe->numero,
                fn (Groupe $a, Groupe $b) => $a->id <=> $b->id,
            ])
            ->map(fn (Groupe $groupe) => [
                'id' => $groupe->id,
                'numero' => $numeros[$groupe->id],
                'membres_count' => $groupe->membres_count,
                'projets_count' => $groupe->projets_count,
                'temoin' => $groupe->temoin?->only('id', 'prenom', 'nom'),
                'classe' => $groupe->classe->only('id', 'numero', 'nom'),
                'cours' => $groupe->classe->cours->only('id', 'nom_cours', 'code', 'groupe'),
            ])
            ->values();
    }
}
