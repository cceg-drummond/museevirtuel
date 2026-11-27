<?php

namespace App\Services;

use App\Models\Groupe;
use App\Models\GroupeMedia;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Construit les médias déposés par les groupes des cours d'un enseignant.
 */
readonly class ListeMediasEnseignant
{
    /**
     * Retourne les groupes ayant des médias, avec le nombre de médias par type.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function obtenirGroupes(User $user): Collection
    {
        $groupes = Groupe::whereHas('classe.cours', fn ($q) => $q->where('enseignant_id', $user->id))
            ->whereHas('medias')
            ->with(['classe:id,cours_id,numero,nom', 'classe.cours:id,nom_cours,code,groupe'])
            ->withCount([
                'medias as photos_count' => fn ($q) => $q->where('type', 'photo'),
                'medias as audios_count' => fn ($q) => $q->where('type', 'audio'),
                'medias as documents_count' => fn ($q) => $q->where('type', 'document'),
            ])
            ->get();

        $numeros = Groupe::numerosParClasses($groupes->pluck('classe_id'));

        return $groupes
            ->sortBy(fn (Groupe $groupe) => [$groupe->classe->cours->code, $groupe->classe->numero, $numeros[$groupe->id]])
            ->map(fn (Groupe $groupe) => [
                ...$this->entete($groupe, $numeros[$groupe->id]),
                'photos_count' => $groupe->photos_count,
                'audios_count' => $groupe->audios_count,
                'documents_count' => $groupe->documents_count,
            ])
            ->values();
    }

    /**
     * Retourne les médias d'un groupe, du plus récent au plus ancien.
     *
     * @return array<string, mixed>
     */
    public function obtenirMedias(Groupe $groupe): array
    {
        $groupe->load(['classe:id,cours_id,numero,nom', 'classe.cours:id,nom_cours,code,groupe', 'medias.auteur:id,prenom,nom']);

        return [
            ...$this->entete($groupe, Groupe::numerosParClasses([$groupe->classe_id])[$groupe->id]),
            'medias' => $groupe->medias->map(fn (GroupeMedia $media) => [
                ...$media->only('id', 'nom_original', 'type', 'taille', 'url', 'transcription_statut'),
                'auteur' => $media->auteur?->only('prenom', 'nom'),
                'depose_le' => $media->created_at->toIso8601String(),
            ])->values(),
        ];
    }

    /**
     * Identifie un groupe : son numéro, sa classe et son cours.
     *
     * @return array<string, mixed>
     */
    private function entete(Groupe $groupe, int $numero): array
    {
        return [
            'groupe' => ['id' => $groupe->id, 'numero' => $numero],
            'classe' => $groupe->classe->only('id', 'numero', 'nom'),
            'cours' => $groupe->classe->cours->only('id', 'nom_cours', 'code', 'groupe'),
        ];
    }
}
