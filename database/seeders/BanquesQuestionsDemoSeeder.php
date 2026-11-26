<?php

namespace Database\Seeders;

use App\Models\Cours;
use App\Models\TypeProjet;
use App\Models\TypeProjetSection;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Banques de questions de démonstration sur les types de projet de prof@demo.com.
 */
class BanquesQuestionsDemoSeeder extends Seeder
{
    /**
     * Banques par type de projet : nom du type => [libellé de la section => questions].
     *
     * @var array<string, array<string, list<string>>>
     */
    private const BANQUES = [
        'Schéma d\'entrevue' => [
            'Questions d\'entrevue — Enfance et famille' => [
                'Où êtes-vous né(e) et où avez-vous grandi ?',
                'Combien de frères et sœurs aviez-vous ?',
                'Quel métier exerçaient vos parents ?',
                'À quoi ressemblait une journée typique dans votre enfance ?',
                'Quels jeux pratiquiez-vous avec vos amis ?',
                'Comment se déroulaient les repas en famille ?',
                'Quelle place occupait la religion dans votre famille ?',
                'Quel souvenir d\'enfance vous a le plus marqué(e) ?',
            ],
            'Questions d\'entrevue — Travail et vie quotidienne' => [
                'Quel a été votre premier emploi ?',
                'Comment vous rendiez-vous au travail ?',
                'Quelles étaient vos conditions de travail ?',
                'Comment les tâches étaient-elles réparties à la maison ?',
                'Que faisiez-vous pendant vos temps libres ?',
                'Qu\'est-ce qui a le plus changé dans le monde du travail depuis ?',
            ],
        ],
        'Projet de recherche' => [
            'Questions sur la Révolution tranquille' => [
                'Quels souvenirs gardez-vous de l\'élection de 1960 ?',
                'Comment avez-vous vécu la création du ministère de l\'Éducation ?',
                'La nationalisation de l\'électricité a-t-elle changé votre quotidien ?',
                'Quelle place l\'Église occupait-elle avant et après les années 1960 ?',
                'Comment la condition des femmes a-t-elle évolué durant cette période ?',
                'Avez-vous visité l\'Expo 67 ? Qu\'en retenez-vous ?',
                'Selon vous, quel a été le changement le plus important de cette époque ?',
            ],
        ],
        'Projet test' => [
            'Banque vide (test)' => [],
        ],
    ];

    /**
     * Ajoute les banques de questions aux types de projet des cours de prof@demo.com.
     */
    public function run(): void
    {
        $enseignant = User::where('email', 'prof@demo.com')->first();

        if ($enseignant === null) {
            $this->command?->warn('prof@demo.com introuvable : lancer DemoSeeder d\'abord.');
        } else {
            Cours::where('enseignant_id', $enseignant->id)
                ->with('typesProjets')
                ->get()
                ->each(fn (Cours $cours) => $this->seederCours($cours));
        }
    }

    /**
     * Ajoute les banques prévues à chaque type de projet du cours qui en a.
     */
    private function seederCours(Cours $cours): void
    {
        foreach ($cours->typesProjets as $typeProjet) {
            foreach (self::BANQUES[$typeProjet->nom] ?? [] as $label => $questions) {
                $this->seederBanque($typeProjet, $label, $questions);
            }
        }
    }

    /**
     * Crée la section « choix de questions » et ses questions si elle n'existe pas déjà.
     *
     * @param  list<string>  $questions
     */
    private function seederBanque(TypeProjet $typeProjet, string $label, array $questions): void
    {
        $section = TypeProjetSection::firstOrCreate(
            ['type_projet_id' => $typeProjet->id, 'label' => $label, 'type' => 'choix_questions'],
            [
                'ordre' => $typeProjet->sections()->max('ordre') + 1,
                'description' => 'Sélectionnez les questions que vous poserez au témoin.',
            ],
        );

        if ($section->wasRecentlyCreated) {
            foreach ($questions as $index => $contenu) {
                $section->questionsBanque()->create(['contenu' => $contenu, 'ordre' => $index + 1]);
            }
        }
    }
}
