<?php

namespace App\Http\Controllers\Enseignant;

use App\Http\Controllers\Controller;
use App\Models\Cours;
use App\Services\ListeEcheanciersEnseignant;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Page Échéancier de l'enseignant : ses cours, puis l'échéancier de chacun.
 */
class EcheancierEnseignantController extends Controller
{
    /**
     * Affiche une carte par cours de l'enseignant connecté.
     */
    public function index(ListeEcheanciersEnseignant $listeEcheanciersEnseignant): Response
    {
        return Inertia::render('Enseignant/Echeancier/Index', [
            'cours' => $listeEcheanciersEnseignant->obtenirCours(auth()->user()),
        ]);
    }

    /**
     * Affiche l'échéancier d'un cours.
     */
    public function show(
        Cours $cours,
        ListeEcheanciersEnseignant $listeEcheanciersEnseignant,
    ): Response {
        $this->authorize('update', $cours);

        return Inertia::render('Enseignant/Echeancier/Show', [
            'echeancier' => $listeEcheanciersEnseignant->obtenirEcheancier($cours),
        ]);
    }
}
