<?php

namespace App\Http\Controllers\Enseignant;

use App\Http\Controllers\Controller;
use App\Services\ListeGroupesEnseignant;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Page Groupes de l'enseignant : tous les groupes de ses cours.
 *
 * Contrôleur à action unique, car GroupeController::index redirige déjà vers la création de groupe.
 */
class GroupesEnseignantController extends Controller
{
    /**
     * Affiche la liste des groupes de tous les cours de l'enseignant connecté.
     */
    public function __invoke(ListeGroupesEnseignant $listeGroupesEnseignant): Response
    {
        return Inertia::render('Enseignant/Groupes/Index', [
            'groupes' => $listeGroupesEnseignant->pour(auth()->user()),
        ]);
    }
}
