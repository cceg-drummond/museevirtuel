<?php

namespace App\Http\Requests\TypeProjet;

use App\Http\Requests\Commun\ReorderRequest;
use App\Http\Requests\Concerns\AutoriseGestionTypeProjet;
use App\Rules\PlanSectionsValide;
use Illuminate\Validation\Validator;

/**
 * Valide le réordonnancement des sections d'un type de projet.
 */
class ReorderTypeProjetSectionRequest extends ReorderRequest
{
    use AutoriseGestionTypeProjet;

    /**
     * Seul l'enseignant du cours (ou un admin) peut réordonner les sections.
     */
    public function authorize(): bool
    {
        return $this->peutGererTypeProjet();
    }

    /**
     * Refuse un nouvel ordre qui rendrait le plan incohérent (ex. une section placée avant tout chapitre).
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isEmpty()) {
                    // validated() relancerait la validation depuis ce hook : on lit l'entrée brute, déjà validée
                    $niveaux = $this->route('typeProjet')->sections()->pluck('niveau', 'id');
                    $plan = array_map(
                        fn (mixed $id) => ['niveau' => $niveaux->get((int) $id)?->value],
                        array_values($this->input('ordre')),
                    );

                    (new PlanSectionsValide)->validate('ordre', $plan, function (string $message) use ($validator): void {
                        $validator->errors()->add('ordre', $message);
                    });
                }
            },
        ];
    }
}
