<?php

namespace App\Http\Controllers\Classe;

use App\Http\Controllers\Controller;
use App\Models\Classe;
use App\Models\Cours;
use App\Models\EcheancierEtudiantProgress;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ClasseController extends Controller
{
    /**
     * Affiche le détail d'une classe (section) avec ses groupes.
     *
     * Accessible aux étudiants inscrits, à l'enseignant et aux admins.
     *
     * @throws AuthorizationException
     * @throws HttpException
     */
    public function show(Cours $cours, Classe $classe): Response
    {
        abort_if($classe->cours_id !== $cours->id, 404);

        $classe->load('cours');
        $this->authorize('view', $classe);

        $user = auth()->user();
        /**
         * Il faudra retiré la portion $estEnseignant = $user->isEnseignant()
         * && en production.
         */
        $estEnseignant = $user->isEnseignant()
            && $cours->enseignant_id === $user->id;

        $classe->load([
            'groupes.membres',
            'groupes.thematiques',
            'groupes.temoin',
            'etudiants' => fn ($query) => $query->withPivot('statut_cours'),
        ]);

        // TypeProjets du cours — pour les boutons d'aperçu notes (enseignant seulement)
        $typesProjets = $estEnseignant
            ? $cours->typesProjets()->get(['id', 'nom', 'ponderation', 'is_sommatif'])
            : collect();

        // Échéancier, documents, objectifs et références — pour les étudiants uniquement
        $echeancierEtapes = collect();
        $documents = collect();
        $objectifs = collect();
        $references = collect();

        if (! $estEnseignant) {
            $etapes = $cours->echeancierEtapes()->get();
            $progressions = EcheancierEtudiantProgress::whereIn('echeancier_etape_id', $etapes->pluck('id'))
                ->where('user_id', $user->id)
                ->pluck('is_done', 'echeancier_etape_id');

            $echeancierEtapes = $etapes->map(fn ($etape) => [
                'id' => $etape->id,
                'semaine' => $etape->semaine,
                'etape' => $etape->etape,
                'is_done' => $etape->is_done,
                'ordre' => $etape->ordre,
                'etudiant_done' => (bool) ($progressions[$etape->id] ?? false),
            ]);

            $documents = $cours->documents()->get()->map(fn ($doc) => [
                'id' => $doc->id,
                'nom_original' => $doc->nom_original,
                'url' => $doc->url,
                'type' => $doc->type,
                'taille' => $doc->taille,
            ]);

            $objectifs = $cours->objectifs()->orderBy('ordre')->get()->map(fn ($o) => [
                'id' => $o->id,
                'contenu' => $o->contenu,
                'ordre' => $o->ordre,
            ]);

            $references = $cours->references()->get()->map(fn ($r) => [
                'id' => $r->id,
                'nom' => $r->nom,
                'url' => $r->url,
                'ordre' => $r->ordre,
            ]);
        }

        return Inertia::render('Classes/Show', [
            'cours' => $cours->only('id', 'nom_cours', 'code', 'groupe'),
            'classe' => $classe,
            'utilisateurConnecteId' => $user->id,
            'estEnseignant' => $estEnseignant,
            'typesProjets' => $typesProjets,
            'echeancierEtapes' => $echeancierEtapes,
            'documents' => $documents,
            'objectifs' => $objectifs,
            'references' => $references,
        ]);
    }

    /**
     * Crée une nouvelle classe (section) dans un cours.
     *
     * Réservé à l'enseignant du cours et aux admins.
     *
     * @throws AuthorizationException
     */
    public function store(Request $request, Cours $cours): RedirectResponse
    {
        $this->authorize('update', $cours);

        $validated = $request->validate(
            $this->reglesValidationClasse($cours),
            $this->messagesValidationClasse(),
        );

        Classe::create(array_merge(
            ['cours_id' => $cours->id, 'code' => $cours->code],
            array_filter($validated, fn ($v) => $v !== null)
        ));

        return back()->with('success', 'Classe créée.');
    }

    /**
     * Met à jour une classe (section) : code et/ou nom.
     *
     * Réservé à l'enseignant du cours et aux admins.
     *
     * @throws AuthorizationException
     * @throws HttpException
     */
    public function update(Request $request, Cours $cours, Classe $classe): RedirectResponse
    {
        abort_if($classe->cours_id !== $cours->id, 404);
        $this->authorize('update', $classe);

        $validated = $request->validate(
            $this->reglesValidationClasse($cours, $classe),
            $this->messagesValidationClasse(),
        );

        $classe->update(array_merge($validated, ['code' => $cours->code]));

        return back()->with('success', 'Classe mise à jour.');
    }

    /**
     * Retourne les règles de validation pour la création ou la mise à jour d'une classe.
     *
     * @return array<string, array<int, mixed>>
     */
    private function reglesValidationClasse(Cours $cours, ?Classe $classe = null): array
    {
        $regleNumero = Rule::unique('classes')->where(fn ($q) => $q->where('cours_id', $cours->id));

        if ($classe !== null) {
            $regleNumero = $regleNumero->ignore($classe->id);
        }

        return [
            'numero' => ['required', 'digits:5', $regleNumero],
            'nom' => ['nullable', 'string', 'max:100'],
            'jour_semaine' => ['nullable', 'string', 'max:20'],
            'plage_horaire' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Retourne les messages d'erreur de validation en français pour une classe.
     *
     * @return array<string, string>
     */
    private function messagesValidationClasse(): array
    {
        return [
            'numero.required' => 'Le numéro de classe est obligatoire.',
            'numero.digits' => 'Les 5 chiffres du groupe doivent être présents.',
            'numero.unique' => 'Ce numéro de classe est déjà utilisé pour ce cours.',
        ];
    }

    /**
     * Supprime une classe (section) — cascade sur les groupes et projets.
     *
     * Réservé à l'enseignant du cours et aux admins.
     *
     * @throws AuthorizationException
     * @throws HttpException
     */
    public function destroy(Cours $cours, Classe $classe): RedirectResponse
    {
        abort_if($classe->cours_id !== $cours->id, 404);

        $classe->load('cours');
        $this->authorize('delete', $classe);

        $classe->delete();

        return redirect()->route('cours.show', $cours)->with('success', 'Classe supprimée.');
    }
}
