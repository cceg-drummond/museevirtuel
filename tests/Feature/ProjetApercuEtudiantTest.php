<?php

use App\Models\Classe;
use App\Models\Cours;
use App\Models\Groupe;
use App\Models\ProjetRecherche;
use App\Models\TypeProjet;
use App\Models\TypeProjetCritere;
use App\Models\User;

// ─── Helper ───────────────────────────────────────────────────────────────────

/**
 * Crée un cours, une classe, un groupe d'un étudiant et un type de projet ouvert.
 *
 * @return array{enseignant: User, etudiant: User, typeProjet: TypeProjet, projet: ProjetRecherche, url: string}
 */
function creerContexteApercuEtudiant(): array
{
    $enseignant = User::factory()->create(['role' => 'enseignant']);
    $etudiant = User::factory()->create(['role' => 'etudiant']);

    $cours = Cours::create([
        'nom_cours' => 'Histoire',
        'code' => '330-AE',
        'groupe' => '01',
        'enseignant_id' => $enseignant->id,
    ]);

    $classe = Classe::create(['cours_id' => $cours->id]);
    $classe->etudiants()->attach($etudiant->id);

    $groupe = Groupe::create(['classe_id' => $classe->id, 'created_by' => $etudiant->id]);
    $groupe->membres()->attach($etudiant->id);

    $typeProjet = TypeProjet::create([
        'enseignant_id' => $enseignant->id,
        'cours_id' => $cours->id,
        'nom' => 'TP aperçu',
        'accessible' => true,
    ]);

    $projet = ProjetRecherche::create(['groupe_id' => $groupe->id, 'type_projet_id' => $typeProjet->id]);

    $url = "/cours/{$cours->id}/classes/{$classe->id}/groupes/{$groupe->id}/projets/{$typeProjet->id}/edit";

    return compact('enseignant', 'etudiant', 'typeProjet', 'projet', 'url');
}

// ─── Tests ────────────────────────────────────────────────────────────────────

test("l'enseignant voit la page comme un membre du groupe en aperçu étudiant", function () {
    ['enseignant' => $enseignant, 'url' => $url] = creerContexteApercuEtudiant();

    $this->actingAs($enseignant)
        ->get($url.'?apercu=etudiant')
        ->assertInertia(fn ($page) => $page
            ->component('Projets/Edit')
            ->where('apercuEtudiant', true)
            ->where('estEnseignant', false)
            // Droits d'un membre : la page affiche les contrôles étudiants (grisés côté client)
            ->where('peutEditer', true)
            ->where('peutRemettre', true)
        );
});

test("l'aperçu étudiant masque les critères invisibles pour les étudiants", function () {
    ['enseignant' => $enseignant, 'typeProjet' => $typeProjet, 'url' => $url] = creerContexteApercuEtudiant();

    TypeProjetCritere::create([
        'type_projet_id' => $typeProjet->id, 'section_id' => null,
        'type' => 'negatif', 'contenu_type' => 'texte',
        'pointage' => 2.0, 'visible' => false, 'ordre' => 1,
    ]);

    $this->actingAs($enseignant)
        ->get($url)
        ->assertInertia(fn ($page) => $page->has('criteresGlobaux', 1));

    $this->actingAs($enseignant)
        ->get($url.'?apercu=etudiant')
        ->assertInertia(fn ($page) => $page->has('criteresGlobaux', 0));
});

test("sans le paramètre, l'enseignant garde sa vue habituelle", function () {
    ['enseignant' => $enseignant, 'url' => $url] = creerContexteApercuEtudiant();

    $this->actingAs($enseignant)
        ->get($url)
        ->assertInertia(fn ($page) => $page
            ->where('apercuEtudiant', false)
            ->where('estEnseignant', true)
        );
});

test("un étudiant ne peut pas activer l'aperçu étudiant", function () {
    ['etudiant' => $etudiant, 'url' => $url] = creerContexteApercuEtudiant();

    $this->actingAs($etudiant)
        ->get($url.'?apercu=etudiant')
        ->assertInertia(fn ($page) => $page
            ->where('apercuEtudiant', false)
            ->where('estEnseignant', false)
        );
});

test("l'aperçu d'un projet verrouillé n'affiche pas les contrôles d'édition", function () {
    ['enseignant' => $enseignant, 'projet' => $projet, 'url' => $url] = creerContexteApercuEtudiant();

    $projet->update(['verrouille' => true]);

    $this->actingAs($enseignant)
        ->get($url.'?apercu=etudiant')
        ->assertInertia(fn ($page) => $page
            ->where('apercuEtudiant', true)
            ->where('peutEditer', false)
            ->where('peutRemettre', false)
        );
});
