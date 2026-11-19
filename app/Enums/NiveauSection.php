<?php

namespace App\Enums;

/**
 * Niveau hiérarchique d'une section dans le plan d'un type de projet.
 *
 * La hiérarchie se déduit de l'ordre global : un élément appartient au dernier
 * élément de niveau inférieur qui le précède.
 */
enum NiveauSection: int
{
    case Chapitre = 0;
    case Section = 1;
    case SousSection = 2;

    /**
     * Retourne le libellé français du niveau.
     */
    public function label(): string
    {
        return match ($this) {
            self::Chapitre => 'Chapitre',
            self::Section => 'Section',
            self::SousSection => 'Sous-section',
        };
    }
}
