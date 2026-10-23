<?php

namespace App\Http\Controllers\Projet;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Projet\Concerns\AutoriseProjetRecherche;
use App\Models\Classe;
use App\Models\Cours;
use App\Models\Groupe;
use App\Models\ProjetRecherche;
use App\Models\ProjetSectionParagraphe;
use App\Models\TypeProjet;
use App\Models\TypeProjetSection;
use App\Services\OrdreElements;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Paragraphes des sections de type 'paragraphes' (CRUD + tri sur l'ordre).
 */
class ProjetSectionParagrapheController extends Controller
{
    use AutoriseProjetRecherche;

    /**
     * Ajoute un nouveau paragraphe à la fin d'une section de type 'paragraphes'.
     *
     * @throws HttpException
     */
    public function store(Request $request, Cours $cours, Classe $classe, Groupe $groupe, TypeProjet $typeProjet, TypeProjetSection $section, OrdreElements $ordres): JsonResponse
    {
        $this->verifierTypeProjetAppartientCours($typeProjet, $cours);
        abort_if($section->type_projet_id !== $typeProjet->id, 404);

        $this->autoriserMembreOuEnseignant($cours, $classe, $groupe);

        $projet = ProjetRecherche::firstOrCreate([
            'groupe_id' => $groupe->id,
            'type_projet_id' => $typeProjet->id,
        ]);
        $this->verifierEditionContenuAutorisee($cours, $classe, $groupe, $projet);

        $ordre = $ordres->prochain($this->portee($projet, $section));

        $paragraphe = ProjetSectionParagraphe::create([
            'projet_id' => $projet->id,
            'section_id' => $section->id,
            'ordre' => $ordre,
            'titre' => null,
            'contenu' => null,
        ]);

        return response()->json([
            'message' => 'created',
            'paragraphe' => $paragraphe->only('id', 'ordre', 'titre', 'contenu'),
        ], 201);
    }

    /**
     * Met à jour le titre et/ou le contenu d'un paragraphe de section.
     *
     * @throws HttpException
     */
    public function update(Request $request, Cours $cours, Classe $classe, Groupe $groupe, TypeProjet $typeProjet, TypeProjetSection $section, ProjetSectionParagraphe $paragraphe): JsonResponse
    {
        $this->verifierTypeProjetAppartientCours($typeProjet, $cours);
        abort_if($section->type_projet_id !== $typeProjet->id, 404);

        $projet = $this->trouverProjet($groupe, $typeProjet);

        abort_if($paragraphe->projet_id !== $projet->id || $paragraphe->section_id !== $section->id, 404);
        $this->verifierEditionContenuAutorisee($cours, $classe, $groupe, $projet);

        $validated = $request->validate([
            'titre' => ['nullable', 'string', 'max:500'],
            'contenu' => ['nullable', 'string'],
        ]);

        $paragraphe->update($validated);

        if (array_key_exists('contenu', $validated) && $validated['contenu'] !== null) {
            $this->supprimerAnnotationsOrphelines(
                $projet,
                'section_paragraphe_'.$paragraphe->id,
                $validated['contenu']
            );
        }

        return response()->json(['message' => 'saved']);
    }

    /**
     * Supprime un paragraphe de section et réordonne les suivants.
     *
     * Refuse la suppression si c'est le dernier paragraphe (minimum : 1).
     *
     * @throws HttpException
     */
    public function destroy(Cours $cours, Classe $classe, Groupe $groupe, TypeProjet $typeProjet, TypeProjetSection $section, ProjetSectionParagraphe $paragraphe, OrdreElements $ordres): JsonResponse
    {
        $this->verifierTypeProjetAppartientCours($typeProjet, $cours);
        abort_if($section->type_projet_id !== $typeProjet->id, 404);

        $projet = $this->trouverProjet($groupe, $typeProjet);

        abort_if($paragraphe->projet_id !== $projet->id || $paragraphe->section_id !== $section->id, 404);
        $this->verifierEditionContenuAutorisee($cours, $classe, $groupe, $projet);

        abort_if($this->portee($projet, $section)->count() <= 1, 422, 'La section doit conserver au moins un paragraphe.');

        $ordres->supprimer($paragraphe, $this->portee($projet, $section));

        return response()->json(['message' => 'deleted']);
    }

    /**
     * Met à jour l'ordre de tous les paragraphes d'une section de type 'paragraphes'.
     *
     * @throws HttpException
     */
    public function reorder(Request $request, Cours $cours, Classe $classe, Groupe $groupe, TypeProjet $typeProjet, TypeProjetSection $section, OrdreElements $ordres): JsonResponse
    {
        $this->verifierTypeProjetAppartientCours($typeProjet, $cours);
        abort_if($section->type_projet_id !== $typeProjet->id, 404);

        $projet = $this->trouverProjet($groupe, $typeProjet);

        $this->verifierEditionContenuAutorisee($cours, $classe, $groupe, $projet);

        $validated = $request->validate([
            'ordre' => ['required', 'array'],
            'ordre.*' => ['required', 'integer', 'exists:projet_section_paragraphes,id'],
        ]);

        $ordres->reordonner($this->portee($projet, $section), $validated['ordre']);

        return response()->json(['message' => 'reordered']);
    }

    /**
     * Paragraphes d'une section donnée dans ce projet : la liste sur laquelle porte l'ordre.
     *
     * @return Builder<ProjetSectionParagraphe>
     */
    private function portee(ProjetRecherche $projet, TypeProjetSection $section): Builder
    {
        return ProjetSectionParagraphe::where('projet_id', $projet->id)
            ->where('section_id', $section->id);
    }
}
