<?php

namespace App\Http\Requests\Groupe;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide l'assignation (ou la désassignation) d'une tâche à un membre du groupe.
 */
class AssignerGroupeTacheRequest extends FormRequest
{
    /**
     * L'autorisation reste vérifiée dans le contrôleur (sera centralisée dans une Policy).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Retourne les règles de validation de l'utilisateur assigné.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'assigne_a' => ['nullable', 'integer', 'exists:users,id'],
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
            'assigne_a.integer' => __('groupe_tache.assigne_a.integer'),
            'assigne_a.exists' => __('groupe_tache.assigne_a.exists'),
        ];
    }
}
