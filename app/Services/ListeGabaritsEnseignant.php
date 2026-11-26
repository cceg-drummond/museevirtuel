<?php

namespace App\Services;

use App\Models\GabaritCours;
use App\Models\GabaritTypeProjet;
use Illuminate\Support\Collection;

/**
 * Construit les gabarits de cours proposés à la création d'un cours.
 */
readonly class ListeGabaritsEnseignant
{
    /**
     * Retourne les gabarits avec le nombre d'éléments de chaque partie.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function obtenirGabarits(): Collection
    {
        return GabaritCours::orderBy('nom')
            ->withCount(['objectifs', 'references', 'echeancierEtapes', 'typesProjets'])
            ->get()
            ->map(fn (GabaritCours $gabarit) => [
                'id' => $gabarit->id,
                'nom' => $gabarit->nom,
                'objectifs_count' => $gabarit->objectifs_count,
                'references_count' => $gabarit->references_count,
                'echeancier_etapes_count' => $gabarit->echeancier_etapes_count,
                'types_projets_count' => $gabarit->types_projets_count,
            ]);
    }

    /**
     * Retourne le contenu complet d'un gabarit.
     *
     * @return array<string, mixed>
     */
    public function obtenirGabarit(GabaritCours $gabarit): array
    {
        $gabarit->load(['objectifs', 'references', 'echeancierEtapes', 'typesProjets.sections']);

        return [
            'id' => $gabarit->id,
            'nom' => $gabarit->nom,
            'objectifs' => $gabarit->objectifs->map->only('id', 'contenu')->values(),
            'references' => $gabarit->references->map->only('id', 'nom', 'url')->values(),
            'etapes' => $gabarit->echeancierEtapes->map->only('id', 'semaine', 'etape')->values(),
            'types_projets' => $gabarit->typesProjets->map(fn (GabaritTypeProjet $typeProjet) => [
                ...$typeProjet->only('id', 'nom', 'description', 'ponderation', 'is_sommatif'),
                'sections' => $typeProjet->sections->map->only('id', 'label')->values(),
            ])->values(),
        ];
    }
}
