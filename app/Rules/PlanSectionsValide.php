<?php

namespace App\Rules;

use App\Enums\NiveauSection;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Vérifie que la liste ordonnée des sections forme un plan cohérent.
 *
 * La hiérarchie n'est stockée que via `niveau` et l'ordre de la liste : un plan qui
 * commence par une section ou saute un niveau rendrait le rattachement ambigu.
 */
class PlanSectionsValide implements ValidationRule
{
    /**
     * Refuse un plan qui ne commence pas par un chapitre ou qui descend de plus d'un niveau d'un coup.
     *
     * Un `niveau` absent vaut chapitre ; une valeur hors enum est laissée à la règle `sections.*.niveau`.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_array($value)) {
            $niveauPrecedent = null;

            foreach (array_values($value) as $index => $section) {
                $niveau = NiveauSection::tryFrom((int) ($section['niveau'] ?? NiveauSection::Chapitre->value));

                if ($niveau !== null) {
                    if ($niveauPrecedent === null && $niveau !== NiveauSection::Chapitre) {
                        $fail(__('type_projet.sections.plan.premier_chapitre'));
                    } elseif ($niveauPrecedent !== null && $niveau->value > $niveauPrecedent->value + 1) {
                        $fail(__('type_projet.sections.plan.saut_niveau', ['position' => $index + 1]));
                    }

                    $niveauPrecedent = $niveau;
                }
            }
        }
    }
}
