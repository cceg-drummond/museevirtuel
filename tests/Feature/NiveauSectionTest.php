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

/**
 * Crée un plan dont chaque élément reçoit le niveau donné, dans l'ordre.
 *
 * @param  list<int>  $niveaux
 * @return list<TypeProjetSection>
 */
function planAvecNiveaux(TypeProjet $typeProjet, array $niveaux): array
{
    return array_map(fn (int $niveau, int $index) => TypeProjetSection::create([
        'type_projet_id' => $typeProjet->id,
        'label' => 'Élément '.($index + 1),
        'ordre' => $index + 1,
        'niveau' => $niveau,
    ]), $niveaux, array_keys($niveaux));
}

/**
 * URL des routes de section du type de projet.
 */
function urlSections(TypeProjet $typeProjet, string $suffixe = ''): string
{
    return "/cours/{$typeProjet->cours_id}/types-projets/{$typeProjet->id}/sections{$suffixe}";
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

test('l\'ajout d\'une section l\'enregistre en fin de plan avec son niveau', function () {
    $typeProjet = typeProjetPourNiveaux();
    planAvecNiveaux($typeProjet, [0, 1]);

    $this->actingAs($typeProjet->enseignant)
        ->post(urlSections($typeProjet), ['label' => 'Sous-section', 'niveau' => 2])
        ->assertSessionHasNoErrors();

    $ajoutee = TypeProjetSection::where('label', 'Sous-section')->first();

    expect($ajoutee->ordre)->toBe(3)
        ->and($ajoutee->niveau)->toBe(NiveauSection::SousSection);
});

test('l\'ajout d\'une section sans niveau crée un chapitre', function () {
    $typeProjet = typeProjetPourNiveaux();
    planAvecNiveaux($typeProjet, [0, 1, 2]);

    $this->actingAs($typeProjet->enseignant)
        ->post(urlSections($typeProjet), ['label' => 'Nouveau chapitre'])
        ->assertSessionHasNoErrors();

    expect(TypeProjetSection::where('label', 'Nouveau chapitre')->first()->niveau)->toBe(NiveauSection::Chapitre);
});

test('l\'ajout d\'une section qui casserait le plan est refusé', function (array $plan, int $niveau) {
    $typeProjet = typeProjetPourNiveaux();
    planAvecNiveaux($typeProjet, $plan);

    $this->actingAs($typeProjet->enseignant)
        ->post(urlSections($typeProjet), ['label' => 'Refusée', 'niveau' => $niveau])
        ->assertSessionHasErrors('niveau');

    expect(TypeProjetSection::where('label', 'Refusée')->exists())->toBeFalse();
})->with([
    'section dans un plan vide' => [[], 1],
    'sous-section sous un chapitre' => [[0], 2],
]);

test('la modification d\'une section ignore le niveau', function () {
    $typeProjet = typeProjetPourNiveaux();
    [, $section] = planAvecNiveaux($typeProjet, [0, 1]);

    $this->actingAs($typeProjet->enseignant)
        ->put(urlSections($typeProjet, "/{$section->id}"), ['label' => 'Renommée', 'niveau' => 0])
        ->assertSessionHasNoErrors();

    expect($section->fresh()->label)->toBe('Renommée')
        ->and($section->fresh()->niveau)->toBe(NiveauSection::Section);
});

test('supprimer un chapitre supprime ses descendants mais pas le chapitre suivant', function () {
    $typeProjet = typeProjetPourNiveaux();
    [$premier, $section, $sousSection, $suivant, $sectionSuivante] = planAvecNiveaux($typeProjet, [0, 1, 2, 0, 1]);

    $this->actingAs($typeProjet->enseignant)
        ->delete(urlSections($typeProjet, "/{$premier->id}"))
        ->assertSessionHasNoErrors();

    expect(TypeProjetSection::whereKey([$premier->id, $section->id, $sousSection->id])->exists())->toBeFalse()
        ->and($suivant->fresh()->ordre)->toBe(1)
        ->and($sectionSuivante->fresh()->ordre)->toBe(2);
});

test('supprimer une section garde ses sœurs et le reste du chapitre', function () {
    $typeProjet = typeProjetPourNiveaux();
    [$chapitre, $section, $sousSection, $soeur] = planAvecNiveaux($typeProjet, [0, 1, 2, 1]);

    $this->actingAs($typeProjet->enseignant)
        ->delete(urlSections($typeProjet, "/{$section->id}"))
        ->assertSessionHasNoErrors();

    expect(TypeProjetSection::whereKey([$section->id, $sousSection->id])->exists())->toBeFalse()
        ->and($chapitre->fresh()->ordre)->toBe(1)
        ->and($soeur->fresh()->ordre)->toBe(2);
});

test('un réordonnancement qui garde un plan cohérent est appliqué', function () {
    $typeProjet = typeProjetPourNiveaux();
    [$chapitre1, $section1, $chapitre2] = planAvecNiveaux($typeProjet, [0, 1, 0]);

    $this->actingAs($typeProjet->enseignant)
        ->put(urlSections($typeProjet, '/reorder'), ['ordre' => [$chapitre2->id, $chapitre1->id, $section1->id]])
        ->assertSessionHasNoErrors();

    expect($chapitre2->fresh()->ordre)->toBe(1)
        ->and($chapitre1->fresh()->ordre)->toBe(2)
        ->and($section1->fresh()->ordre)->toBe(3);
});

test('un réordonnancement qui casserait le plan est refusé sans rien modifier', function () {
    $typeProjet = typeProjetPourNiveaux();
    [$chapitre, $section] = planAvecNiveaux($typeProjet, [0, 1]);

    $this->actingAs($typeProjet->enseignant)
        ->put(urlSections($typeProjet, '/reorder'), ['ordre' => [$section->id, $chapitre->id]])
        ->assertSessionHasErrors('ordre');

    expect($chapitre->fresh()->ordre)->toBe(1)
        ->and($section->fresh()->ordre)->toBe(2);
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
