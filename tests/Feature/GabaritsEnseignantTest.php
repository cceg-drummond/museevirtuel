<?php

use App\Models\GabaritCours;
use App\Models\User;

/**
 * Crée un gabarit avec un objectif, deux étapes et un type de projet à une section.
 */
function creerGabaritComplet(): GabaritCours
{
    $gabarit = GabaritCours::create(['slug' => 'test', 'type_cours' => 'cours_complet', 'nom' => 'Gabarit test']);
    $gabarit->objectifs()->create(['contenu' => 'Comprendre le passé', 'ordre' => 1]);
    $gabarit->echeancierEtapes()->create(['semaine' => 2, 'etape' => 'Entrevue', 'ordre' => 1]);
    $gabarit->echeancierEtapes()->create(['semaine' => 1, 'etape' => 'Équipes', 'ordre' => 1]);
    $gabarit->typesProjets()->create(['nom' => 'Projet de recherche', 'ponderation' => 40, 'is_sommatif' => true, 'ordre' => 1])
        ->sections()->create(['label' => 'Introduction', 'type' => 'texte', 'ordre' => 1]);

    return $gabarit;
}

test('la page Gabarits affiche une carte par gabarit avec le nombre de ses éléments', function () {
    $gabarit = creerGabaritComplet();

    $this->actingAs(User::factory()->create(['role' => 'enseignant']))
        ->get(route('enseignant.gabarits.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Enseignant/Gabarits/Index')
            ->where('gabarits', fn ($gabarits) => collect($gabarits)->contains(fn ($g) => $g['id'] === $gabarit->id
                && $g['objectifs_count'] === 1
                && $g['echeancier_etapes_count'] === 2
                && $g['types_projets_count'] === 1))
        );
});

test("un gabarit affiche son contenu, l'échéancier dans l'ordre des semaines", function () {
    $gabarit = creerGabaritComplet();

    $this->actingAs(User::factory()->create(['role' => 'enseignant']))
        ->get(route('enseignant.gabarits.show', $gabarit))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Enseignant/Gabarits/Show')
            ->where('gabarit.nom', 'Gabarit test')
            ->where('gabarit.objectifs.0.contenu', 'Comprendre le passé')
            ->where('gabarit.etapes.0.etape', 'Équipes')
            ->where('gabarit.types_projets.0.is_sommatif', true)
            ->where('gabarit.types_projets.0.sections.0.label', 'Introduction')
        );
});

test('la page Gabarits renvoie les étudiants vers leur tableau de bord', function () {
    $this->actingAs(User::factory()->create(['role' => 'etudiant']))
        ->get(route('enseignant.gabarits.index'))
        ->assertRedirect(route('dashboard'));
});
