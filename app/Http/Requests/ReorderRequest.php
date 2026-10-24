<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide un réordonnancement : la liste complète des IDs dans l'ordre voulu.
 *
 * L'autorisation reste dans chaque contrôleur (elle dépend de la ressource parente),
 * et les IDs hors de la liste concernée sont ignorés par OrdreElements::reordonner().
 */
class ReorderRequest extends FormRequest
{
    /**
     * L'autorisation est vérifiée par le contrôleur appelant.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation de la liste ordonnée.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'ordre' => ['required', 'array'],
            'ordre.*' => ['required', 'integer', 'distinct'],
        ];
    }

    /**
     * Retourne les IDs dans l'ordre reçu.
     *
     * @return list<int>
     */
    public function ids(): array
    {
        return array_map('intval', array_values($this->validated('ordre')));
    }
}
