<?php

namespace App\Http\Requests\TypeProjet;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation du basculement de visibilité d'un groupe de critères (positif ou négatif).
 */
class UpdateVisibiliteGroupeCritereRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     * L'autorisation reste vérifiée dans le contrôleur.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Retourne les règles de validation pour le basculement de visibilité d'un groupe de critères.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:positif,negatif'],
            'visible' => ['required', 'boolean'],
        ];
    }

    /**
     * Retourne les messages d'erreur de validation personnalisés.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => __('visibilite_groupe_critere.type.required'),
            'type.in' => __('visibilite_groupe_critere.type.in'),
            'visible.required' => __('visibilite_groupe_critere.visible.required'),
            'visible.boolean' => __('visibilite_groupe_critere.visible.boolean'),
        ];
    }
}
