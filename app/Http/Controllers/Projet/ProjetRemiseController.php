<?php

namespace App\Http\Controllers\Projet;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Projet\Concerns\AutoriseProjetRecherche;
use App\Http\Requests\Projet\VoterProjetRemiseRequest;
use App\Models\Classe;
use App\Models\Cours;
use App\Models\Groupe;
use App\Models\ProjetRecherche;
use App\Models\TypeProjet;
use App\Services\RemiseProjetService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Remise du travail par le groupe : remise, annulation et vote des membres.
 */
class ProjetRemiseController extends Controller
{
    use AutoriseProjetRecherche;

    /**
     * Enregistre la remise du travail par l'équipe d'étudiants.
     *
     * @throws HttpException
     */
    public function store(Cours $cours, Classe $classe, Groupe $groupe, TypeProjet $typeProjet, RemiseProjetService $remiseProjetService): JsonResponse
    {
        abort_if($classe->cours_id !== $cours->id, 404);
        abort_if($groupe->classe_id !== $classe->id, 404);
        $this->verifierTypeProjetAppartientCours($typeProjet, $cours);

        $groupe->loadMissing('membres');
        abort_unless($groupe->membres->contains('id', auth()->id()), 403);

        $projet = ProjetRecherche::firstOrCreate([
            'groupe_id' => $groupe->id,
            'type_projet_id' => $typeProjet->id,
        ]);

        // Associer la relation pour que peutEtreRemis() lise les paramètres depuis TypeProjet
        $projet->setRelation('typeProjet', $typeProjet);

        abort_if($projet->verrouille, 403, 'Ce document est verrouillé.');
        abort_unless($projet->peutEtreRemis(), 422, 'Ce travail a déjà été remis et les remises multiples ne sont pas autorisées.');

        $contenusManquants = $remiseProjetService->remettre($projet, $groupe);

        if ($contenusManquants !== []) {
            return response()->json([
                'message' => 'Le projet doit contenir les textes requis avant de pouvoir être remis.',
                'manquants' => $contenusManquants,
            ], 422);
        }

        return response()->json([
            'message' => 'remis',
            'remis_le' => $projet->remis_le->toIso8601String(),
        ]);
    }

    /**
     * Annule la remise du travail (enseignant seulement).
     *
     * @throws HttpException
     */
    public function destroy(Cours $cours, Classe $classe, Groupe $groupe, TypeProjet $typeProjet, RemiseProjetService $remiseProjetService): JsonResponse
    {
        $this->verifierTypeProjetAppartientCours($typeProjet, $cours);
        $this->autoriserEnseignant($cours, $classe, $groupe);

        $projet = $this->trouverProjet($groupe, $typeProjet);
        $projet->setRelation('typeProjet', $typeProjet);

        $remiseProjetService->annuler($projet);

        return response()->json(['message' => 'remise_annulee']);
    }

    /**
     * Enregistre ou met à jour le vote de remise d'un étudiant membre du groupe.
     *
     * Si tous les membres ont voté true, la remise est enregistrée de façon atomique.
     *
     * @throws HttpException
     */
    public function voter(VoterProjetRemiseRequest $request, Cours $cours, Classe $classe, Groupe $groupe, TypeProjet $typeProjet, RemiseProjetService $remiseProjetService): JsonResponse
    {
        abort_if($classe->cours_id !== $cours->id, 404);
        abort_if($groupe->classe_id !== $classe->id, 404);
        $this->verifierTypeProjetAppartientCours($typeProjet, $cours);

        $groupe->loadMissing('membres');
        abort_unless($groupe->membres->contains('id', auth()->id()), 403);

        $projet = $this->trouverProjet($groupe, $typeProjet);
        $projet->setRelation('typeProjet', $typeProjet);

        abort_unless($projet->peutEtreRemis(), 422, 'La remise n\'est plus possible.');

        $remiseProjetService->voter($projet, $groupe, $request->user(), (bool) $request->validated('vote'));

        return response()->json([
            'message' => 'vote_enregistre',
            'remis_le' => $projet->fresh()->remis_le?->toIso8601String(),
        ]);
    }
}
