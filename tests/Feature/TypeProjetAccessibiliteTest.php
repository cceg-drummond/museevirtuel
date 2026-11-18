<?php

use App\Models\Classe;
use App\Models\Cours;
use App\Models\Groupe;
use App\Models\TypeProjet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

// ─── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Crée un cours à deux classes, chacune avec un étudiant dans un groupe, et un type de projet masqué.
 *
 * @return array{enseignant: User, cours: Cours, classeA: Classe, classeB: Classe, etudiantA: User, etudiantB: User, groupeA: Groupe, groupeB: Groupe, typeProjet: TypeProjet}
 */
function creerScenarioAccessibilite(): array
{
    $enseignant = User::factory()->create(['role' => 'enseignant']);
    $cours = Cours::create([
        'nom_cours' => 'Cours accès',
        'code' => '330-ACC',
        'groupe' => '01',
        'enseignant_id' => $enseignant->id,
    ]);

    $classeA = Classe::create(['cours_id' => $cours->id]);
    $classeB = Classe::create(['cours_id' => $cours->id]);

    $etudiantA = User::factory()->create(['role' => 'etudiant']);
    $etudiantB = User::factory()->create(['role' => 'etudiant']);
    $classeA->etudiants()->attach($etudiantA->id);
    $classeB->etudiants()->attach($etudiantB->id);

    $groupeA = Groupe::create(['classe_id' => $classeA->id, 'created_by' => $etudiantA->id]);
    $groupeA->membres()->attach($etudiantA->id);
    $groupeB = Groupe::create(['classe_id' => $classeB->id, 'created_by' => $etudiantB->id]);
    $groupeB->membres()->attach($etudiantB->id);

    $typeProjet = TypeProjet::create([
        'enseignant_id' => $enseignant->id,
        'cours_id' => $cours->id,
        'nom' => 'Projet ciblé',
        'accessible' => false,
    ]);

    return compact('enseignant', 'cours', 'classeA', 'classeB', 'etudiantA', 'etudiantB', 'groupeA', 'groupeB', 'typeProjet');
}

/**
 * Retourne l'URL de mise à jour de l'accès du type de projet du scénario.
 */
function urlAccessibilite(array $ctx): string
{
    return route('types-projets.accessibilite.update', [$ctx['cours'], $ctx['typeProjet']]);
}

// ─── Index enseignant ─────────────────────────────────────────────────────────

test("l'index fournit les classes du cours et celles ayant accès à chaque type", function () {
    $ctx = creerScenarioAccessibilite();
    $ctx['typeProjet']->classes()->attach($ctx['classeA']->id);

    $this->actingAs($ctx['enseignant'])
        ->get(route('types-projets.index', $ctx['cours']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('TypeProjet/Index')
            ->has('classes', 2)
            ->where('typesProjets.0.classes', [$ctx['classeA']->id])
        );
});

test('la page du cours fournit les classes ayant accès à chaque type', function () {
    $ctx = creerScenarioAccessibilite();
    $ctx['typeProjet']->classes()->attach($ctx['classeB']->id);

    $this->actingAs($ctx['enseignant'])
        ->get(route('cours.show', $ctx['cours']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cours/Show')
            ->where('typesProjets.0.accessible', false)
            ->where('typesProjets.0.classes', [$ctx['classeB']->id])
        );
});

// ─── Mise à jour de l'accès ───────────────────────────────────────────────────

test("l'enseignant rend un type de projet accessible à une classe précise", function () {
    $ctx = creerScenarioAccessibilite();

    $this->actingAs($ctx['enseignant'])
        ->put(urlAccessibilite($ctx), ['tous' => false, 'classes' => [$ctx['classeA']->id]])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $ctx['typeProjet']->refresh();

    expect($ctx['typeProjet']->accessible)->toBeFalse()
        ->and($ctx['typeProjet']->classes()->pluck('classes.id')->all())->toBe([$ctx['classeA']->id]);
});

test('« Tous » ouvre le type à toutes les classes et vide la sélection précise', function () {
    $ctx = creerScenarioAccessibilite();
    $ctx['typeProjet']->classes()->attach($ctx['classeA']->id);

    $this->actingAs($ctx['enseignant'])
        ->put(urlAccessibilite($ctx), ['tous' => true])
        ->assertSessionHasNoErrors();

    $ctx['typeProjet']->refresh();

    expect($ctx['typeProjet']->accessible)->toBeTrue()
        ->and($ctx['typeProjet']->classes()->exists())->toBeFalse();
});

test('« Tous » inclut une classe créée après coup', function () {
    $ctx = creerScenarioAccessibilite();
    $ctx['typeProjet']->update(['accessible' => true]);

    $nouvelleClasse = Classe::create(['cours_id' => $ctx['cours']->id]);

    expect($ctx['typeProjet']->isAccessiblePour($nouvelleClasse))->toBeTrue();
});

test('une sélection vide sans « Tous » est refusée', function () {
    $ctx = creerScenarioAccessibilite();

    $this->actingAs($ctx['enseignant'])
        ->put(urlAccessibilite($ctx), ['tous' => false, 'classes' => []])
        ->assertSessionHasErrors('classes');
});

test("une classe d'un autre cours est refusée", function () {
    $ctx = creerScenarioAccessibilite();
    $autreCours = Cours::create([
        'nom_cours' => 'Autre',
        'code' => '330-AUT',
        'groupe' => '02',
        'enseignant_id' => $ctx['enseignant']->id,
    ]);
    $classeEtrangere = Classe::create(['cours_id' => $autreCours->id]);

    $this->actingAs($ctx['enseignant'])
        ->put(urlAccessibilite($ctx), ['tous' => false, 'classes' => [$classeEtrangere->id]])
        ->assertSessionHasErrors('classes.0');

    expect($ctx['typeProjet']->classes()->exists())->toBeFalse();
});

test("un autre enseignant ne peut pas changer l'accès (IDOR)", function () {
    $ctx = creerScenarioAccessibilite();
    $autre = User::factory()->create(['role' => 'enseignant']);

    $this->actingAs($autre)
        ->put(urlAccessibilite($ctx), ['tous' => true])
        ->assertForbidden();

    expect($ctx['typeProjet']->refresh()->accessible)->toBeFalse();
});

test('masquer un type ouvert à certaines classes le retire de toutes', function () {
    $ctx = creerScenarioAccessibilite();
    $ctx['typeProjet']->classes()->attach($ctx['classeA']->id);

    $this->actingAs($ctx['enseignant'])
        ->patch(route('types-projets.toggle-accessible', [$ctx['cours'], $ctx['typeProjet']]));

    $ctx['typeProjet']->refresh();

    expect($ctx['typeProjet']->accessible)->toBeFalse()
        ->and($ctx['typeProjet']->classes()->exists())->toBeFalse();
});

// ─── Côté étudiant ────────────────────────────────────────────────────────────

test('seuls les étudiants des classes choisies voient le type de projet', function () {
    $ctx = creerScenarioAccessibilite();
    $ctx['typeProjet']->classes()->attach($ctx['classeA']->id);

    $this->actingAs($ctx['etudiantA'])
        ->get(route('projets.index', [$ctx['cours'], $ctx['classeA'], $ctx['groupeA']]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('projets', 1)
            ->where('projets.0.typeProjet.accessible', true)
        );

    $this->actingAs($ctx['etudiantB'])
        ->get(route('projets.index', [$ctx['cours'], $ctx['classeB'], $ctx['groupeB']]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('projets', 0));
});

test("un étudiant d'une classe non choisie ne peut pas ouvrir le projet", function () {
    $ctx = creerScenarioAccessibilite();
    $ctx['typeProjet']->classes()->attach($ctx['classeA']->id);

    $this->actingAs($ctx['etudiantB'])
        ->get(route('projets.edit', [$ctx['cours'], $ctx['classeB'], $ctx['groupeB'], $ctx['typeProjet']]))
        ->assertForbidden();
});
