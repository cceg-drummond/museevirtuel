<?php

namespace App\Services;

use App\Models\Thematique;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Liste les témoins (aînés) pour un enseignant : ceux en attente sur ses thématiques
 * et ceux qu'il a approuvés.
 *
 */
readonly class ListeTemoinsEnseignant
{
    /**
     * Retourne les témoins en attente liés à au moins une thématique visible par l'enseignant
     *
     * @return Collection<int, User>
     */
    public function obtenirEnAttente(User $user): Collection
    {
        return $this->queryEnAttente($user)
            ->with('thematiquesChoisies:id,nom')
            ->orderBy('created_at')
            ->get(['id', 'prenom', 'nom', 'email', 'description', 'created_at']);
    }

    /**
     * Retourne les témoins actifs approuvés par cet enseignant.
     *
     * @return Collection<int, User>
     */
    public function obtenirApprouves(User $user): Collection
    {
        return $this->queryApprouves($user)
            ->with('thematiquesChoisies:id,nom')
            ->orderBy('created_at')
            ->get(['id', 'prenom', 'nom', 'email', 'description', 'created_at']);
    }

    /**
     * Retourne la requête des témoins en attente liés à au moins une thématique visible
     * par l'enseignant.
     */
    public function queryEnAttente(User $user): Builder
    {
        $thematiqueIds = Thematique::visiblesPar($user)->pluck('id');

        return User::where('role', 'personne_agee')
            ->whereHas('thematiquesChoisies', fn ($q) => $q->whereIn('thematiques.id', $thematiqueIds))
            ->enAttente();
    }

    /**
     * Retourne la requête des témoins actifs approuvés par l'enseignant.
     */
    public function queryApprouves(User $user): Builder
    {
        return User::where('role', 'personne_agee')
            ->where('approuve_par_id', $user->id)
            ->actif();
    }
}
