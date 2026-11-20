<?php

use App\Actions\ExportProjetPdf;
use App\Actions\ExportProjetWord;
use App\Actions\TransfererCoursAction;
use App\Enums\NiveauSection;
use App\Enums\TypeCours;
use App\Helpers\PlanHelper;
use App\Models\Classe;
use App\Models\Cours;
use App\Models\GabaritCours;
use App\Models\GabaritTypeProjet;
use App\Models\GabaritTypeProjetSection;
use App\Models\Groupe;
use App\Models\ProjetRecherche;
use App\Models\TypeProjet;
use App\Models\TypeProjetSection;
use App\Models\User;
use App\Services\AppliquerGabaritService;
use App\Services\ProjetRechercheProps;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;

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

test('les sections envoyées à l\'éditeur du projet exposent leur niveau', function () {
    $typeProjet = typeProjetPourNiveaux();
    planAvecNiveaux($typeProjet, [0, 1, 2]);
    $classe = Classe::create(['cours_id' => $typeProjet->cours_id]);
    $groupe = Groupe::create(['classe_id' => $classe->id, 'created_by' => $typeProjet->enseignant_id]);
    $projet = ProjetRecherche::create(['groupe_id' => $groupe->id, 'type_projet_id' => $typeProjet->id]);

    $sections = app(ProjetRechercheProps::class)->sections($projet->load('typeProjet.sections'));

    expect(array_column($sections, 'niveau'))->toBe([0, 1, 2]);
});

// ─── Numérotation et exports ──────────────────────────────────────────────────

/**
 * Projet d'un groupe d'un membre dont le type a le plan Chapitre 1 > Méthode > Terrain, puis Chapitre 2.
 *
 * @return array{projet: ProjetRecherche, groupe: Groupe}
 */
function projetExportNiveaux(): array
{
    $typeProjet = typeProjetPourNiveaux();
    $typeProjet->update(['generer_table_matieres' => true]);

    foreach ([['Chapitre un', 0], ['Méthode', 1], ['Terrain', 2], ['Chapitre deux', 0]] as $index => [$label, $niveau]) {
        TypeProjetSection::create([
            'type_projet_id' => $typeProjet->id, 'label' => $label, 'ordre' => $index + 1, 'niveau' => $niveau, 'type' => 'texte',
        ]);
    }

    $etudiant = User::factory()->create(['role' => 'etudiant']);
    $classe = Classe::create(['cours_id' => $typeProjet->cours_id]);
    $groupe = Groupe::create(['classe_id' => $classe->id, 'created_by' => $etudiant->id]);
    $groupe->membres()->attach($etudiant->id);
    $projet = ProjetRecherche::create(['groupe_id' => $groupe->id, 'type_projet_id' => $typeProjet->id]);

    return ['projet' => $projet, 'groupe' => $groupe->fresh()];
}

test('la numérotation du plan suit les niveaux', function (array $niveaux, array $numeros) {
    expect(PlanHelper::numerotation($niveaux))->toBe($numeros);
})->with([
    'chapitres seuls' => [[0, 0, 0], ['1', '2', '3']],
    'sections repartent à 1 sous chaque chapitre' => [[0, 1, 1, 0, 1], ['1', '1.1', '1.2', '2', '2.1']],
    'sous-sections' => [[0, 1, 2, 2, 1, 2], ['1', '1.1', '1.1.1', '1.1.2', '1.2', '1.2.1']],
    'enums et null' => [[NiveauSection::Chapitre, NiveauSection::Section, null], ['1', '1.1', '2']],
]);

test('l\'export PDF numérote les titres et n\'ouvre une page que pour les chapitres', function () {
    ['projet' => $projet, 'groupe' => $groupe] = projetExportNiveaux();

    $donnees = null;
    View::composer('projets.export', function ($view) use (&$donnees) {
        $donnees = $view->getData();
    });
    (new ExportProjetPdf)->execute($projet, $groupe);

    $html = view('projets.export', $donnees)->render();

    expect($donnees['sections']->pluck('numero')->all())->toBe(['1', '1.1', '1.1.1', '2'])
        ->and($donnees['sections']->pluck('niveau')->all())->toBe([0, 1, 2, 0])
        ->and($html)->toContain('<h2 class="titre-niveau-1">1.1 Méthode</h2>')
        ->and($html)->toContain('<h2 class="titre-niveau-2">1.1.1 Terrain</h2>')
        ->and(substr_count($html, 'class="section section--suite"'))->toBe(2)
        ->and(substr_count($html, 'class="section "'))->toBe(2);
});

test('l\'export Word donne à chaque section un titre numéroté du niveau correspondant', function () {
    ['projet' => $projet, 'groupe' => $groupe] = projetExportNiveaux();

    ob_start();
    (new ExportProjetWord)->execute($projet, $groupe)->sendContent();
    $fichier = tempnam(sys_get_temp_dir(), 'test_niveaux_');
    file_put_contents($fichier, ob_get_clean());
    $zip = new ZipArchive;
    $zip->open($fichier);
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    unlink($fichier);

    // Un paragraphe Word (<w:p>…</w:p>) qui porte à la fois le style de titre et le texte attendu
    $titre = fn (int $niveau, string $texte) => '/<w:p\b(?:(?!<\/w:p>).)*w:val="Heading'.$niveau.'"(?:(?!<\/w:p>).)*>'.preg_quote($texte, '/').'</s';

    expect($xml)->toMatch($titre(1, '1 Chapitre un'))
        ->and($xml)->toMatch($titre(2, '1.1 Méthode'))
        ->and($xml)->toMatch($titre(3, '1.1.1 Terrain'))
        ->and($xml)->toMatch($titre(1, '2 Chapitre deux'))
        ->and($xml)->toContain('TOC \o "1-4"')
        // Un seul saut de page entre les sections : avant le chapitre 2
        ->and(substr_count($xml, '<w:br w:type="page"/>'))->toBe(1);
});

// ─── Copie du plan ────────────────────────────────────────────────────────────

test('appliquer un gabarit copie le niveau de ses sections', function () {
    $gabarit = GabaritCours::create(['slug' => 'gabarit_niveaux', 'type_cours' => TypeCours::Dep->value, 'nom' => 'Gabarit']);
    $gabaritTypeProjet = GabaritTypeProjet::create(['gabarit_cours_id' => $gabarit->id, 'nom' => 'Projet']);
    foreach ([0, 1, 2] as $index => $niveau) {
        GabaritTypeProjetSection::create([
            'gabarit_type_projet_id' => $gabaritTypeProjet->id, 'label' => "Élément {$index}", 'type' => 'texte', 'ordre' => $index + 1, 'niveau' => $niveau,
        ]);
    }
    $enseignant = User::factory()->create(['role' => 'enseignant']);
    $cours = Cours::create(['nom_cours' => 'Histoire', 'code' => '330-GAB', 'groupe' => 'A', 'enseignant_id' => $enseignant->id]);

    app(AppliquerGabaritService::class)->appliquer($cours, 'gabarit_niveaux');

    $niveaux = TypeProjet::where('cours_id', $cours->id)->first()->sections->pluck('niveau')->all();

    expect($niveaux)->toBe([NiveauSection::Chapitre, NiveauSection::Section, NiveauSection::SousSection]);
});

test('transférer un cours copie le niveau des sections de ses types de projet', function () {
    $typeProjet = typeProjetPourNiveaux();
    planAvecNiveaux($typeProjet, [0, 1, 2, 0]);

    $nouveau = app(TransfererCoursAction::class)->execute(Cours::find($typeProjet->cours_id), 2027, 'automne');

    $niveaux = TypeProjet::where('cours_id', $nouveau->id)->first()->sections->pluck('niveau')->all();

    expect($niveaux)->toBe([NiveauSection::Chapitre, NiveauSection::Section, NiveauSection::SousSection, NiveauSection::Chapitre]);
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
