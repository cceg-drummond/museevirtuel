<?php

use App\Models\Thematique;
use App\Models\User;

test("la page Thématiques liste les thématiques visibles par l'enseignant avec leur auteur", function () {
    $enseignant = User::factory()->create(['role' => 'enseignant']);
    $thematique = Thematique::factory()->create(['enseignant_id' => $enseignant->id, 'nom' => 'La pêche']);

    // Thématique d'un autre enseignant sans établissement commun : invisible
    Thematique::factory()->create(['nom' => "Celle d'un autre"]);

    $this->actingAs($enseignant)
        ->get(route('enseignant.thematiques.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Enseignant/Thematiques/Index')
            ->has('thematiques', 1)
            ->where('thematiques.0.id', $thematique->id)
            ->where('thematiques.0.enseignant.id', $enseignant->id)
        );
});

test('la page Thématiques renvoie les étudiants vers leur tableau de bord', function () {
    $this->actingAs(User::factory()->create(['role' => 'etudiant']))
        ->get(route('enseignant.thematiques.index'))
        ->assertRedirect(route('dashboard'));
});
