<?php

namespace App\Http\Requests\QuestionBanque;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation de la création et de la mise à jour d'une question de la banque d'une section.
 */
class SaveQuestionBanqueRequest extends FormRequest
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
     * Retourne les règles de validation pour la création ou la mise à jour d'une question.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'contenu' => ['required', 'string', 'max:2000'],
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
            'contenu.required' => __('question_banque.contenu.required'),
            'contenu.string' => __('question_banque.contenu.string'),
            'contenu.max' => __('question_banque.contenu.max'),
        ];
    }
}
