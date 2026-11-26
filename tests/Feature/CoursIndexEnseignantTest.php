<?php

use App\Models\Classe;
use App\Models\Cours;
use App\Models\Groupe;
use App\Models\TypeProjet;
use App\Models\User;

test("la page Cours liste les cours de l'enseignant avec leurs compteurs", function () {
    $enseignant = User::factory()->create(['role' => 'enseignant']);
    $cours = Cours::create(['nom_cours' => 'Histoire', 'code' => '330-A', 'groupe' => '01', 'enseignant_id' => $enseignant->id]);

    $etudiants = User::factory()->count(2)->create(['role' => 'etudiant']);
    $classeA = Classe::create(['cours_id' => $cours->id]);
    $classeA->etudiants()->attach($etudiants->pluck('id'));
    // Le même étudiant dans deux classes ne compte qu'une fois
    Classe::create(['cours_id' => $cours->id])->etudiants()->attach($etudiants[0]->id);
    Groupe::create(['classe_id' => $classeA->id, 'created_by' => $etudiants[0]->id]);
    Groupe::create(['classe_id' => $classeA->id, 'created_by' => $etudiants[1]->id]);
    TypeProjet::create(['enseignant_id' => $enseignant->id, 'cours_id' => $cours->id, 'nom' => 'TP']);

    // Le cours d'un autre enseignant ne doit pas apparaître
    Cours::create(['nom_cours' => 'Autre', 'code' => '330-B', 'groupe' => '01', 'enseignant_id' => User::factory()->create(['role' => 'enseignant'])->id]);

    $this->actingAs($enseignant)
        ->get(route('enseignant.cours.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Enseignant/Cours/Index')
            ->has('cours', 1)
            ->where('cours.0.id', $cours->id)
            ->where('cours.0.classes_count', 2)
            ->where('cours.0.groupes_count', 2)
            ->where('cours.0.etudiants_count', 2)
            ->where('cours.0.types_projets_count', 1)
            ->missing('cours.0.classes')
        );
});

test('la page Cours renvoie les étudiants vers leur tableau de bord', function () {
    $this->actingAs(User::factory()->create(['role' => 'etudiant']))
        ->get(route('enseignant.cours.index'))
        ->assertRedirect(route('dashboard'));
});
