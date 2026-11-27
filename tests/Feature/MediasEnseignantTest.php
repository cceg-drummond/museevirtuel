<?php

use App\Models\Classe;
use App\Models\Cours;
use App\Models\Groupe;
use App\Models\GroupeMedia;
use App\Models\User;

/**
 * Crée un groupe dans un nouveau cours de l'enseignant.
 */
function creerGroupeEnseignant(User $enseignant): Groupe
{
    $cours = Cours::create(['nom_cours' => 'Histoire', 'code' => '330-H', 'groupe' => '01', 'enseignant_id' => $enseignant->id]);
    $classe = Classe::create(['cours_id' => $cours->id]);

    return Groupe::create(['classe_id' => $classe->id, 'created_by' => User::factory()->create(['role' => 'etudiant'])->id]);
}

/**
 * Ajoute un média du type donné au groupe.
 */
function ajouterMedia(Groupe $groupe, string $type, string $nom): GroupeMedia
{
    return $groupe->medias()->create([
        'user_id' => $groupe->created_by,
        'nom_original' => $nom,
        'file_path' => "images/groupes/{$groupe->id}/{$nom}",
        'type' => $type,
        'taille' => 2048,
    ]);
}

test('la page Médias affiche une carte par groupe ayant des médias, avec le nombre par type', function () {
    $enseignant = User::factory()->create(['role' => 'enseignant']);
    $groupe = creerGroupeEnseignant($enseignant);
    ajouterMedia($groupe, 'photo', 'ferme.jpg');
    ajouterMedia($groupe, 'photo', 'ecole.jpg');
    ajouterMedia($groupe, 'audio', 'entrevue.mp3');

    // Groupe sans média et groupe d'un autre enseignant : absents de la page
    Groupe::create(['classe_id' => $groupe->classe_id, 'created_by' => $groupe->created_by]);
    ajouterMedia(creerGroupeEnseignant(User::factory()->create(['role' => 'enseignant'])), 'photo', 'autre.jpg');

    $this->actingAs($enseignant)
        ->get(route('enseignant.medias.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Enseignant/Medias/Index')
            ->has('groupes', 1)
            ->where('groupes.0.groupe.id', $groupe->id)
            ->where('groupes.0.photos_count', 2)
            ->where('groupes.0.audios_count', 1)
            ->where('groupes.0.documents_count', 0)
        );
});

test("les médias d'un groupe sont listés avec leur auteur", function () {
    $enseignant = User::factory()->create(['role' => 'enseignant']);
    $groupe = creerGroupeEnseignant($enseignant);
    ajouterMedia($groupe, 'document', 'plan.pdf');

    $this->actingAs($enseignant)
        ->get(route('enseignant.medias.show', $groupe))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Enseignant/Medias/Show')
            ->where('groupe.groupe.numero', 1)
            ->has('groupe.medias', 1)
            ->where('groupe.medias.0.nom_original', 'plan.pdf')
            ->where('groupe.medias.0.type', 'document')
            ->has('groupe.medias.0.auteur.prenom')
        );
});

test("les médias d'un groupe sont refusés à un autre enseignant", function () {
    $groupe = creerGroupeEnseignant(User::factory()->create(['role' => 'enseignant']));

    $this->actingAs(User::factory()->create(['role' => 'enseignant']))
        ->get(route('enseignant.medias.show', $groupe))
        ->assertForbidden();
});

test('la page Médias renvoie les étudiants vers leur tableau de bord', function () {
    $this->actingAs(User::factory()->create(['role' => 'etudiant']))
        ->get(route('enseignant.medias.index'))
        ->assertRedirect(route('dashboard'));
});
