<?php

namespace App\Http\Controllers\Projet;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Projet\Concerns\AutoriseProjetRecherche;
use App\Http\Requests\Projet\ClonerProjetCritereCorrectionRequest;
use App\Http\Requests\Projet\UpsertProjetCritereCorrectionRequest;
use App\Models\Classe;
use App\Models\Cours;
use App\Models\Groupe;
use App\Models\ProjetCritereCorrection;
use App\Models\ProjetRecherche;
use App\Models\TypeProjet;
use App\Models\TypeProjetCritere;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Corrections des critères d'évaluation par l'enseignant.
 */
class ProjetCritereCorrectionController extends Controller
{
    use AutoriseProjetRecherche;

    /**
     * Crée ou met à jour la correction d'un critère pour ce projet.
     *
     * `user_id` null = correction appliquée à tout le groupe.
     * Pour un critère positif, `verifie = true` et `points = null` accorde
     * automatiquement le pointage complet du critère.
     *
     * @throws HttpException
     */
    public function upsert(
        UpsertProjetCritereCorrectionRequest $request,
        Cours $cours,
        Classe $classe,
        Groupe $groupe,
        TypeProjet $typeProjet,
        TypeProjetCritere $critere,
    ): JsonResponse {
        $this->verifierTypeProjetAppartientCours($typeProjet, $cours);
        $this->autoriserEnseignant($cours, $classe, $groupe);
        abort_if($critere->type_projet_id !== $typeProjet->id, 404);

        $validated = $request->validated();

        $projet = ProjetRecherche::firstOrCreate([
            'groupe_id' => $groupe->id,
            'type_projet_id' => $typeProjet->id,
        ]);

        $verifie = $request->boolean('verifie', false);

        [$correction, $idsRetires] = DB::transaction(function () use ($validated, $projet, $critere, $verifie) {
            $idsRetires = [];
            if (! isset($validated['user_id'])) {
                $idsRetires = ProjetCritereCorrection::where('projet_id', $projet->id)
                    ->where('critere_id', $critere->id)
                    ->whereNotNull('user_id')
                    ->pluck('user_id')
                    ->all();

                if (! empty($idsRetires)) {
                    ProjetCritereCorrection::where('projet_id', $projet->id)
                        ->where('critere_id', $critere->id)
                        ->whereNotNull('user_id')
                        ->delete();
                }
            }

            $correction = ProjetCritereCorrection::updateOrCreate(
                [
                    'projet_id' => $projet->id,
                    'critere_id' => $critere->id,
                    'user_id' => $validated['user_id'] ?? null,
                ],
                [
                    'points' => $validated['points'] ?? null,
                    'commentaire' => $validated['commentaire'] ?? null,
                    'verifie' => $verifie,
                ]
            );

            return [$correction, $idsRetires];
        });

        return response()->json([
            'message' => __('reponse.saved'),
            'correction' => $correction->only('id', 'projet_id', 'critere_id', 'user_id', 'points', 'commentaire', 'verifie', 'source_id'),
            'cleared_user_ids' => $idsRetires,
        ]);
    }

    /**
     * Supprime une correction de critère et tous ses clones.
     *
     * @throws HttpException
     */
    public function destroy(
        Cours $cours,
        Classe $classe,
        Groupe $groupe,
        TypeProjet $typeProjet,
        ProjetCritereCorrection $correction,
    ): JsonResponse {
        $this->verifierTypeProjetAppartientCours($typeProjet, $cours);
        $this->autoriserEnseignant($cours, $classe, $groupe);

        $projet = $this->trouverProjet($groupe, $typeProjet);
        abort_if($correction->projet_id !== $projet->id, 404);

        DB::transaction(function () use ($correction) {
            $correction->clones()->delete();
            $correction->delete();
        });

        return response()->json(['message' => __('reponse.deleted')]);
    }

    /**
     * Clone une correction de groupe pour appliquer des points différents à un étudiant.
     *
     * La correction source peut être une correction de groupe (user_id = null) ou
     * individuelle. Le clone remplace toute correction individuelle existante pour
     * le même (projet, critère, étudiant).
     *
     * @throws HttpException
     */
    public function cloner(
        ClonerProjetCritereCorrectionRequest $request,
        Cours $cours,
        Classe $classe,
        Groupe $groupe,
        TypeProjet $typeProjet,
        ProjetCritereCorrection $correction,
    ): JsonResponse {
        $this->verifierTypeProjetAppartientCours($typeProjet, $cours);
        $this->autoriserEnseignant($cours, $classe, $groupe);

        $projet = $this->trouverProjet($groupe, $typeProjet);
        abort_if($correction->projet_id !== $projet->id, 404);

        $validated = $request->validated();

        $verifie = $request->boolean('verifie', (bool) $correction->verifie);

        $clone = DB::transaction(function () use ($projet, $correction, $validated, $verifie) {
            ProjetCritereCorrection::where('projet_id', $projet->id)
                ->where('critere_id', $correction->critere_id)
                ->where('user_id', $validated['user_id'])
                ->delete();

            return ProjetCritereCorrection::create([
                'projet_id' => $projet->id,
                'critere_id' => $correction->critere_id,
                'user_id' => $validated['user_id'],
                'points' => $validated['points'] ?? null,
                'commentaire' => $validated['commentaire'] ?? null,
                'verifie' => $verifie,
                'source_id' => $correction->id,
            ]);
        });

        return response()->json([
            'message' => __('reponse.cloned'),
            'correction' => $clone->only('id', 'projet_id', 'critere_id', 'user_id', 'points', 'commentaire', 'verifie', 'source_id'),
        ]);
    }
}
