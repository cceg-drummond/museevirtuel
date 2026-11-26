<?php

namespace App\Http\Controllers\Enseignant;

use App\Http\Controllers\Controller;
use App\Models\TypeProjetSection;
use App\Services\ListeQuestionsEnseignant;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Page Banque de questions de l'enseignant : ses banques, puis les questions de chacune.
 */
class QuestionsEnseignantController extends Controller
{
    /**
     * Affiche une carte par banque de questions de l'enseignant connecté.
     */
    public function index(ListeQuestionsEnseignant $listeQuestionsEnseignant): Response
    {
        return Inertia::render('Enseignant/Questions/Index', [
            'banques' => $listeQuestionsEnseignant->obtenirBanques(auth()->user()),
        ]);
    }

    /**
     * Affiche les questions d'une banque.
     */
    public function show(
        TypeProjetSection $section,
        ListeQuestionsEnseignant $listeQuestionsEnseignant,
    ): Response {
        abort_if($section->type !== 'choix_questions', 404);
        $this->authorize('update', $section->typeProjet);

        return Inertia::render('Enseignant/Questions/Show', [
            'banque' => $listeQuestionsEnseignant->obtenirBanque($section),
        ]);
    }
}
