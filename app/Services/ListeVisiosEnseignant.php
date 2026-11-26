<?php

namespace App\Services;

use App\Http\Resources\VisioConferenceResource;
use App\Models\User;
use App\Models\VisioConference;

/**
 * Liste les visioconférences de tous les cours d'un enseignant.
 */
readonly class ListeVisiosEnseignant
{
    /**
     * Retourne les visioconférences de l'enseignant, de la plus récente à la plus ancienne.
     *
     * @return array<int, mixed>
     */
    public function obtenirVisios(User $user): array
    {
        return VisioConference::whereHas('cours', fn ($q) => $q->where('enseignant_id', $user->id))
            ->with(['cours:id,nom_cours,code,groupe', 'groupe', 'animateur:id,prenom,nom'])
            ->orderByDesc('scheduled_at')
            ->get()
            ->pipe(fn ($visios) => VisioConferenceResource::collection($visios)->resolve());
    }
}
