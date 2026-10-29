<?php

namespace App\Http\Requests\Concerns;

/**
 * Autorisation commune aux requêtes de gestion d'un type de projet (enseignant du cours ou admin).
 */
trait AutoriseGestionTypeProjet
{
    /**
     * Vérifie que l'utilisateur peut modifier le cours et que le type de projet lui appartient.
     *
     * Le 403 est évalué avant le 404 pour conserver l'ordre des contrôles des contrôleurs.
     */
    protected function peutGererTypeProjet(): bool
    {
        $cours = $this->route('cours');

        if ($this->user()->cannot('update', $cours)) {
            return false;
        }

        abort_if($this->route('typeProjet')->cours_id !== $cours->id, 404);

        return true;
    }
}
