<?php

use App\Models\Classe;
use App\Models\Cours;
use App\Models\Groupe;
use App\Models\GroupeEchange;
use App\Models\User;

test("la page Échanges liste les conversations des groupes jumelés, la plus récemment active d'abord", function () {
    $enseignant = User::factory()->create(['role' => 'enseignant']);
    $cours = Cours::create(['nom_cours' => 'Histoire', 'code' => '330-H', 'groupe' => '01', 'enseignant_id' => $enseignant->id]);
    $classe = Classe::create(['cours_id' => $cours->id]);
    $etudiant = User::factory()->create(['role' => 'etudiant']);
    $temoin = User::factory()->create(['role' => 'personne_agee', 'statut' => 'actif', 'prenom' => 'Gérard']);

    // Groupe jumelé sans message, puis groupe jumelé avec deux messages
    $silencieux = Groupe::create(['classe_id' => $classe->id, 'created_by' => $etudiant->id, 'personne_agee_id' => $temoin->id]);
    $actif = Groupe::create(['classe_id' => $classe->id, 'created_by' => $etudiant->id, 'personne_agee_id' => $temoin->id]);
    GroupeEchange::create(['groupe_id' => $actif->id, 'auteur_id' => $etudiant->id, 'contenu' => '<p>Premier</p>'])
        ->forceFill(['created_at' => now()->subDay()])->save();
    GroupeEchange::create(['groupe_id' => $actif->id, 'auteur_id' => $temoin->id, 'contenu' => '<p>En 1962…</p>']);

    // Groupe non jumelé et sans message : n'est pas une conversation
    Groupe::create(['classe_id' => $classe->id, 'created_by' => $etudiant->id]);

    $this->actingAs($enseignant)
        ->get(route('enseignant.echanges.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Enseignant/Echanges/Index')
            ->has('conversations', 2)
            ->where('conversations.0.groupe.id', $actif->id)
            ->where('conversations.0.messages_count', 2)
            ->where('conversations.0.dernier_message.extrait', 'En 1962…')
            ->where('conversations.0.dernier_message.auteur.prenom', 'Gérard')
            ->where('conversations.1.groupe.id', $silencieux->id)
            ->where('conversations.1.dernier_message', null)
        );
});

test('la page Échanges renvoie les étudiants vers leur tableau de bord', function () {
    $this->actingAs(User::factory()->create(['role' => 'etudiant']))
        ->get(route('enseignant.echanges.index'))
        ->assertRedirect(route('dashboard'));
});
