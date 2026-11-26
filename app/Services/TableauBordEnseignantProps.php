<?php

namespace App\Services;

use App\Enums\StatutProjetRecherche;
use App\Http\Resources\VisioConferenceResource;
use App\Models\Classe;
use App\Models\Groupe;
use App\Models\MuseePublication;
use App\Models\ProjetRecherche;
use App\Models\User;
use App\Models\VisioConference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Construit les props Inertia du tableau de bord de l'enseignant.
 */
class TableauBordEnseignantProps
{
    /**
     * Injecte les services qui listent les cours, les thématiques et les témoins de l'enseignant.
     */
    public function __construct(
        private readonly ListeCoursEnseignant $listeCoursEnseignant,
        private readonly ListeThematiquesEnseignant $listeThematiquesEnseignant,
        private readonly ListeTemoinsEnseignant $listeTemoinsEnseignant,
    ) {}

    /**
     * Retourne les props de la page Enseignant/Index pour l'enseignant donné.
     *
     * @return array<string, mixed>
     */
    public function pourEnseignant(User $user): array
    {
        $cours = $this->listeCoursEnseignant->pour($user);

        return [
            'cours' => $cours,
            'thematiques' => $this->listeThematiquesEnseignant->pour($user),
            'travauxRemis' => $this->travauxRemis($user),
            'temoinsEnAttente' => $this->listeTemoinsEnseignant->obtenirEnAttente($user),
            'temoinsApprouves' => $this->listeTemoinsEnseignant->obtenirApprouves($user),
            'prochainesVisios' => $this->prochainesVisios($user),
            'indicateurs' => $this->indicateurs($user, $cours),
            'statutProjets' => $this->statutProjets($user),
        ];
    }

    /**
     * Retourne les compteurs affichés dans les cartes du tableau de bord.
     *
     * @return array<string, int>
     */
    private function indicateurs(User $user, Collection $cours): array
    {
        $classeIds = Classe::whereIn('cours_id', $cours->pluck('id'))->pluck('id');
        $groupes = Groupe::whereIn('classe_id', $classeIds);
        $publications = MuseePublication::whereHas('projetRecherche.groupe.classe.cours',
            fn ($q) => $q->where('enseignant_id', $user->id));

        return [
            'cours' => $cours->count(),
            'coursVerrouilles' => $cours->where('is_verrouille', true)->count(),
            'classes' => $classeIds->count(),
            'etudiants' => DB::table('classe_etudiant')
                ->whereIn('classe_id', $classeIds)
                ->distinct()
                ->count('user_id'),
            'groupes' => (clone $groupes)->count(),
            'groupesAvecTemoin' => $groupes->whereNotNull('personne_agee_id')->count(),
            'temoinsApprouves' => $this->listeTemoinsEnseignant->queryApprouves($user)->count(),
            'temoinsEnAttente' => $this->listeTemoinsEnseignant->queryEnAttente($user)->count(),
            'visiosAVenir' => $this->queryVisiosAVenir($user)->count(),
            'visiosCetteSemaine' => $this->queryVisiosAVenir($user)
                ->where('scheduled_at', '<=', now()->endOfWeek())
                ->count(),
            'publicationsMusee' => (clone $publications)->where('est_publie', true)->count(),
            'publicationsAApprouver' => $publications
                ->where('statut', MuseePublication::STATUT_SOUMIS)
                ->count(),
        ];
    }

    /**
     * Retourne le nombre de projets de l'enseignant pour chacun des statuts possibles.
     *
     * @return array<int, array{valeur: string, libelle: string, total: int}>
     */
    private function statutProjets(User $user): array
    {
        $totaux = $this->queryProjets($user)
            ->with('typeProjet:id,date_remise')
            ->get(['id', 'type_projet_id', 'date_remise', 'remis_le'])
            ->countBy(fn (ProjetRecherche $projet) => $projet->statutActuel()->value);

        return array_map(fn (StatutProjetRecherche $statut) => [
            'valeur' => $statut->value,
            'libelle' => $statut->label(),
            'total' => $totaux->get($statut->value, 0),
        ], StatutProjetRecherche::cases());
    }

    /**
     * Retourne les 10 travaux les plus récemment remis parmi les groupes de l'enseignant.
     */
    private function travauxRemis(User $user): Collection
    {
        return $this->queryProjets($user)
            ->whereNotNull('remis_le')
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
                'classe_id' => $projet->groupe->classe_id,
                'membres' => $projet->groupe->membres
                    ->map->only('id', 'prenom', 'nom')
                    ->values(),
            ]);
    }

    /**
     * Retourne les 10 prochaines visioconférences planifiées dans les cours de l'enseignant.
     *
     * @return array<int, mixed>
     */
    private function prochainesVisios(User $user): array
    {
        return $this->queryVisiosAVenir($user)
            ->with(['cours:id,nom_cours,code,groupe', 'groupe', 'animateur:id,prenom,nom'])
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get()
            ->pipe(fn ($visios) => VisioConferenceResource::collection($visios)->resolve());
    }

    /**
     * Retourne la requête des projets appartenant aux groupes des cours de l'enseignant.
     */
    private function queryProjets(User $user): Builder
    {
        return ProjetRecherche::whereHas('groupe.classe.cours',
            fn ($q) => $q->where('enseignant_id', $user->id));
    }

    /**
     * Retourne la requête des visioconférences planifiées et non terminées
     * à venir dans les cours de l'enseignant.
     */
    private function queryVisiosAVenir(User $user): Builder
    {
        return VisioConference::whereHas('cours', fn ($q) => $q->where('enseignant_id', $user->id))
            ->whereNull('ended_at')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', now());
    }
}
