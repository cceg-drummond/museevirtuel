<?php

namespace App\Http\Controllers\Projet;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Projet\Concerns\AutoriseEntrevue;
use App\Http\Requests\Projet\UpdateEntrevueLigneRequest;
use App\Models\Classe;
use App\Models\Cours;
use App\Models\EntrevueConcept;
use App\Models\EntrevueLigne;
use App\Models\Groupe;
use App\Models\TypeProjet;
use App\Models\TypeProjetSection;
use App\Services\OrdreElements;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Lignes (dimension, indicateur, questions) d'un concept d'entrevue.
 */
class EntrevueLigneController extends Controller
{
    use AutoriseEntrevue;

    /**
     * Ajoute une nouvelle ligne (dimension/indicateur/questions) à un concept.
     *
     * @throws HttpException
     */
    public function store(Request $request, Cours $cours, Classe $classe, Groupe $groupe, TypeProjet $typeProjet, TypeProjetSection $section, EntrevueConcept $concept, OrdreElements $ordres): JsonResponse
    {
        $this->autoriserEtVerifier($cours, $classe, $groupe, $typeProjet, $section);

        $projet = $this->trouverProjet($groupe, $typeProjet);

        abort_if($concept->projet_id !== $projet->id || $concept->section_id !== $section->id, 404);
        abort_if($projet->verrouille, 403, 'Ce document est verrouillé.');
        abort_if(! $projet->peutEtreRemis(), 422, 'Ce travail a déjà été remis.');

        $ordre = $ordres->prochain($concept->lignes());

        $ligne = EntrevueLigne::create([
            'concept_id' => $concept->id,
            'dimension' => null,
            'indicateur' => null,
            'questions' => [],
            'ordre' => $ordre,
        ]);

        return response()->json([
            'message' => 'created',
            'ligne' => $ligne->only('id', 'ordre', 'dimension', 'indicateur', 'questions'),
        ], 201);
    }

    /**
     * Met à jour les champs d'une ligne d'entrevue.
     *
     * @throws HttpException
     */
    public function update(UpdateEntrevueLigneRequest $request, Cours $cours, Classe $classe, Groupe $groupe, TypeProjet $typeProjet, TypeProjetSection $section, EntrevueConcept $concept, EntrevueLigne $ligne): JsonResponse
    {
        $this->autoriserEtVerifier($cours, $classe, $groupe, $typeProjet, $section);

        $projet = $this->trouverProjet($groupe, $typeProjet);

        abort_if($concept->projet_id !== $projet->id || $concept->section_id !== $section->id, 404);
        abort_if($ligne->concept_id !== $concept->id, 404);
        abort_if($projet->verrouille, 403, 'Ce document est verrouillé.');
        abort_if(! $projet->peutEtreRemis(), 422, 'Ce travail a déjà été remis.');

        $validated = $request->validated();

        $ligne->update($validated);

        return response()->json(['message' => 'saved']);
    }

    /**
     * Supprime une ligne d'entrevue et réordonne les suivantes.
     *
     * @throws HttpException
     */
    public function destroy(Cours $cours, Classe $classe, Groupe $groupe, TypeProjet $typeProjet, TypeProjetSection $section, EntrevueConcept $concept, EntrevueLigne $ligne, OrdreElements $ordres): JsonResponse
    {
        $this->autoriserEtVerifier($cours, $classe, $groupe, $typeProjet, $section);

        $projet = $this->trouverProjet($groupe, $typeProjet);

        abort_if($concept->projet_id !== $projet->id || $concept->section_id !== $section->id, 404);
        abort_if($ligne->concept_id !== $concept->id, 404);
        abort_if($projet->verrouille, 403, 'Ce document est verrouillé.');
        abort_if(! $projet->peutEtreRemis(), 422, 'Ce travail a déjà été remis.');

        $ordres->supprimer($ligne, $concept->lignes());

        return response()->json(['message' => 'deleted']);
    }
}
