<?php

namespace App\Policies;

use App\Models\Cours;
use App\Models\User;

class CoursPolicy
{
    /**
     * Détermine si l'utilisateur peut consulter le cours.
     */
    public function view(User $user, Cours $cours): bool
    {
        return $user->isAdmin() || ($user->isEnseignant() && $cours->enseignant_id === $user->id);
    }

    /**
     * Détermine si l'utilisateur peut télécharger ou afficher les documents du cours.
     *
     * Plus large que view() : les étudiants inscrits à une classe du cours y ont aussi accès.
     */
    public function viewDocuments(User $user, Cours $cours): bool
    {
        return $this->view($user, $cours)
            || ($user->isEtudiant() && $cours->classes()
                ->whereHas('etudiants', fn ($query) => $query->where('users.id', $user->id))
                ->exists());
    }

    /**
     * Détermine si l'utilisateur peut modifier le cours ou gérer ses étudiants.
     */
    public function update(User $user, Cours $cours): bool
    {
        return $user->isAdmin() || ($user->isEnseignant() && $cours->enseignant_id === $user->id);
    }

    /**
     * Détermine si l'utilisateur peut supprimer le cours.
     */
    public function delete(User $user, Cours $cours): bool
    {
        return $user->isAdmin() || ($user->isEnseignant() && $cours->enseignant_id === $user->id);
    }
}
