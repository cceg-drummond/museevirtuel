<?php

namespace App\Services;

use App\Models\Cours;
use App\Models\EcheancierEtape;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Liste les échéanciers de tous les cours d'un enseignant.
 */
readonly class ListeEcheanciersEnseignant
{
    /**
     * Retourne les cours de l'enseignant avec leurs étapes et l'avancement des étudiants.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function obtenirEcheanciers(User $user): Collection
    {
        return Cours::where('enseignant_id', $user->id)
            ->orderBy('code')
            ->orderBy('groupe')
            ->with([
                'echeancierEtapes' => fn ($q) => $q->withCount([
                    'etudiantProgress as etudiants_termines' => fn ($q) => $q->where('is_done', true),
                ]),
            ])
            ->withCount(['classes as etudiants_count' => fn ($q) => $q->join(
                'classe_etudiant', 'classes.id', '=', 'classe_etudiant.classe_id')]
            )
            ->get()
            ->map(fn (Cours $cours) => $this->echeancier($cours))
            ->values();
    }

    /**
     * Construit l'échéancier d'un cours.
     *
     * @return array<string, mixed>
     */
    private function echeancier(Cours $cours): array
    {
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
