<?php

namespace App\Services;

use App\Models\Cours;
use App\Models\EcheancierEtape;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Construit les échéanciers des cours d'un enseignant.
 */
readonly class ListeEcheanciersEnseignant
{
    /**
     * Retourne les cours de l'enseignant avec le nombre d'étapes, terminées ou non.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function obtenirCours(User $user): Collection
    {
        return Cours::where('enseignant_id', $user->id)
            ->orderBy('code')
            ->orderBy('groupe')
            ->withCount([
                'echeancierEtapes as etapes_count',
                'echeancierEtapes as etapes_terminees_count' => fn ($q) => $q->where('is_done', true),
            ])
            ->get()
            ->map(fn (Cours $cours) => [
                ...$cours->only('id', 'nom_cours', 'code', 'groupe'),
                'etapes_count' => $cours->etapes_count,
                'etapes_terminees_count' => $cours->etapes_terminees_count,
            ]);
    }

    /**
     * Retourne l'échéancier d'un cours avec l'avancement des étudiants pour chaque étape.
     *
     * @return array<string, mixed>
     */
    public function obtenirEcheancier(Cours $cours): array
    {
        $cours->load(['echeancierEtapes' => fn ($q) => $q->withCount([
            'etudiantProgress as etudiants_termines' => fn ($q) => $q->where('is_done', true),
        ])])->loadCount(['classes as etudiants_count' => fn ($q) => $q->join(
            'classe_etudiant', 'classes.id', '=', 'classe_etudiant.classe_id'
        )]);

        return [
            'cours' => $cours->only('id', 'nom_cours', 'code', 'groupe'),
            'etudiants_count' => $cours->etudiants_count,
            'etapes' => $cours->echeancierEtapes->map(fn (EcheancierEtape $etape) => [
                'id' => $etape->id,
                'semaine' => $etape->semaine,
                'periode' => $etape->periode,
                'etape' => $etape->etape,
                'is_done' => $etape->is_done,
                'etudiants_termines' => $etape->etudiants_termines,
            ])->values(),
        ];
    }
}
