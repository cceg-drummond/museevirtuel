<?php

use App\Enums\NiveauSection;
use App\Enums\TypeCours;
use App\Models\Cours;
use App\Models\GabaritCours;
use App\Models\GabaritTypeProjet;
use App\Models\GabaritTypeProjetSection;
use App\Models\TypeProjet;
use App\Models\TypeProjetSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Type de projet minimal pour y rattacher des sections.
 */
function typeProjetPourNiveaux(): TypeProjet
{
    $enseignant = User::factory()->create(['role' => 'enseignant']);
    $cours = Cours::create([
        'nom_cours' => 'Histoire',
        'code' => '330-NIV',
        'groupe' => 'A',
        'enseignant_id' => $enseignant->id,
    ]);

    return TypeProjet::create([
        'enseignant_id' => $enseignant->id,
        'cours_id' => $cours->id,
        'nom' => 'Projet hiérarchique',
    ]);
}

test('chaque niveau a son libellé français', function (NiveauSection $niveau, string $label) {
    expect($niveau->label())->toBe($label);
})->with([
    [NiveauSection::Chapitre, 'Chapitre'],
    [NiveauSection::Section, 'Section'],
    [NiveauSection::SousSection, 'Sous-section'],
]);

test('une section créée sans niveau est un chapitre', function () {
    $typeProjet = typeProjetPourNiveaux();

    $section = TypeProjetSection::create(['type_projet_id' => $typeProjet->id, 'label' => 'Intro', 'ordre' => 1]);

    expect($section->fresh()->niveau)->toBe(NiveauSection::Chapitre);
});

test('le niveau d\'une section est enregistré et relu comme enum', function () {
    $typeProjet = typeProjetPourNiveaux();

    $section = TypeProjetSection::create([
        'type_projet_id' => $typeProjet->id,
        'label' => 'Sous-partie',
        'ordre' => 3,
        'niveau' => NiveauSection::SousSection,
    ]);

    expect($section->fresh()->niveau)->toBe(NiveauSection::SousSection)
        ->and($section->fresh()->toArray()['niveau'])->toBe(2);
});

test('la création enregistre le niveau et l\'ordre de chaque élément du plan', function () {
    $typeProjet = typeProjetPourNiveaux();

    $this->actingAs($typeProjet->enseignant)
        ->post("/cours/{$typeProjet->cours_id}/types-projets", donneesTypeProjet([
            'sections' => [
                ['label' => 'Chapitre 1', 'niveau' => 0],
                ['label' => 'Section 1.1', 'niveau' => 1],
                ['label' => 'Sous-section 1.1.1', 'niveau' => 2],
                ['label' => 'Chapitre 2', 'niveau' => 0],
            ],
        ]))
        ->assertSessionHasNoErrors();

    // Le type de projet du scénario n'a aucune section : seules celles créées par la requête existent
    $plan = TypeProjetSection::orderBy('ordre')->get(['label', 'ordre', 'niveau']);

    expect($plan->map(fn ($s) => [$s->label, $s->ordre, $s->niveau])->all())->toBe([
        ['Chapitre 1', 1, NiveauSection::Chapitre],
        ['Section 1.1', 2, NiveauSection::Section],
        ['Sous-section 1.1.1', 3, NiveauSection::SousSection],
        ['Chapitre 2', 4, NiveauSection::Chapitre],
    ]);
});

test('une section sans niveau est créée comme chapitre', function () {
    $typeProjet = typeProjetPourNiveaux();

    $this->actingAs($typeProjet->enseignant)
        ->post("/cours/{$typeProjet->cours_id}/types-projets", donneesTypeProjet([
            'sections' => [['label' => 'Sans niveau']],
        ]))
        ->assertSessionHasNoErrors();

    expect(TypeProjetSection::where('label', 'Sans niveau')->first()->niveau)->toBe(NiveauSection::Chapitre);
});

test('un plan incohérent est refusé à la création', function (array $niveaux) {
    $typeProjet = typeProjetPourNiveaux();

    $this->actingAs($typeProjet->enseignant)
        ->post("/cours/{$typeProjet->cours_id}/types-projets", donneesTypeProjet([
            'sections' => array_map(fn (int $n) => ['label' => "Niveau {$n}", 'niveau' => $n], $niveaux),
        ]))
        ->assertSessionHasErrors('sections');
})->with([
    'commence par une section' => [[1, 2]],
    'commence par une sous-section' => [[2]],
    'saute de chapitre à sous-section' => [[0, 2]],
    'saute après un retour au chapitre' => [[0, 1, 2, 0, 2]],
]);

test('un niveau hors enum est refusé', function () {
    $typeProjet = typeProjetPourNiveaux();

    $this->actingAs($typeProjet->enseignant)
        ->post("/cours/{$typeProjet->cours_id}/types-projets", donneesTypeProjet([
            'sections' => [['label' => 'Chapitre', 'niveau' => 3]],
        ]))
        ->assertSessionHasErrors('sections.0.niveau');
});

test('la mise à jour synchronise le niveau des sections existantes', function () {
    $typeProjet = typeProjetPourNiveaux();
    $chapitre = TypeProjetSection::create(['type_projet_id' => $typeProjet->id, 'label' => 'Chapitre', 'ordre' => 1]);
    $section = TypeProjetSection::create(['type_projet_id' => $typeProjet->id, 'label' => 'Devient section', 'ordre' => 2]);

    $this->actingAs($typeProjet->enseignant)
        ->put("/cours/{$typeProjet->cours_id}/types-projets/{$typeProjet->id}", donneesTypeProjet([
            'sections' => [
                ['id' => $chapitre->id, 'label' => 'Chapitre', 'niveau' => 0],
                ['id' => $section->id, 'label' => 'Devient section', 'niveau' => 1],
            ],
        ]))
        ->assertSessionHasNoErrors();

    expect($chapitre->fresh()->niveau)->toBe(NiveauSection::Chapitre)
        ->and($section->fresh()->niveau)->toBe(NiveauSection::Section);
});

test('un plan incohérent est refusé à la mise à jour sans rien modifier', function () {
    $typeProjet = typeProjetPourNiveaux();
    $chapitre = TypeProjetSection::create(['type_projet_id' => $typeProjet->id, 'label' => 'Chapitre', 'ordre' => 1]);

    $this->actingAs($typeProjet->enseignant)
        ->put("/cours/{$typeProjet->cours_id}/types-projets/{$typeProjet->id}", donneesTypeProjet([
            'sections' => [['id' => $chapitre->id, 'label' => 'Chapitre', 'niveau' => 1]],
        ]))
        ->assertSessionHasErrors('sections');

    expect($chapitre->fresh()->niveau)->toBe(NiveauSection::Chapitre);
});

test('le niveau d\'une section de gabarit est enregistré et relu comme enum', function () {
    $gabarit = GabaritCours::create(['slug' => 'niveaux', 'type_cours' => TypeCours::Dep->value, 'nom' => 'Gabarit']);
    $gabaritTypeProjet = GabaritTypeProjet::create(['gabarit_cours_id' => $gabarit->id, 'nom' => 'Projet']);

    $section = GabaritTypeProjetSection::create([
        'gabarit_type_projet_id' => $gabaritTypeProjet->id,
        'label' => 'Section',
        'type' => 'texte',
        'ordre' => 2,
        'niveau' => NiveauSection::Section,
    ]);

    expect($section->fresh()->niveau)->toBe(NiveauSection::Section);
});
