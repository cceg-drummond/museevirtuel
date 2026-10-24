<?php

namespace App\Services;

use App\Models\ProjetAnnotation;
use App\Models\ProjetCritereCorrection;
use App\Models\TypeProjetCritere;
use Illuminate\Support\Collection;

/**
 * Calcule la note d'un étudiant sur un projet.
 *
 * La correction individuelle de l'étudiant prime sur la correction de groupe
 * Critère positif : compté seulement s'il est vérifié (points saisis, sinon le pointage complet)
 * Critère négatif : on retire les points saisis, sinon le pointage complet
 * Les malus d'annotation visant l'étudiant ou tout le groupe sont déduits.
 */
class CalculNotesService
{
    /**
     * Retourne les points obtenus par l'étudiant.
     *
     * @param  Collection<int, TypeProjetCritere>  $criteres
     * @param  Collection<int, ProjetCritereCorrection>  $corrections
     * @param  Collection<int, ProjetAnnotation>  $annotations
     */
    public function points(Collection $criteres, Collection $corrections,
        Collection $annotations, int $userId): float
    {
        $correctionsParCritere = $corrections->groupBy('critere_id');
        $obtenu = 0.0;

        foreach ($criteres as $critere) {
            $candidates = $correctionsParCritere->get($critere->id, collect());
            $correction = $candidates->first(fn (ProjetCritereCorrection $c) => $c->user_id === $userId)
                ?? $candidates->first(fn (ProjetCritereCorrection $c) => $c->user_id === null);

            $points = $correction->points !== null ? (float) $correction->points : (float) $critere->pointage;

            if ($critere->type === 'positif') {
                $obtenu += $correction->verifie ? $points : 0.0;
            } else {
                $obtenu -= $points;
            }
        }

        $malus = $annotations
            ->filter(fn (ProjetAnnotation $a) => $a->points_malus !== null
                && ($a->cible_user_id === null || $a->cible_user_id === $userId))
            ->sum(fn (ProjetAnnotation $a) => (float) $a->points_malus);

        return $obtenu - $malus;
    }

    /**
     * Retourne le maximum atteignable : la somme des pointages des critères positifs.
     *
     * @param  Collection<int, TypeProjetCritere>  $criteres
     */
    public function maximum(Collection $criteres): float
    {
        return (float) $criteres
            ->where('type', 'positif')
            ->sum(fn (TypeProjetCritere $c) => (float) $c->pointage);
    }

    /**
     * Retourne la note en pourcentage, ou null si le projet n'a aucun critère positif.
     *
     * @param  Collection<int, TypeProjetCritere>  $criteres
     * @param  Collection<int, ProjetCritereCorrection>  $corrections
     * @param  Collection<int, ProjetAnnotation>  $annotations
     */
    public function pourcentage(Collection $criteres, Collection $corrections, Collection $annotations, int $userId): ?float
    {
        $maximum = $this->maximum($criteres);

        if ($maximum <= 0) {
            return null;
        }

        return round($this->points($criteres, $corrections, $annotations, $userId) / $maximum * 100, 2);
    }
}
