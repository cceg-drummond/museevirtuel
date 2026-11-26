<?php

namespace App\Http\Controllers\Enseignant;

use App\Http\Controllers\Controller;
use App\Models\GabaritCours;
use App\Services\ListeGabaritsEnseignant;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Page Gabarits de l'enseignant : les gabarits de cours, puis le contenu de chacun.
 */
class GabaritsEnseignantController extends Controller
{
    /**
     * Affiche une carte par gabarit de cours.
     */
    public function index(ListeGabaritsEnseignant $listeGabaritsEnseignant): Response
    {
        return Inertia::render('Enseignant/Gabarits/Index', [
            'gabarits' => $listeGabaritsEnseignant->obtenirGabarits(),
        ]);
    }

    /**
     * Affiche le contenu d'un gabarit de cours.
     */
    public function show(
        GabaritCours $gabarit,
        ListeGabaritsEnseignant $listeGabaritsEnseignant,
    ): Response {
        return Inertia::render('Enseignant/Gabarits/Show', [
            'gabarit' => $listeGabaritsEnseignant->obtenirGabarit($gabarit),
        ]);
    }
}
