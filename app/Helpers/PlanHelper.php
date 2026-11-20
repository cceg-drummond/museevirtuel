<?php

namespace App\Helpers;

use App\Enums\NiveauSection;

/**
 * Calculs sur le plan hiérarchique d'un type de projet (chapitre → section → sous-section).
 *
 * Miroir de resources/js/lib/planSections.ts : les exports doivent numéroter comme l'éditeur.
 */
class PlanHelper
{
    /**
     * Retourne la numérotation de chaque élément du plan : « 2 », « 2.1 », « 2.1.3 ».
     *
     * @param  iterable<NiveauSection|int|null>  $niveaux  niveaux dans l'ordre du plan (null = chapitre)
     * @return list<string>
     */
    public static function numerotation(iterable $niveaux): array
    {
        $compteurs = [];
        $numeros = [];

        foreach ($niveaux as $niveau) {
            $profondeur = $niveau instanceof NiveauSection ? $niveau->value : (int) $niveau;

            // Les compteurs plus profonds repartent de zéro sous chaque nouveau parent
            $compteurs = array_slice($compteurs, 0, $profondeur + 1);
            $compteurs[$profondeur] = ($compteurs[$profondeur] ?? 0) + 1;

            // Un niveau intermédiaire absent (plan incohérent) compte pour 1 plutôt que de disparaître
            $numeros[] = implode('.', array_replace(array_fill(0, $profondeur + 1, 1), $compteurs));
        }

        return $numeros;
    }
}
