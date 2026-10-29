<?php

namespace App\Services;

use App\Models\Groupe;
use App\Models\ProjetRecherche;
use App\Models\ProjetVoteRemise;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Remise d'un projet de recherche : remise directe, annulation et vote des membres.
 *
 * Le projet doit avoir sa relation typeProjet chargée : peutEtreRemis() et
 * synchroniserStatut() lisent les paramètres de remise depuis le TypeProjet.
 */
class RemiseProjetService
{
    /**
     * Crée le service.
     */
    public function __construct(private VerificationCompletudeProjet $completude) {}

    /**
     * Remet le projet si tous les contenus requis sont rédigés.
     *
     * @return list<array{section: string, raison: string}> contenus manquants ; vide si la remise a été enregistrée
     */
    public function remettre(ProjetRecherche $projet, Groupe $groupe): array
    {
        $contenusManquants = $this->completude->contenusManquants($projet, $groupe);

        if ($contenusManquants !== []) {
            return $contenusManquants;
        }

        $projet->update(['remis_le' => now()]);
        $projet->synchroniserStatut();

        return [];
    }

    /**
     * Annule la remise : efface les votes et la date de remise.
     */
    public function annuler(ProjetRecherche $projet): void
    {
        DB::transaction(function () use ($projet): void {
            $projet->votes()->delete();
            $projet->update(['remis_le' => null]);
            $projet->synchroniserStatut();
        });
    }

    /**
     * Enregistre le vote d'un membre ; si tous les membres ont voté pour, le projet est remis.
     *
     * Le groupe doit avoir sa relation membres chargée.
     */
    public function voter(ProjetRecherche $projet, Groupe $groupe, User $membre, bool $vote): void
    {
        ProjetVoteRemise::updateOrCreate(
            ['projet_id' => $projet->id, 'user_id' => $membre->id],
            ['vote' => $vote],
        );

        $votes = $projet->votes()->get();

        $tousOntVote = $votes->count() === $groupe->membres->count()
            && $votes->every(fn (ProjetVoteRemise $v) => $v->vote);

        if (! $tousOntVote) {
            return;
        }

        DB::transaction(function () use ($projet): void {
            $projet->refresh();

            if ($projet->remis_le === null || $projet->typeProjet->remises_multiples) {
                $projet->update(['remis_le' => now()]);
                $projet->synchroniserStatut();
            }
        });
    }
}
