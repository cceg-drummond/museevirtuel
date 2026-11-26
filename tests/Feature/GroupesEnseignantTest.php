<?php

use App\Models\Classe;
use App\Models\Cours;
use App\Models\Groupe;
use App\Models\User;

test("la page Groupes liste les groupes de l'enseignant avec leur numéro, leurs membres et leur aîné", function () {
    $enseignant = User::factory()->create(['role' => 'enseignant']);
    $cours = Cours::create(['nom_cours' => 'Histoire', 'code' => '330-H', 'groupe' => '01', 'enseignant_id' => $enseignant->id]);
    $classe = Classe::create(['cours_id' => $cours->id]);
    $etudiants = User::factory()->count(2)->create(['role' => 'etudiant']);
    $temoin = User::factory()->create(['role' => 'personne_agee', 'statut' => 'actif']);

    $premier = Groupe::create(['classe_id' => $classe->id, 'created_by' => $etudiants[0]->id]);
    $premier->membres()->attach($etudiants->pluck('id'));
    $second = Groupe::create(['classe_id' => $classe->id, 'created_by' => $etudiants[1]->id, 'personne_agee_id' => $temoin->id]);

    // Le groupe d'un autre enseignant ne doit pas apparaître
    $autreCours = Cours::create(['nom_cours' => 'Autre', 'code' => '999', 'groupe' => '01', 'enseignant_id' => User::factory()->create(['role' => 'enseignant'])->id]);
    Groupe::create(['classe_id' => Classe::create(['cours_id' => $autreCours->id])->id, 'created_by' => $etudiants[0]->id]);

    $this->actingAs($enseignant)
        ->get(route('enseignant.groupes.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Enseignant/Groupes/Index')
            ->has('groupes', 2)
            ->where('groupes.0.id', $premier->id)
            ->where('groupes.0.numero', $premier->numero)
            ->where('groupes.0.membres_count', 2)
            ->where('groupes.0.temoin', null)
            ->where('groupes.1.numero', $second->numero)
            ->where('groupes.1.temoin.id', $temoin->id)
            ->where('groupes.1.cours.code', '330-H')
        );
});

test('la page Groupes renvoie les étudiants vers leur tableau de bord', function () {
    $this->actingAs(User::factory()->create(['role' => 'etudiant']))
        ->get(route('enseignant.groupes.index'))
        ->assertRedirect(route('dashboard'));
});
