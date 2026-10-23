<?php

namespace App\Http\Controllers\Projet\Concerns;

use App\Models\Classe;
use App\Models\Cours;
use App\Models\Groupe;
use App\Models\ProjetRecherche;
use App\Models\TypeProjet;
use App\Models\TypeProjetSection;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Autorisation commune aux contrôleurs d'entrevue (concepts et lignes).
 */
trait AutoriseEntrevue
{
    /**
     * Vérifie les autorisations et la cohérence des paramètres d'URL.
     *
     * - Classe appartient au cours
     * - Groupe appartient à la classe
     * - TypeProjet appartient au cours (anti-IDOR)
     * - Section appartient au TypeProjet
     * - Section est de type 'entrevue'
     * - L'utilisateur est membre du groupe ou enseignant
     *
     * @throws HttpException
     */
    private function autoriserEtVerifier(Cours $cours, Classe $classe, Groupe $groupe, TypeProjet $typeProjet, TypeProjetSection $section): void
    {
        abort_if($classe->cours_id !== $cours->id, 404);
        abort_if($groupe->classe_id !== $classe->id, 404);
        abort_if($typeProjet->cours_id !== $cours->id, 404);
        abort_if($section->type_projet_id !== $typeProjet->id, 404);
        abort_if($section->type !== 'entrevue', 422, 'Cette section n\'est pas de type entrevue.');

        $groupe->loadMissing('classe.cours');
        $this->authorize('manageThematiques', $groupe);
    }

    /**
     * Retourne le ProjetRecherche ou lève une 404.
     *
     * @throws HttpException
     */
    private function trouverProjet(Groupe $groupe, TypeProjet $typeProjet): ProjetRecherche
    {
        $projet = ProjetRecherche::where('groupe_id', $groupe->id)
            ->where('type_projet_id', $typeProjet->id)
            ->firstOrFail();

        // Sans la relation, peutEtreRemis() retombe sur les anciennes colonnes du projet
        return $projet->setRelation('typeProjet', $typeProjet);
    }
}
