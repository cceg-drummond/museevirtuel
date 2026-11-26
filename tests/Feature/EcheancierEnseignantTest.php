<?php

use App\Models\Classe;
use App\Models\Cours;
use App\Models\EcheancierEtape;
use App\Models\EcheancierEtudiantProgress;
use App\Models\User;

test("la page Échéancier affiche une carte par cours de l'enseignant avec ses étapes terminées", function () {
    $enseignant = User::factory()->create(['role' => 'enseignant']);
    $cours = Cours::create(['nom_cours' => 'Histoire', 'code' => '330-H', 'groupe' => '01', 'enseignant_id' => $enseignant->id]);
    EcheancierEtape::create(['cours_id' => $cours->id, 'semaine' => 1, 'etape' => 'Recherche', 'is_done' => true, 'ordre' => 0]);
    EcheancierEtape::create(['cours_id' => $cours->id, 'semaine' => 2, 'etape' => 'Entrevue', 'is_done' => false, 'ordre' => 0]);

    // Cours d'un autre enseignant : absent de la page
    Cours::create(['nom_cours' => 'Autre', 'code' => '330-Z', 'groupe' => '01', 'enseignant_id' => User::factory()->create(['role' => 'enseignant'])->id]);

    $this->actingAs($enseignant)
        ->get(route('enseignant.echeancier.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Enseignant/Echeancier/Index')
            ->has('cours', 1)
            ->where('cours.0.id', $cours->id)
            ->where('cours.0.etapes_count', 2)
            ->where('cours.0.etapes_terminees_count', 1)
        );
});

test("l'échéancier d'un cours liste ses étapes avec l'avancement des étudiants", function () {
    $enseignant = User::factory()->create(['role' => 'enseignant']);
    $cours = Cours::create(['nom_cours' => 'Histoire', 'code' => '330-H', 'groupe' => '01', 'enseignant_id' => $enseignant->id]);
    $classe = Classe::create(['cours_id' => $cours->id]);
    [$fini, $enCours] = User::factory()->count(2)->create(['role' => 'etudiant']);
    $classe->etudiants()->attach([$fini->id, $enCours->id]);

    $etape = EcheancierEtape::create(['cours_id' => $cours->id, 'semaine' => 2, 'etape' => 'Entrevue', 'is_done' => true, 'ordre' => 0]);
    EcheancierEtudiantProgress::create(['echeancier_etape_id' => $etape->id, 'user_id' => $fini->id, 'is_done' => true]);
    EcheancierEtudiantProgress::create(['echeancier_etape_id' => $etape->id, 'user_id' => $enCours->id, 'is_done' => false]);

    $this->actingAs($enseignant)
        ->get(route('enseignant.echeancier.show', $cours))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Enseignant/Echeancier/Show')
            ->where('echeancier.cours.id', $cours->id)
            ->where('echeancier.etudiants_count', 2)
            ->has('echeancier.etapes', 1)
            ->where('echeancier.etapes.0.etape', 'Entrevue')
            ->where('echeancier.etapes.0.is_done', true)
            ->where('echeancier.etapes.0.etudiants_termines', 1)
        );
});

test("l'échéancier d'un cours est refusé à un autre enseignant", function () {
    $cours = Cours::create(['nom_cours' => 'Histoire', 'code' => '330-H', 'groupe' => '01', 'enseignant_id' => User::factory()->create(['role' => 'enseignant'])->id]);

    $this->actingAs(User::factory()->create(['role' => 'enseignant']))
        ->get(route('enseignant.echeancier.show', $cours))
        ->assertForbidden();
});

test('la page Échéancier renvoie les étudiants vers leur tableau de bord', function () {
    $this->actingAs(User::factory()->create(['role' => 'etudiant']))
        ->get(route('enseignant.echeancier.index'))
        ->assertRedirect(route('dashboard'));
});
