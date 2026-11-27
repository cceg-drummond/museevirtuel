<?php

use App\Models\Classe;
use App\Models\Cours;
use App\Models\Groupe;
use App\Models\MuseePublication;
use App\Models\ProjetRecherche;
use App\Models\Thematique;
use App\Models\TypeProjet;
use App\Models\User;
use App\Models\VisioConference;

/**
 * Crée un enseignant avec un cours, une classe de deux étudiants et un groupe.
 *
 * @return array{enseignant: User, cours: Cours, classe: Classe, groupe: Groupe, etudiants: array<int, User>}
 */
function creerTableauBordEnseignant(): array
{
    $enseignant = User::factory()->create(['role' => 'enseignant']);
    $cours = Cours::create([
        'nom_cours' => 'Histoire',
        'code' => '330-TB',
        'groupe' => '01',
        'enseignant_id' => $enseignant->id,
    ]);

    $etudiants = User::factory()->count(2)->create(['role' => 'etudiant'])->all();
    $classe = Classe::create(['cours_id' => $cours->id]);
    $classe->etudiants()->attach(collect($etudiants)->pluck('id'));

    $groupe = Groupe::create(['classe_id' => $classe->id, 'created_by' => $etudiants[0]->id]);
    $groupe->membres()->attach($etudiants[0]->id);

    return compact('enseignant', 'cours', 'classe', 'groupe', 'etudiants');
}

test("le tableau de bord de l'enseignant expose travaux remis et visios, sans les listes de gestion", function () {
    ['enseignant' => $enseignant, 'cours' => $cours, 'classe' => $classe, 'groupe' => $groupe, 'etudiants' => $etudiants] = creerTableauBordEnseignant();

    // Un étudiant inscrit dans deux classes du même cours ne compte qu'une fois
    Classe::create(['cours_id' => $cours->id])->etudiants()->attach($etudiants[0]->id);

    $typeProjet = TypeProjet::create(['enseignant_id' => $enseignant->id, 'cours_id' => $cours->id, 'nom' => 'TP', 'accessible' => true]);
    $projet = ProjetRecherche::create([
        'groupe_id' => $groupe->id,
        'type_projet_id' => $typeProjet->id,
        'titre_projet' => 'Mon projet',
        'remis_le' => now()->subDay(),
    ]);
    ProjetRecherche::create(['groupe_id' => $groupe->id, 'type_projet_id' => TypeProjet::create(['enseignant_id' => $enseignant->id, 'cours_id' => $cours->id, 'nom' => 'Non remis'])->id]);

    VisioConference::create(['cours_id' => $cours->id, 'animateur_id' => $enseignant->id, 'jitsi_room' => 'salle-a', 'titre' => 'À venir', 'scheduled_at' => now()->addDay()]);
    VisioConference::create(['cours_id' => $cours->id, 'animateur_id' => $enseignant->id, 'jitsi_room' => 'salle-b', 'titre' => 'Passée', 'scheduled_at' => now()->subDay()]);

    $this->actingAs($enseignant)
        ->get('/enseignant')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Enseignant/Index')
            ->missing('cours')
            ->missing('thematiques')
            ->missing('temoinsApprouves')
            ->has('travauxRemis', 1)
            ->where('travauxRemis.0.id', $projet->id)
            ->where('travauxRemis.0.titre_projet', 'Mon projet')
            ->where('travauxRemis.0.remis_le', $projet->fresh()->remis_le->toIso8601String())
            ->where('travauxRemis.0.classe', ['id' => $groupe->id, 'numero' => $groupe->fresh()->numero, 'cours_id' => $cours->id])
            ->where('travauxRemis.0.classe_id', $classe->id)
            ->where('travauxRemis.0.membres', [$etudiants[0]->only('id', 'prenom', 'nom')])
            ->has('prochainesVisios', 1)
            ->where('prochainesVisios.0.titre', 'À venir')
        );
});

test("le tableau de bord compte les indicateurs de l'enseignant sans ceux des autres", function () {
    ['enseignant' => $enseignant, 'cours' => $cours, 'classe' => $classe, 'groupe' => $groupe, 'etudiants' => $etudiants] = creerTableauBordEnseignant();
    $cours->update(['is_verrouille' => true]);

    // Un étudiant inscrit dans deux classes ne compte qu'une fois
    Classe::create(['cours_id' => $cours->id])->etudiants()->attach($etudiants[0]->id);
    $temoin = User::factory()->create(['role' => 'personne_agee', 'statut' => 'actif', 'approuve_par_id' => $enseignant->id]);
    Groupe::create(['classe_id' => $classe->id, 'created_by' => $etudiants[1]->id, 'personne_agee_id' => $temoin->id]);

    $typeProjet = TypeProjet::create(['enseignant_id' => $enseignant->id, 'cours_id' => $cours->id, 'nom' => 'TP']);
    $publie = ProjetRecherche::create(['groupe_id' => $groupe->id, 'type_projet_id' => $typeProjet->id]);
    MuseePublication::create(['projet_recherche_id' => $publie->id, 'est_publie' => true, 'statut' => MuseePublication::STATUT_APPROUVE]);
    $soumis = ProjetRecherche::create(['groupe_id' => $groupe->id, 'type_projet_id' => TypeProjet::create(['enseignant_id' => $enseignant->id, 'cours_id' => $cours->id, 'nom' => 'TP2'])->id]);
    MuseePublication::create(['projet_recherche_id' => $soumis->id, 'est_publie' => false, 'statut' => MuseePublication::STATUT_SOUMIS]);

    VisioConference::create(['cours_id' => $cours->id, 'animateur_id' => $enseignant->id, 'jitsi_room' => 'a', 'titre' => 'Bientôt', 'scheduled_at' => now()->endOfWeek()->subMinute()]);
    VisioConference::create(['cours_id' => $cours->id, 'animateur_id' => $enseignant->id, 'jitsi_room' => 'b', 'titre' => 'Plus tard', 'scheduled_at' => now()->addWeeks(2)]);

    // Les données d'un autre enseignant ne doivent pas être comptées
    creerTableauBordEnseignant();

    $this->actingAs($enseignant)
        ->get('/enseignant')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('indicateurs', [
            'cours' => 1,
            'coursVerrouilles' => 1,
            'classes' => 2,
            'etudiants' => 2,
            'groupes' => 2,
            'groupesAvecTemoin' => 1,
            'temoinsApprouves' => 1,
            'temoinsEnAttente' => 0,
            'visiosAVenir' => 2,
            'visiosCetteSemaine' => 1,
            'publicationsMusee' => 1,
            'publicationsAApprouver' => 1,
            'thematiques' => 0,
        ]));
});

test("le tableau de bord d'un enseignant sans cours n'affiche que des zéros", function () {
    $enseignant = User::factory()->create(['role' => 'enseignant']);

    $this->actingAs($enseignant)
        ->get('/enseignant')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('indicateurs.cours', 0)
            ->where('indicateurs.etudiants', 0)
            ->where('indicateurs.groupes', 0)
            ->where('statutProjets.0.total', 0)
        );
});

test('le tableau de bord répartit les projets selon leur statut recalculé à partir des dates', function () {
    ['enseignant' => $enseignant, 'cours' => $cours, 'groupe' => $groupe] = creerTableauBordEnseignant();
    $creerProjet = fn (?string $dateRemise, ?string $remisLe) => ProjetRecherche::create([
        'groupe_id' => $groupe->id,
        'type_projet_id' => TypeProjet::create(['enseignant_id' => $enseignant->id, 'cours_id' => $cours->id, 'nom' => uniqid(), 'date_remise' => $dateRemise])->id,
        'remis_le' => $remisLe,
    ]);

    $creerProjet(now()->addWeek(), null);
    $creerProjet(now()->addWeek(), now()->subDay());
    $creerProjet(now()->subWeek(), now()->subDay());
    // Statut stocké « en_cours » périmé : la date limite est passée
    $creerProjet(now()->subDay(), null)->forceFill(['statut' => 'en_cours'])->saveQuietly();

    $this->actingAs($enseignant)
        ->get('/enseignant')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('statutProjets', [
            ['valeur' => 'en_cours', 'libelle' => 'En cours', 'total' => 1],
            ['valeur' => 'remis', 'libelle' => 'Remis', 'total' => 1],
            ['valeur' => 'en_retard', 'libelle' => 'En retard', 'total' => 1],
            ['valeur' => 'remis_en_retard', 'libelle' => 'Remis en retard', 'total' => 1],
        ]));
});

test("la page Aînés sépare les témoins en attente et ceux approuvés par l'enseignant", function () {
    ['enseignant' => $enseignant] = creerTableauBordEnseignant();
    $thematique = Thematique::factory()->create(['enseignant_id' => $enseignant->id, 'nom' => 'Ma thématique']);
    Thematique::factory()->create(['nom' => "Celle d'un autre"]);

    $enAttente = User::factory()->create(['role' => 'personne_agee', 'statut' => 'en_attente']);
    $enAttente->thematiquesChoisies()->attach($thematique->id);

    // Lié à une thématique invisible pour l'enseignant : ne doit pas apparaître
    User::factory()->create(['role' => 'personne_agee', 'statut' => 'en_attente'])
        ->thematiquesChoisies()->attach(Thematique::factory()->create()->id);

    $approuve = User::factory()->create(['role' => 'personne_agee', 'statut' => 'actif', 'approuve_par_id' => $enseignant->id]);
    User::factory()->create(['role' => 'personne_agee', 'statut' => 'actif', 'approuve_par_id' => User::factory()->create(['role' => 'enseignant'])->id]);

    // Le tableau de bord ne compte que les thématiques visibles par l'enseignant
    $this->actingAs($enseignant)
        ->get('/enseignant')
        ->assertInertia(fn ($page) => $page->where('indicateurs.thematiques', 1));

    $this->actingAs($enseignant)
        ->get(route('enseignant.temoins.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('temoinsEnAttente', 1)
            ->where('temoinsEnAttente.0.id', $enAttente->id)
            ->where('temoinsEnAttente.0.thematiques_choisies.0.nom', 'Ma thématique')
            ->has('temoinsApprouves', 1)
            ->where('temoinsApprouves.0.id', $approuve->id)
        );
});
