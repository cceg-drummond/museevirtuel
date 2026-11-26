<?php

namespace App\Http\Controllers\Enseignant;

use App\Http\Controllers\Controller;
use App\Services\ListeEchangesEnseignant;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Page Échanges de l'enseignant : les conversations groupe-aîné de ses cours.
 */
class EchangesEnseignantController extends Controller
{
    /**
     * Affiche la liste des conversations de l'enseignant connecté.
     */
    public function __invoke(ListeEchangesEnseignant $listeEchangesEnseignant): Response
    {
        return Inertia::render('Enseignant/Echanges/Index', [
            'conversations' => $listeEchangesEnseignant->obtenirConversations(auth()->user()),
        ]);
    }
}
