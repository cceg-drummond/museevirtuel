<?php

namespace App\Http\Controllers\Enseignant;

use App\Http\Controllers\Controller;
use App\Models\Groupe;
use App\Services\ListeMediasEnseignant;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Page Médias de l'enseignant : les groupes ayant des médias, puis les médias de chacun.
 */
class MediasEnseignantController extends Controller
{
    /**
     * Affiche une carte par groupe ayant déposé des médias.
     */
    public function index(ListeMediasEnseignant $listeMediasEnseignant): Response
    {
        return Inertia::render('Enseignant/Medias/Index', [
            'groupes' => $listeMediasEnseignant->obtenirGroupes(auth()->user()),
        ]);
    }

    /**
     * Affiche les médias d'un groupe.
     */
    public function show(
        Groupe $groupe,
        ListeMediasEnseignant $listeMediasEnseignant,
    ): Response {
        $this->authorize('view', $groupe);

        return Inertia::render('Enseignant/Medias/Show', [
            'groupe' => $listeMediasEnseignant->obtenirMedias($groupe),
        ]);
    }
}
