<?php

namespace App\Services;

use App\Models\Groupe;
use App\Models\ProjetRecherche;

/**
 * Vérifie qu'un projet contient les textes requis avant sa remise.
 *
 * Les seuils de mots sont dans config/projet.php ; l'éditeur Vue applique les mêmes
 * règles côté client, ce service empêche qu'une requête directe les contourne.
 */
class VerificationCompletudeProjet
{
    /**
     * Retourne les contenus textuels vides ou trop courts avant une remise.
     *
     * Cette validation reprend les mêmes règles que l'éditeur frontend pour
     * empêcher qu'une requête directe contourne les contrôles de l'interface.
     *
     * @return list<array{section: string, raison: string}>
     */
    public function contenusManquants(ProjetRecherche $projet, Groupe $groupe): array
    {
        $seuils = config('projet.mots_minimum');

        $projet->loadMissing([
            'typeProjet.sections',
            'sectionContenus',
            'sectionParagraphes',
            'conclusions',
            'developpements',
        ]);

        $manquants = [];

        $contenusParSection = $projet->sectionContenus->keyBy('section_id');
        $paragraphesParSection = $projet->sectionParagraphes->groupBy('section_id');
        $conclusionsParSection = $projet->conclusions
            ->whereNotNull('section_id')
            ->groupBy('section_id');

        foreach ($projet->typeProjet->sections as $section) {
            $libelle = $section->label ?: 'Section '.$section->id;

            if ($section->type === 'texte') {
                $manquants = $this->ajouterSiInsuffisant(
                    $manquants,
                    (string) $contenusParSection->get($section->id)?->contenu,
                    1,
                    $libelle,
                    'Le contenu textuel est vide.',
                );
            }

            if ($section->type === 'paragraphes') {
                $paragraphes = $paragraphesParSection->get($section->id, collect());

                if ($paragraphes->isEmpty()) {
                    $manquants[] = [
                        'section' => $libelle,
                        'raison' => 'Aucun paragraphe n’a été ajouté.',
                    ];
                }

                foreach ($paragraphes as $paragraphe) {
                    if ($this->nombreMots($paragraphe->contenu) < 1) {
                        $manquants[] = [
                            'section' => "{$libelle} — paragraphe {$paragraphe->ordre}",
                            'raison' => 'Le contenu est vide.',
                        ];
                    }
                }
            }

            if ($section->type === 'individuel') {
                $conclusions = $conclusionsParSection->get($section->id, collect());

                foreach ($groupe->membres as $membre) {
                    $conclusion = $conclusions->firstWhere('user_id', $membre->id);

                    $manquants = $this->ajouterSiInsuffisant(
                        $manquants,
                        (string) $conclusion?->contenu,
                        $seuils['conclusion'],
                        "{$libelle} — conclusion de {$membre->prenom} {$membre->nom}",
                        "La conclusion doit avoir au moins {$seuils['conclusion']} mots.",
                    );
                }
            }
        }

        if ($projet->typeProjet->sections->isEmpty() && $projet->typeProjet->has_introduction) {
            foreach ([
                'introduction_amener' => 'Sujet amené',
                'introduction_poser' => 'Sujet posé',
                'introduction_diviser' => 'Sujet divisé',
            ] as $champ => $libelle) {
                $manquants = $this->ajouterSiInsuffisant(
                    $manquants,
                    (string) $projet->{$champ},
                    $seuils['introduction'],
                    $libelle,
                    "L'introduction doit avoir au moins {$seuils['introduction']} mots.",
                );
            }
        }

        if ($projet->typeProjet->sections->isEmpty() && $projet->typeProjet->has_conclusion_individuelle) {
            $conclusions = $projet->conclusions->whereNull('section_id');

            foreach ($groupe->membres as $membre) {
                $conclusion = $conclusions->firstWhere('user_id', $membre->id);

                $manquants = $this->ajouterSiInsuffisant(
                    $manquants,
                    (string) $conclusion?->contenu,
                    $seuils['conclusion'],
                    "Conclusion de {$membre->prenom} {$membre->nom}",
                    "La conclusion doit avoir au moins {$seuils['conclusion']} mots.",
                );
            }
        }

        foreach ($projet->developpements as $developpement) {
            $manquants = $this->ajouterSiInsuffisant(
                $manquants,
                (string) $developpement->titre,
                $seuils['developpement_titre'],
                "Paragraphe de développement {$developpement->ordre} — titre",
                "Le titre doit avoir au moins {$seuils['developpement_titre']} mots.",
            );
            $manquants = $this->ajouterSiInsuffisant(
                $manquants,
                (string) $developpement->contenu,
                $seuils['developpement_contenu'],
                "Paragraphe de développement {$developpement->ordre} — contenu",
                "Le contenu doit avoir au moins {$seuils['developpement_contenu']} mots.",
            );
        }

        return $manquants;
    }

    /**
     * Ajoute une entrée à la liste si le contenu n'atteint pas le nombre de mots minimum.
     *
     * @param  list<array{section: string, raison: string}>  $manquants
     * @return list<array{section: string, raison: string}>
     */
    private function ajouterSiInsuffisant(array $manquants, string $contenu, int $minimum, string $section, string $raison): array
    {
        if ($this->nombreMots($contenu) < $minimum) {
            $manquants[] = [
                'section' => $section,
                'raison' => $raison,
            ];
        }

        return $manquants;
    }

    /**
     * Compte les mots d'un contenu texte ou HTML après normalisation.
     */
    private function nombreMots(?string $contenu): int
    {
        $texte = trim(html_entity_decode(strip_tags((string) $contenu)));

        if ($texte === '') {
            return 0;
        }

        return count(preg_split('/\s+/u', $texte, -1, PREG_SPLIT_NO_EMPTY));
    }
}
