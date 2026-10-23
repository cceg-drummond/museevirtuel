<?php

namespace App\Http\Controllers\Classe;

use App\Http\Controllers\Controller;
use App\Models\Classe;
use App\Models\Cours;
use App\Models\TypeProjet;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Aperçus des notes d'une classe (par type de projet et cumulées).
 */
class ClasseNotesApercuController extends Controller
{
    /**
     * Affiche la page d'aperçu des notes de toute la classe pour un TypeProjet donné.
     *
     * Agrège les notes de tous les groupes de la classe : une ligne par étudiant
     * avec son numéro DA et sa note finale (format "DA NOTE").
     * Accessible aux enseignants et admins uniquement.
     *
     * @throws AuthorizationException
     * @throws HttpException
     */
    public function show(Cours $cours, Classe $classe, TypeProjet $typeProjet): Response
    {
        $this->autoriserEnseignantOuAdmin($cours, $classe);

        // Charger les critères du TypeProjet pour le calcul des notes
        $typeProjet->load('criteres');

        $classe->load([
            'groupes.membres',
            'groupes.projets' => fn ($q) => $q->where('type_projet_id', $typeProjet->id),
            'groupes.projets.critereCorrections',
        ]);

        // Maximum de points positifs — diviseur pour le calcul en pourcentage
        $totalMax = $typeProjet->criteres
            ->where('type', 'positif')
            ->sum(fn ($c) => (float) $c->pointage);

        $lignes = collect();

        foreach ($classe->groupes as $groupe) {
            // Un groupe a au plus un projet par TypeProjet
            $projet = $groupe->projets->first();

            foreach ($groupe->membres as $membre) {
                $note = null;

                if ($projet !== null && $totalMax > 0) {
                    $corrections = $projet->critereCorrections;
                    $totalPoints = 0.0;

                    foreach ($typeProjet->criteres as $critere) {
                        // La correction individuelle prime sur la correction de groupe (user_id null)
                        $individuelle = $corrections->first(
                            fn ($cor) => $cor->critere_id === $critere->id && $cor->user_id === $membre->id
                        );
                        $groupeCorrection = $corrections->first(
                            fn ($cor) => $cor->critere_id === $critere->id && $cor->user_id === null
                        );
                        $correction = $individuelle ?? $groupeCorrection;

                        if ($correction === null) {
                            continue;
                        }

                        if ($critere->type === 'positif' && $correction->verifie) {
                            $totalPoints += $correction->points !== null
                                ? (float) $correction->points
                                : (float) $critere->pointage;
                        } elseif ($critere->type === 'negatif') {
                            $totalPoints -= (float) ($correction->points ?? $critere->pointage);
                        }
                    }

                    $note = round($totalPoints / $totalMax * 100, 2);
                }

                $lignes->push([
                    'da' => preg_replace('/\D/', '', (string) $membre->no_da),
                    'nom' => $membre->nom,
                    'prenom' => $membre->prenom,
                    'note' => $note,
                ]);
            }
        }

        return Inertia::render('Classes/ApercuNotes', [
            'cours' => $cours->only('id', 'nom_cours'),
            'classe' => ['id' => $classe->id, 'numero' => $classe->numero, 'nom' => $classe->nom],
            'typeProjet' => ['id' => $typeProjet->id, 'nom' => $typeProjet->nom],
            'lignes' => $lignes->values(),
        ]);
    }

    /**
     * Affiche la page d'aperçu des notes accumulées (pondérées) de toute la classe.
     *
     * Agrège les notes finales de chaque étudiant sur tous les TypeProjets ayant
     * une pondération définie, et calcule le total pondéré : Σ(noteFinale × pondération / 100).
     * Accessible aux enseignants et admins uniquement.
     *
     * @throws AuthorizationException
     * @throws HttpException
     */
    public function accumulees(Cours $cours, Classe $classe): Response
    {
        $this->autoriserEnseignantOuAdmin($cours, $classe);

        // TypeProjets sommatifs du cours ayant une pondération définie.
        // Les projets non-sommatifs (is_sommatif = false) n'ont pas de poids sur la note finale.
        $typesProjets = $cours->typesProjets()
            ->where('is_sommatif', true)
            ->whereNotNull('ponderation')
            ->orderBy('nom')
            ->with('criteres')
            ->get();

        $typeProjetIds = $typesProjets->pluck('id');

        $classe->load([
            'groupes.membres',
            'groupes.projets' => fn ($q) => $q->whereIn('type_projet_id', $typeProjetIds),
            'groupes.projets.critereCorrections',
        ]);

        $lignes = collect();

        foreach ($classe->groupes as $groupe) {
            // Index des projets du groupe par type_projet_id pour accès O(1)
            $projetParType = $groupe->projets->keyBy('type_projet_id');

            foreach ($groupe->membres as $membre) {
                $notesParType = [];
                $total = 0.0;

                foreach ($typesProjets as $tp) {
                    $projet = $projetParType->get($tp->id);

                    if ($projet === null) {
                        $notesParType[$tp->id] = null;

                        continue;
                    }

                    $corrections = $projet->critereCorrections;
                    $totalMax = $tp->criteres->where('type', 'positif')->sum(fn ($c) => (float) $c->pointage);
                    $totalPoints = 0.0;

                    foreach ($tp->criteres as $critere) {
                        // La correction individuelle prime sur la correction de groupe (user_id null)
                        $individuelle = $corrections->first(fn ($cor) => $cor->critere_id === $critere->id && $cor->user_id === $membre->id);
                        $groupeCorrection = $corrections->first(fn ($cor) => $cor->critere_id === $critere->id && $cor->user_id === null);
                        $correction = $individuelle ?? $groupeCorrection;

                        if ($correction === null) {
                            continue;
                        }

                        if ($critere->type === 'positif' && $correction->verifie) {
                            $totalPoints += $correction->points !== null
                                ? (float) $correction->points
                                : (float) $critere->pointage;
                        } elseif ($critere->type === 'negatif') {
                            $totalPoints -= (float) ($correction->points ?? $critere->pointage);
                        }
                    }

                    $note = $totalMax > 0 ? round($totalPoints / $totalMax * 100, 2) : null;
                    $notesParType[$tp->id] = $note;

                    if ($note !== null) {
                        $total += $note * ((float) $tp->ponderation / 100);
                    }
                }

                $lignes->push([
                    'da' => preg_replace('/\D/', '', (string) $membre->no_da),
                    'prenom' => $membre->prenom,
                    'nom' => $membre->nom,
                    'notes_par_type' => $notesParType,
                    'total' => round($total, 2),
                ]);
            }
        }

        $somme = $typesProjets->sum('ponderation');

        return Inertia::render('Classes/ApercuNotesAccumulees', [
            'cours' => $cours->only('id', 'nom_cours'),
            'classe' => ['id' => $classe->id, 'numero' => $classe->numero, 'nom' => $classe->nom],
            'typesProjets' => $typesProjets->map(fn ($tp) => [
                'id' => $tp->id,
                'nom' => $tp->nom,
                'ponderation' => (float) $tp->ponderation,
            ])->values(),
            'lignes' => $lignes->values(),
            'sommePonderations' => (float) $somme,
        ]);
    }

    /**
     * Valide que la classe appartient au cours, que l'utilisateur a accès via la Policy,
     * et qu'il est l'enseignant du cours ou un admin.
     *
     * Factorisé ici car ce triplet apparaît dans toutes les méthodes réservées
     * aux enseignants (apercuNotesClasse, apercuNotesAccumulees, etc.).
     *
     * @throws AuthorizationException
     * @throws HttpException
     */
    private function autoriserEnseignantOuAdmin(Cours $cours, Classe $classe): void
    {
        abort_if($classe->cours_id !== $cours->id, 404);

        $classe->load('cours');
        $this->authorize('view', $classe);

        $user = auth()->user();
        abort_unless(
            $user->isAdmin() || ($user->isEnseignant() && $cours->enseignant_id === $user->id),
            403,
        );
    }
}
