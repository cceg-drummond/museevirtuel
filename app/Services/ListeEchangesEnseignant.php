<?php

namespace App\Services;

use App\Models\Groupe;
use App\Models\GroupeEchange;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Liste les conversations groupe-aîné des cours d'un enseignant.
 */
readonly class ListeEchangesEnseignant
{
    /**
     * Retourne les groupes jumelés à un aîné ou ayant des messages.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function obtenirConversations(User $user): Collection
    {
        $groupes = Groupe::whereHas('classe.cours', fn ($q) => $q->where('enseignant_id', $user->id))
            ->where(fn ($q) => $q->whereNotNull('personne_agee_id')->orWhereHas('echanges'))
            ->with([
                'classe:id,cours_id,numero,nom',
                'classe.cours:id,nom_cours,code,groupe',
                'temoin:id,prenom,nom',
                'echanges' => fn ($q) => $q->reorder()->latest()->limit(1)->with('auteur:id,prenom,nom'),
            ])
            ->withCount('echanges')
            ->get();

        $numeros = Groupe::numerosParClasses($groupes->pluck('classe_id'));

        return $groupes
            ->sortByDesc(fn (Groupe $groupe) => $groupe->echanges->first()?->created_at?->timestamp ?? 0)
            ->map(fn (Groupe $groupe) => $this->conversation($groupe, $numeros[$groupe->id]))
            ->values();
    }

    /**
     * Construit la conversation d'un groupe.
     *
     * @return array<string, mixed>
     */
    private function conversation(Groupe $groupe, int $numero): array
    {
        return [
            'groupe' => ['id' => $groupe->id, 'numero' => $numero],
            'classe' => $groupe->classe->only('id', 'numero', 'nom'),
            'cours' => $groupe->classe->cours->only('id', 'nom_cours', 'code', 'groupe'),
            'temoin' => $groupe->temoin?->only('id', 'prenom', 'nom'),
            'messages_count' => $groupe->echanges_count,
            'dernier_message' => $this->dernierMessage($groupe->echanges->first()),
        ];
    }

    /**
     * Résume le dernier message d'une conversation, ou null s'il n'y en a pas.
     *
     * @return array<string, mixed>|null
     */
    private function dernierMessage(?GroupeEchange $message): ?array
    {
        return $message ? [
            'extrait' => str($message->contenu)->stripTags()->squish()->limit(90)->value(),
            'auteur' => $message->auteur?->only('prenom', 'nom'),
            'envoye_le' => $message->created_at->toIso8601String(),
        ] : null;
    }
}
