<?php

use App\Models\Cours;
use App\Models\User;
use App\Models\VisioConference;

test("la page Visioconférences liste les visios de tous les cours de l'enseignant, la plus récente d'abord", function () {
    $enseignant = User::factory()->create(['role' => 'enseignant']);
    $cours = Cours::create(['nom_cours' => 'Histoire', 'code' => '330-H', 'groupe' => '01', 'enseignant_id' => $enseignant->id]);

    $passee = VisioConference::create(['cours_id' => $cours->id, 'animateur_id' => $enseignant->id, 'jitsi_room' => 'a', 'titre' => 'Passée', 'scheduled_at' => now()->subWeek(), 'ended_at' => now()->subWeek()]);
    $aVenir = VisioConference::create(['cours_id' => $cours->id, 'animateur_id' => $enseignant->id, 'jitsi_room' => 'b', 'titre' => 'À venir', 'scheduled_at' => now()->addDay()]);

    // La visio d'un autre enseignant ne doit pas apparaître
    $autre = User::factory()->create(['role' => 'enseignant']);
    $autreCours = Cours::create(['nom_cours' => 'Autre', 'code' => '999', 'groupe' => '01', 'enseignant_id' => $autre->id]);
    VisioConference::create(['cours_id' => $autreCours->id, 'animateur_id' => $autre->id, 'jitsi_room' => 'c', 'titre' => 'Autre', 'scheduled_at' => now()->addDay()]);

    $this->actingAs($enseignant)
        ->get(route('enseignant.visios.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Enseignant/Visios/Index')
            ->has('visios', 2)
            ->where('visios.0.id', $aVenir->id)
            ->where('visios.0.cours.code', '330-H')
            ->where('visios.0.animateur.id', $enseignant->id)
            ->where('visios.1.id', $passee->id)
            ->where('visios.1.has_recording', false)
        );
});

test('la page Visioconférences renvoie les étudiants vers leur tableau de bord', function () {
    $this->actingAs(User::factory()->create(['role' => 'etudiant']))
        ->get(route('enseignant.visios.index'))
        ->assertRedirect(route('dashboard'));
});
