<?php

use App\Models\Classe;
use App\Models\Cours;
use App\Models\Groupe;
use App\Models\ProjetRecherche;
use App\Models\TypeProjet;
use App\Models\User;

test("la page Projets liste les projets de l'enseignant avec leur statut recalculé et leur groupe", function () {
    $enseignant = User::factory()->create(['role' => 'enseignant']);
    $cours = Cours::create(['nom_cours' => 'Histoire', 'code' => '330-H', 'groupe' => '01', 'enseignant_id' => $enseignant->id]);
    $classe = Classe::create(['cours_id' => $cours->id]);
    $etudiant = User::factory()->create(['role' => 'etudiant']);

    // Un premier groupe sans projet : le groupe du projet doit quand même être numéroté 2
    Groupe::create(['classe_id' => $classe->id, 'created_by' => $etudiant->id]);
    $groupe = Groupe::create(['classe_id' => $classe->id, 'created_by' => $etudiant->id]);

    $typeProjet = TypeProjet::create(['enseignant_id' => $enseignant->id, 'cours_id' => $cours->id, 'nom' => 'Récit', 'date_remise' => now()->subDay()]);
    $projet = ProjetRecherche::create(['groupe_id' => $groupe->id, 'type_projet_id' => $typeProjet->id, 'titre_projet' => 'Les filatures']);
    // Statut stocké périmé : la date limite est passée sans remise
    $projet->forceFill(['statut' => 'en_cours'])->saveQuietly();

    // Le projet d'un autre enseignant ne doit pas apparaître
    $autreCours = Cours::create(['nom_cours' => 'Autre', 'code' => '999', 'groupe' => '01', 'enseignant_id' => User::factory()->create(['role' => 'enseignant'])->id]);
    $autreGroupe = Groupe::create(['classe_id' => Classe::create(['cours_id' => $autreCours->id])->id, 'created_by' => $etudiant->id]);
    ProjetRecherche::create(['groupe_id' => $autreGroupe->id, 'type_projet_id' => TypeProjet::create(['enseignant_id' => $autreCours->enseignant_id, 'cours_id' => $autreCours->id, 'nom' => 'X'])->id]);

    $this->actingAs($enseignant)
        ->get(route('enseignant.projets.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Enseignant/Projets/Index')
            ->has('projets', 1)
            ->where('projets.0.id', $projet->id)
            ->where('projets.0.titre_projet', 'Les filatures')
            ->where('projets.0.statut', 'en_retard')
            ->where('projets.0.type_projet.nom', 'Récit')
            ->where('projets.0.groupe.numero', 2)
            ->where('projets.0.groupe.numero', $groupe->numero)
            ->where('projets.0.cours.code', '330-H')
        );
});

test('la page Projets renvoie les étudiants vers leur tableau de bord', function () {
    $this->actingAs(User::factory()->create(['role' => 'etudiant']))
        ->get(route('enseignant.projets.index'))
        ->assertRedirect(route('dashboard'));
});
