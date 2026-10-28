<?php

namespace App\Http\Controllers\Projet;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Projet\Concerns\AutoriseEntrevue;
use App\Http\Requests\Commun\ReorderRequest;
use App\Http\Requests\Projet\SaveEntrevueConceptRequest;
use App\Models\Classe;
use App\Models\Cours;
use App\Models\EntrevueConcept;
use App\Models\Groupe;
use App\Models\ProjetRecherche;
use App\Models\TypeProjet;
use App\Models\TypeProjetSection;
use App\Services\OrdreElements;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EntrevueConceptController extends Controller
{
    use AutoriseEntrevue;

    /**
     * Crée un nouveau concept dans une section de type 'entrevue'.
     *
     * @throws HttpException
     */
    public function store(SaveEntrevueConceptRequest $request, Cours $cours, Classe $classe,
        Groupe $groupe, TypeProjet $typeProjet, TypeProjetSection $section,
        OrdreElements $ordres): JsonResponse
    {
        $this->autoriserEtVerifier($cours, $classe, $groupe, $typeProjet, $section);

        $projet = ProjetRecherche::firstOrCreate([
            'groupe_id' => $groupe->id,
            'type_projet_id' => $typeProjet->id,
        ]);

        abort_if($projet->verrouille, 403, 'Ce document est verrouillé.');
        abort_if(! $projet->peutEtreRemis(), 422, 'Ce travail a déjà été remis.');

        $validated = $request->validated();

        $ordre = $ordres->prochain($this->portee($projet, $section));

        $concept = EntrevueConcept::create([
            'projet_id' => $projet->id,
            'section_id' => $section->id,
            'label' => $validated['label'],
            'ordre' => $ordre,
        ]);

        return response()->json([
            'message' => 'created',
            'concept' => $this->formaterConcept($concept),
        ], 201);
    }

    /**
     * Met à jour le label d'un concept existant.
     *
     * @throws HttpException
     */
    public function update(SaveEntrevueConceptRequest $request, Cours $cours, Classe $classe, Groupe $groupe, TypeProjet $typeProjet, TypeProjetSection $section, EntrevueConcept $concept): JsonResponse
    {
        $this->autoriserEtVerifier($cours, $classe, $groupe, $typeProjet, $section);

        $projet = $this->trouverProjet($groupe, $typeProjet);

        abort_if($concept->projet_id !== $projet->id || $concept->section_id !== $section->id, 404);
        abort_if($projet->verrouille, 403, 'Ce document est verrouillé.');
        abort_if(! $projet->peutEtreRemis(), 422, 'Ce travail a déjà été remis.');

        $validated = $request->validated();

        $concept->update($validated);

        return response()->json(['message' => 'saved']);
    }

    /**
     * Supprime un concept et réordonne les suivants.
     *
     * @throws HttpException
     */
    public function destroy(Cours $cours, Classe $classe, Groupe $groupe, TypeProjet $typeProjet, TypeProjetSection $section, EntrevueConcept $concept, OrdreElements $ordres): JsonResponse
    {
        $this->autoriserEtVerifier($cours, $classe, $groupe, $typeProjet, $section);

        $projet = $this->trouverProjet($groupe, $typeProjet);

        abort_if($concept->projet_id !== $projet->id || $concept->section_id !== $section->id, 404);
        abort_if($projet->verrouille, 403, 'Ce document est verrouillé.');
        abort_if(! $projet->peutEtreRemis(), 422, 'Ce travail a déjà été remis.');

        $ordres->supprimer($concept, $this->portee($projet, $section));

        return response()->json(['message' => 'deleted']);
    }

    /**
     * Met à jour l'ordre de tous les concepts d'une section de type 'entrevue'.
     *
     * @throws HttpException
     */
    public function reorder(ReorderRequest $request, Cours $cours, Classe $classe, Groupe $groupe, TypeProjet $typeProjet, TypeProjetSection $section, OrdreElements $ordres): JsonResponse
    {
        $this->autoriserEtVerifier($cours, $classe, $groupe, $typeProjet, $section);

        $projet = $this->trouverProjet($groupe, $typeProjet);

        abort_if($projet->verrouille, 403, 'Ce document est verrouillé.');

        $ordres->reordonner($this->portee($projet, $section), $request->ids());

        return response()->json(['message' => 'reordered']);
    }

    // ─── Méthodes privées ─────────────────────────────────────────────────────

    /**
     * Formate un concept avec ses lignes pour la réponse JSON.
     *
     * @return array<string, mixed>
     */
    private function formaterConcept(EntrevueConcept $concept): array
    {
        return [
            'id' => $concept->id,
            'label' => $concept->label,
            'ordre' => $concept->ordre,
            'lignes' => [],
        ];
    }

    /**
     * Concepts d'une section donnée dans ce projet : la liste sur laquelle porte l'ordre.
     */
    private function portee(ProjetRecherche $projet, TypeProjetSection $section): EntrevueConcept
    {
        return EntrevueConcept::where('projet_id', $projet->id)
            ->where('section_id', $section->id);
    }
}
