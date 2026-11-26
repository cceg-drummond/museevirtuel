<?php

use App\Models\Classe;
use App\Models\Cours;
use App\Models\Groupe;
use App\Models\User;

test("la page Classes liste les classes de tous les cours de l'enseignant, triées par cours", function () {
    $enseignant = User::factory()->create(['role' => 'enseignant']);
    $histoire = Cours::create(['nom_cours' => 'Histoire', 'code' => '330-H', 'groupe' => '01', 'enseignant_id' => $enseignant->id]);
    $arts = Cours::create(['nom_cours' => 'Arts', 'code' => '510-A', 'groupe' => '02', 'enseignant_id' => $enseignant->id]);

    $classeHistoire = Classe::create(['cours_id' => $histoire->id, 'jour_semaine' => 'Lundi']);
    $etudiants = User::factory()->count(2)->create(['role' => 'etudiant']);
    $classeHistoire->etudiants()->attach($etudiants->pluck('id'));
    Groupe::create(['classe_id' => $classeHistoire->id, 'created_by' => $etudiants[0]->id]);
    $classeArts = Classe::create(['cours_id' => $arts->id]);

    // La classe d'un autre enseignant ne doit pas apparaître
    Classe::create(['cours_id' => Cours::create(['nom_cours' => 'Autre', 'code' => '999', 'groupe' => '01', 'enseignant_id' => User::factory()->create(['role' => 'enseignant'])->id])->id]);

    $this->actingAs($enseignant)
        ->get(route('enseignant.classes.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Enseignant/Classes/Index')
            ->has('classes', 2)
            ->where('classes.0.id', $classeArts->id)
            ->where('classes.1.id', $classeHistoire->id)
            ->where('classes.1.cours.code', '330-H')
            ->where('classes.1.jour_semaine', 'Lundi')
            ->where('classes.1.etudiants_count', 2)
            ->where('classes.1.groupes_count', 1)
        );
});

test('la page Classes renvoie les étudiants vers leur tableau de bord', function () {
    $this->actingAs(User::factory()->create(['role' => 'etudiant']))
        ->get(route('enseignant.classes.index'))
        ->assertRedirect(route('dashboard'));
});
