<?php

namespace App\Services;

use App\Models\TypeProjetSection;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Construit les banques de questions des types de projet d'un enseignant.
 */
readonly class ListeQuestionsEnseignant
{
    /**
     * Retourne les sections « choix de questions » de l'enseignant avec leur nombre de questions.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function obtenirBanques(User $user): Collection
    {
        return TypeProjetSection::where('type', 'choix_questions')
            ->whereHas('typeProjet.cours', fn ($q) => $q->where('enseignant_id', $user->id))
            ->with(['typeProjet:id,cours_id,nom', 'typeProjet.cours:id,nom_cours,code,groupe'])
            ->withCount('questionsBanque')
            ->get()
            ->sortBy([
                fn (TypeProjetSection $a, TypeProjetSection $b) => [
                    $a->typeProjet->cours->code, $a->typeProjet->cours->groupe, $a->typeProjet->nom, $a->ordre,
                ] <=> [
                    $b->typeProjet->cours->code, $b->typeProjet->cours->groupe, $b->typeProjet->nom, $b->ordre,
                ],
            ])
            ->map(fn (TypeProjetSection $section) => [
                ...$this->entete($section),
                'questions_count' => $section->questions_banque_count,
            ])
            ->values();
    }

    /**
     * Retourne une banque de questions avec ses questions dans l'ordre.
     *
     * @return array<string, mixed>
     */
    public function obtenirBanque(TypeProjetSection $section): array
    {
        $section->load(['typeProjet:id,cours_id,nom', 'typeProjet.cours:id,nom_cours,code,groupe',
            'questionsBanque:id,section_id,contenu,ordre']);

        return [
            ...$this->entete($section),
            'questions' => $section->questionsBanque->map->only('id', 'contenu')->values(),
        ];
    }

    /**
     * Identifie une banque : sa section, son type de projet et son cours.
     *
     * @return array<string, mixed>
     */
    private function entete(TypeProjetSection $section): array
    {
        return [
            'id' => $section->id,
            'label' => $section->label,
            'type_projet' => $section->typeProjet->only('id', 'nom'),
            'cours' => $section->typeProjet->cours->only('id', 'nom_cours', 'code', 'groupe'),
        ];
    }
}
