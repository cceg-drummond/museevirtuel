<?php

namespace App\Http\Controllers;

use App\Http\Resources\VisioConferenceResource;
use App\Models\Cours;
use App\Models\ProjetRecherche;
use App\Models\Thematique;
use App\Models\User;
use App\Models\VisioConference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class EnseignantController extends Controller
{
    /**
     * Affiche le tableau de bord de l'enseignant avec ses classes, thématiques,
     * les travaux récemment remis et les témoins en attente liés à ses thématiques.
     */
    public function index(): Response
    {
        $user = auth()->user();

        $cours = $user->cours()
            ->withCount('classes')
            ->with('classes.etudiants:id')
            ->orderBy('nom_cours')
            ->get();

        $cours->each(function (Cours $cours): void {
            $etudiantsIds = $cours->classes
                ->flatMap(fn ($classe) => $classe->etudiants->pluck('id'))
                ->unique();

            $cours->setAttribute('etudiants_count', $etudiantsIds->count());
            $cours->unsetRelation('classes');
        });

        $thematiques = Thematique::visiblesPar($user)
            ->with('enseignant:id,prenom,nom')
            ->orderBy('nom')
            ->get();

        // Les 10 travaux les plus récemment remis parmi les groupes de ce prof
        $travauxRemis = ProjetRecherche::whereNotNull('remis_le')
            ->whereHas('groupe.classe.cours', fn ($q) => $q->where('enseignant_id', $user->id))
            ->with(['groupe' => fn ($q) => $q->with(['membres', 'classe.cours'])])
            ->orderByDesc('remis_le')
            ->limit(10)
            ->get()
            ->map(fn (ProjetRecherche $projet) => [
                'id' => $projet->id,
                'titre_projet' => $projet->titre_projet,
                'remis_le' => $projet->remis_le->toIso8601String(),
                'classe' => [
                    'id' => $projet->groupe->id,
                    'numero' => $projet->groupe->numero,
                    'cours_id' => $projet->groupe->classe->cours_id,
                ],
                'membres' => $projet->groupe->membres
                    ->map->only('id', 'prenom', 'nom')
                    ->values(),
            ]);

        // Témoins en attente liés à au moins une thématique du cégep (ou de l'enseignant si sans cégep)
        $thematiqueIds = Thematique::visiblesPar($user)->pluck('id');

        $temoinsEnAttente = $this->queryTemoinsParThematiques($thematiqueIds)
            ->enAttente()
            ->get(['id', 'prenom', 'nom', 'email', 'description', 'created_at']);

        // Témoins approuvés par cet enseignant spécifiquement
        $temoinsApprouves = User::where('role', 'personne_agee')
            ->where('approuve_par_id', $user->id)
            ->actif()
            ->with('thematiquesChoisies:id,nom')
            ->orderBy('created_at')
            ->get(['id', 'prenom', 'nom', 'email', 'description', 'created_at']);

        // Prochaines visioconférences planifiées dans les cours de cet enseignant
        $prochainesVisios = VisioConference::whereHas('cours', fn ($q) => $q->where('enseignant_id', $user->id))
            ->whereNull('ended_at')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', now())
            ->with(['cours:id,nom_cours,code,groupe', 'groupe', 'animateur:id,prenom,nom'])
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get()
            ->pipe(fn ($visios) => VisioConferenceResource::collection($visios)->resolve());

        return Inertia::render('Enseignant/Index', [
            'cours' => $cours,
            'thematiques' => $thematiques,
            'travauxRemis' => $travauxRemis,
            'temoinsEnAttente' => $temoinsEnAttente,
            'temoinsApprouves' => $temoinsApprouves,
            'prochainesVisios' => $prochainesVisios,
        ]);
    }

    /**
     * Retourne une base de requête pour les témoins liés aux thématiques données.
     */
    private function queryTemoinsParThematiques(Collection $thematiqueIds): Builder
    {
        return User::where('role', 'personne_agee')
            ->whereHas('thematiquesChoisies', fn ($q) => $q->whereIn('thematiques.id', $thematiqueIds))
            ->with('thematiquesChoisies:id,nom')
            ->orderBy('created_at');
    }
}
