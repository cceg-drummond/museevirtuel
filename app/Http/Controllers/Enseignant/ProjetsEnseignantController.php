<?php

namespace App\Http\Controllers\Enseignant;

use App\Http\Controllers\Controller;
use App\Services\ListeProjetsEnseignant;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Page Projets de l'enseignant : tous les projets des groupes de ses cours.
 *
 */
class ProjetsEnseignantController extends Controller
{
    /**
     * Affiche la liste des projets de tous les groupes de l'enseignant connecté.
     */
    public function __invoke(ListeProjetsEnseignant $listeProjetsEnseignant): Response
    {
        return Inertia::render('Enseignant/Projets/Index', [
            'projets' => $listeProjetsEnseignant->ObtenirProjets(auth()->user()),
        ]);
    }
}
