<?php

namespace App\Http\Controllers\Cours;

use App\Enums\TypeCours;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCoursRequest;
use App\Http\Requests\UpdateCoursRequest;
use App\Models\Cours;
use App\Models\TypeProjet;
use App\Models\VisioConference;
use App\Services\AppliquerGabaritService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CoursController extends Controller
{
    /**
     * Affiche le formulaire de création d'un cours.
     */
    public function create(): Response
    {
        return Inertia::render('Enseignant/CoursEdition/Create');
    }

    /**
     * Affiche le formulaire de modification d'un cours appartenant à l'enseignant.
     */
    public function edit(Cours $cours): Response
    {
        abort_unless($cours->enseignant_id === auth()->id(), 403);

        return Inertia::render('Enseignant/CoursEdition/Edit', ['cours' => $cours]);
    }

    /**
     * Affiche le détail d'un cours avec ses étudiants, classes et documents.
     *
     * Accessible aux enseignants propriétaires et aux admins (CoursPolicy::view).
     */
    public function show(Cours $cours): Response
    {
        $this->authorize('view', $cours);

        $classes = $cours->classes()
            ->withCount(['etudiants', 'groupes'])
            ->orderBy('numero')
            ->get();

        $documents = $cours->documents()->get();

        $echeancierEtapes = $cours->echeancierEtapes()
            ->get()
            ->map(fn ($etape) => [
                'id' => $etape->id,
                'semaine' => $etape->semaine,
                'periode' => $etape->periode,
                'etape' => $etape->etape,
                'is_done' => $etape->is_done,
                'ordre' => $etape->ordre,
            ]);

        $objectifs = $cours->objectifs()
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'contenu' => $o->contenu,
                'ordre' => $o->ordre,
            ]);

        $references = $cours->references()
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'nom' => $r->nom,
                'url' => $r->url,
                'ordre' => $r->ordre,
            ]);

        $typesProjets = TypeProjet::where('cours_id', $cours->id)
            ->with(['sections'])
            ->orderBy('nom')
            ->get();

        $visioConferences = $cours->visioConferences()
            ->with('animateur:id,prenom,nom')
            ->get()
            ->map(fn (VisioConference $v) => [
                'id' => $v->id,
                'cours_id' => $v->cours_id,
                'groupe_id' => $v->groupe_id,
                'jitsi_room' => $v->jitsi_room,
                'titre' => $v->titre,
                'scheduled_at' => $v->scheduled_at?->toIso8601String(),
                'started_at' => $v->started_at?->toIso8601String(),
                'ended_at' => $v->ended_at?->toIso8601String(),
                'recording_url' => $v->recording_url,
                'animateur' => [
                    'id' => $v->animateur->id,
                    'prenom' => $v->animateur->prenom,
                    'nom' => $v->animateur->nom,
                ],
            ]);

        return Inertia::render('Cours/Show', [
            'cours' => $cours,
            'classes' => $classes,
            'documents' => $documents,
            'echeancierEtapes' => $echeancierEtapes,
            'objectifs' => $objectifs,
            'references' => $references,
            'typesProjets' => $typesProjets->map(fn (TypeProjet $tp) => [
                'id' => $tp->id,
                'nom' => $tp->nom,
                'description' => $tp->description,
                'accessible' => $tp->accessible,
                'sections' => $tp->sections->map(fn ($s) => [
                    'id' => $s->id,
                    'label' => $s->label,
                    'description' => $s->description,
                    'ordre' => $s->ordre,
                ])->values(),
            ])->values(),
            'visioConferences' => $visioConferences,
        ]);
    }

    /**
     * Enregistre un nouveau cours pour l'enseignant authentifié.
     *
     * Si le type est 'cours_complet' et que `utiliser_gabarit` est activé,
     * le gabarit standard est automatiquement appliqué au cours créé.
     *
     * La validation et l'autorisation sont déléguées à StoreCoursRequest.
     */
    public function store(StoreCoursRequest $request, AppliquerGabaritService $gabaritService): RedirectResponse
    {
        $validated = $request->validated();

        // On extrait le flag avant la création — il ne fait pas partie du modèle
        $utiliserGabarit = (bool) ($validated['utiliser_gabarit'] ?? false);
        unset($validated['utiliser_gabarit']);

        /** @var Cours $cours */
        $cours = auth()->user()->cours()->create($validated);

        if ($utiliserGabarit && $cours->type_cours === TypeCours::CoursComplet) {
            $gabaritService->appliquer($cours, 'cours_complet');
        }

        return to_route('enseignant.index')->with('success', __('cours.created'));
    }

    /**
     * Met à jour un cours existant.
     *
     * La validation et l'autorisation (CoursPolicy::update) sont déléguées à UpdateCoursRequest.
     */
    public function update(UpdateCoursRequest $request, Cours $cours): RedirectResponse
    {
        $cours->update($request->validated());

        return to_route('enseignant.index')->with('success', __('cours.updated'));
    }

    /**
     * Inverse le statut de verrouillage d'un cours.
     *
     * Un cours verrouillé n'est plus visible pour les étudiants.
     *
     * @throws AuthorizationException
     */
    public function toggleVerrouillage(Cours $cours): RedirectResponse
    {
        $this->authorize('update', $cours);

        $verrouille = ! $cours->is_verrouille;
        $cours->update(['is_verrouille' => $verrouille]);

        $message = $verrouille ? __('cours.verrouille') : __('cours.deverrouille');

        return to_route('enseignant.index')->with('success', $message);
    }

    /**
     * Supprime un cours et toutes ses données associées.
     *
     * @throws AuthorizationException
     */
    public function destroy(Cours $cours): RedirectResponse
    {
        $this->authorize('delete', $cours);

        $cours->delete();

        return to_route('enseignant.index')->with('success', __('cours.deleted'));
    }
}
