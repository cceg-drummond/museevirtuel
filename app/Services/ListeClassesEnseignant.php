<?php

namespace App\Services;

use App\Models\Classe;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Liste toutes les classes (sections) des cours d'un enseignant, pour la page Classes.
 */
readonly class ListeClassesEnseignant
{
    /**
     * Retourne les classes de l'enseignant triées par cours puis par numéro,
     * avec leur cours, leur horaire et leurs nombres d'étudiants et de groupes.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function pour(User $user): Collection
    {
        return Classe::whereHas('cours', fn ($q) => $q->where('enseignant_id', $user->id))
            ->with('cours:id,nom_cours,code,groupe,session,annee,is_verrouille')
            ->withCount(['etudiants', 'groupes'])
            ->get()
            ->sortBy([
                fn (Classe $a, Classe $b) => strcmp($a->cours->nom_cours, $b->cours->nom_cours),
                fn (Classe $a, Classe $b) => $a->numero <=> $b->numero,
            ])
            ->map(fn (Classe $classe) => [
                'id' => $classe->id,
                'numero' => $classe->numero,
                'nom' => $classe->nom,
                'jour_semaine' => $classe->jour_semaine,
                'plage_horaire' => $classe->plage_horaire,
                'etudiants_count' => $classe->etudiants_count,
                'groupes_count' => $classe->groupes_count,
                'cours' => $classe->cours->only('id', 'nom_cours', 'code', 'groupe', 'session', 'annee', 'is_verrouille'),
            ])
            ->values();
    }
}
