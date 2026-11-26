<?php

use App\Models\Thematique;
use App\Models\User;

test("la page Aînés sépare les témoins en attente sur les thématiques de l'enseignant et ceux qu'il a approuvés", function () {
    $enseignant = User::factory()->create(['role' => 'enseignant']);
    $thematique = Thematique::factory()->create(['enseignant_id' => $enseignant->id, 'nom' => 'La pêche']);

    $enAttente = User::factory()->create(['role' => 'personne_agee', 'statut' => 'en_attente']);
    $enAttente->thematiquesChoisies()->attach($thematique->id);

    // En attente sur une thématique invisible pour l'enseignant : ne doit pas apparaître
    User::factory()->create(['role' => 'personne_agee', 'statut' => 'en_attente'])
        ->thematiquesChoisies()->attach(Thematique::factory()->create()->id);

    $approuve = User::factory()->create(['role' => 'personne_agee', 'statut' => 'actif', 'approuve_par_id' => $enseignant->id]);

    $this->actingAs($enseignant)
        ->get(route('enseignant.temoins.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Enseignant/Temoins/Index')
            ->has('temoinsEnAttente', 1)
            ->where('temoinsEnAttente.0.id', $enAttente->id)
            ->where('temoinsEnAttente.0.thematiques_choisies.0.nom', 'La pêche')
            ->has('temoinsApprouves', 1)
            ->where('temoinsApprouves.0.id', $approuve->id)
            ->has('thematiques', 1)
            ->where('thematiques.0', ['id' => $thematique->id, 'nom' => 'La pêche'])
        );
});

test('la page Aînés renvoie les étudiants vers leur tableau de bord', function () {
    $this->actingAs(User::factory()->create(['role' => 'etudiant']))
        ->get(route('enseignant.temoins.index'))
        ->assertRedirect(route('dashboard'));
});
