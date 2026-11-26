<?php

namespace App\Http\Controllers\Enseignant;

use App\Http\Controllers\Controller;
use App\Services\ListeEcheanciersEnseignant;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Page Échéancier de l'enseignant : les étapes de tous ses cours.
 */
class EcheancierEnseignantController extends Controller
{
    /**
     * Affiche les échéanciers des cours de l'enseignant connecté.
     */
    public function __invoke(ListeEcheanciersEnseignant $listeEcheanciersEnseignant): Response
    {
        return Inertia::render('Enseignant/Echeancier/Index', [
            'echeanciers' => $listeEcheanciersEnseignant->obtenirEcheanciers(auth()->user()),
        ]);
    }
}
