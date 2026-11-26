<?php

use App\Models\Cours;
use App\Models\QuestionBanque;
use App\Models\TypeProjet;
use App\Models\TypeProjetSection;
use App\Models\User;

/**
 * Crée un type de projet avec une section du type donné pour un nouveau cours de l'enseignant.
 */
function creerSectionEnseignant(User $enseignant, string $type = 'choix_questions'): TypeProjetSection
{
    $cours = Cours::create(['nom_cours' => 'Histoire', 'code' => '330-H', 'groupe' => '01', 'enseignant_id' => $enseignant->id]);
    $typeProjet = TypeProjet::create(['enseignant_id' => $enseignant->id, 'cours_id' => $cours->id, 'nom' => 'Entrevue']);

    return TypeProjetSection::create(['type_projet_id' => $typeProjet->id, 'label' => 'Choix des questions', 'type' => $type, 'ordre' => 1]);
}

test("la page Banque de questions affiche une carte par section « choix de questions » de l'enseignant", function () {
    $enseignant = User::factory()->create(['role' => 'enseignant']);
    $section = creerSectionEnseignant($enseignant);
    QuestionBanque::create(['section_id' => $section->id, 'contenu' => 'Où avez-vous grandi ?', 'ordre' => 1]);

    // Section d'un autre type et banque d'un autre enseignant : absentes de la page
    TypeProjetSection::create(['type_projet_id' => $section->type_projet_id, 'label' => 'Intro', 'type' => 'texte', 'ordre' => 2]);
    creerSectionEnseignant(User::factory()->create(['role' => 'enseignant']));

    $this->actingAs($enseignant)
        ->get(route('enseignant.questions.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Enseignant/Questions/Index')
            ->has('banques', 1)
            ->where('banques.0.id', $section->id)
            ->where('banques.0.type_projet.nom', 'Entrevue')
            ->where('banques.0.cours.code', '330-H')
            ->where('banques.0.questions_count', 1)
        );
});

test("une banque de questions liste ses questions dans l'ordre", function () {
    $enseignant = User::factory()->create(['role' => 'enseignant']);
    $section = creerSectionEnseignant($enseignant);
    QuestionBanque::create(['section_id' => $section->id, 'contenu' => 'Deuxième', 'ordre' => 2]);
    QuestionBanque::create(['section_id' => $section->id, 'contenu' => 'Première', 'ordre' => 1]);

    $this->actingAs($enseignant)
        ->get(route('enseignant.questions.show', $section))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Enseignant/Questions/Show')
            ->where('banque.label', 'Choix des questions')
            ->where('banque.questions.0.contenu', 'Première')
            ->where('banque.questions.1.contenu', 'Deuxième')
        );
});

test('une banque de questions est refusée à un autre enseignant', function () {
    $section = creerSectionEnseignant(User::factory()->create(['role' => 'enseignant']));

    $this->actingAs(User::factory()->create(['role' => 'enseignant']))
        ->get(route('enseignant.questions.show', $section))
        ->assertForbidden();
});

test("une section qui n'est pas « choix de questions » n'a pas de banque", function () {
    $enseignant = User::factory()->create(['role' => 'enseignant']);

    $this->actingAs($enseignant)
        ->get(route('enseignant.questions.show', creerSectionEnseignant($enseignant, 'texte')))
        ->assertNotFound();
});

test('la page Banque de questions renvoie les étudiants vers leur tableau de bord', function () {
    $this->actingAs(User::factory()->create(['role' => 'etudiant']))
        ->get(route('enseignant.questions.index'))
        ->assertRedirect(route('dashboard'));
});
