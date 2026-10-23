<?php

namespace App\Http\Controllers\Projet;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Projet\Concerns\AutoriseProjetRecherche;
use App\Models\Classe;
use App\Models\Cours;
use App\Models\Groupe;
use App\Models\ProjetConclusion;
use App\Models\ProjetRecherche;
use App\Models\TypeProjet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Conclusions individuelles des membres d'un projet.
 */
class ProjetConclusionController extends Controller
{
    use AutoriseProjetRecherche;

    /**
     * Sauvegarde la conclusion individuelle d'un membre du groupe.
     *
     * N'importe quel membre du groupe peut modifier la conclusion d'un autre membre.
     * Le user_id cible doit être validé comme membre du groupe pour éviter l'IDOR.
     *
     * @throws HttpException
     */
    public function update(Request $request, Cours $cours, Classe $classe, Groupe $groupe, TypeProjet $typeProjet): JsonResponse
    {
        $this->verifierTypeProjetAppartientCours($typeProjet, $cours);

        // Charger les membres pour valider le user_id cible (toujours nécessaire, même pour l'enseignant)
        $groupe->load(['classe.cours', 'membres']);

        // Vérifier l'autorisation avant la validation pour retourner 403 plutôt que 422
        // aux utilisateurs qui ne font pas partie du groupe et ne sont pas l'enseignant.
        $this->autoriserMembreOuEnseignant($cours, $classe, $groupe);

        $validated = $request->validate([
            'contenu' => ['nullable', 'string'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'section_id' => ['nullable', 'integer', Rule::exists('type_projet_sections', 'id')->where('type_projet_id', $typeProjet->id)],
        ]);

        abort_unless(
            $groupe->membres->contains('id', $validated['user_id']),
            422,
            'Cet étudiant n\'est pas membre du groupe.',
        );

        $projet = ProjetRecherche::firstOrCreate([
            'groupe_id' => $groupe->id,
            'type_projet_id' => $typeProjet->id,
        ]);

        $this->verifierEditionContenuAutorisee($cours, $classe, $groupe, $projet);

        $clé = ['projet_id' => $projet->id, 'user_id' => $validated['user_id']];
        if (isset($validated['section_id'])) {
            $clé['section_id'] = $validated['section_id'];
        }

        ProjetConclusion::updateOrCreate(
            $clé,
            ['contenu' => $validated['contenu']],
        );

        return response()->json(['message' => 'saved']);
    }
}
