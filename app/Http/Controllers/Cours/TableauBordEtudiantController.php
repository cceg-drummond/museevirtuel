<?php

namespace App\Http\Controllers\Cours;

use App\Http\Controllers\Controller;
use App\Models\Cours;
use App\Models\Groupe;
use App\Models\ProjetRecherche;
use App\Models\TypeProjet;
use App\Models\VisioConference;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tableau de bord de l'étudiant : ses cours, projets et visioconférences.
 */
class TableauBordEtudiantController extends Controller
{
    /**
     * Affiche la liste des cours dans lesquels l'étudiant authentifié est inscrit.
     * Les cours verrouillés par l'enseignant sont masqués.
     * Inclut les prochaines visioconférences, les références personnelles et la config Zotero.
     */
    public function __invoke(): Response
    {
        $user = auth()->user();

        $cours = $user->coursInscrits()
            ->where('is_verrouille', false)
            ->with('enseignant:id,prenom,nom')
            ->orderBy('nom_cours')
            ->get();

        $coursIds = $cours->pluck('id');
        $groupeIds = $user->groupesMembre()->pluck('groupes.id');

        // Classe (section) de l'étudiant pour chaque cours — permet de lier directement la page de classe
        $classesParCours = $user->classesInscrites()
            ->whereIn('classes.cours_id', $coursIds)
            ->get(['classes.id', 'classes.cours_id'])
            ->keyBy('cours_id');

        // Projets de l'étudiant via ses groupes, avec le TypeProjet et le chemin URL complet
        $projets = $user->groupesMembre()
            ->with([
                'classe:id,cours_id',
                'projets' => fn ($q) => $q->with('typeProjet:id,nom'),
            ])
            ->get()
            ->flatMap(function (Groupe $groupe): Collection {
                return $groupe->projets->map(fn (ProjetRecherche $projet): array => [
                    'id' => $projet->id,
                    'titre' => $projet->titre_projet,
                    'type_projet' => ['id' => $projet->typeProjet->id, 'nom' => $projet->typeProjet->nom],
                    'groupe_id' => $groupe->id,
                    'classe_id' => $groupe->classe_id,
                    'cours_id' => $groupe->classe->cours_id,
                ]);
            })
            ->values();

        // Visios des cours inscrits : ouvertes à tout le cours OU ciblées pour le groupe de l'étudiant
        $prochainesVisios = VisioConference::whereIn('cours_id', $coursIds)
            ->where(fn ($q) => $q->whereNull('groupe_id')->orWhereIn('groupe_id', $groupeIds))
            ->whereNull('ended_at')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', now())
            ->with(['cours:id,nom_cours', 'animateur:id,prenom,nom'])
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get()
            ->map(fn (VisioConference $v) => [
                'id' => $v->id,
                'titre' => $v->titre,
                'scheduled_at' => $v->scheduled_at->toIso8601String(),
                'started_at' => $v->started_at?->toIso8601String(),
                'jitsi_room' => $v->jitsi_room,
                'cours' => ['id' => $v->cours->id, 'nom_cours' => $v->cours->nom_cours],
                'animateur' => ['prenom' => $v->animateur->prenom, 'nom' => $v->animateur->nom],
            ]);

        // Références personnelles de l'étudiant
        $mesReferences = $user->etudiantReferences()
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'titre' => $r->titre,
                'auteurs' => $r->auteurs,
                'annee' => $r->annee,
                'type_source' => $r->type_source,
                'url' => $r->url,
                'doi' => $r->doi,
                'publication' => $r->publication,
                'ordre' => $r->ordre,
                'est_depuis_zotero' => $r->estDepuisZotero(),
            ]);

        // Configuration Zotero de l'étudiant (null si non configuré)
        $zoteroConfig = $user->zoteroCredential
            ? [
                'configure' => true,
                'synchronise_le' => $user->zoteroCredential->synchronise_le?->toIso8601String(),
            ]
            : ['configure' => false, 'synchronise_le' => null];

        return Inertia::render('Cours/Index', [
            'cours' => $cours->map(fn ($c) => [
                'id' => $c->id,
                'nom_cours' => $c->nom_cours,
                'description' => $c->description,
                'code' => $c->code,
                'groupe' => $c->groupe,
                'annee' => $c->annee,
                'session' => $c->session,
                'enseignant' => [
                    'id' => $c->enseignant->id,
                    'prenom' => $c->enseignant->prenom,
                    'nom' => $c->enseignant->nom,
                ],
                'classe_id' => $classesParCours->get($c->id)?->id,
            ]),
            'projets' => $projets,
            'prochainesVisios' => $prochainesVisios,
            'mesReferences' => $mesReferences,
            'zoteroConfig' => $zoteroConfig,
        ]);
    }
}
