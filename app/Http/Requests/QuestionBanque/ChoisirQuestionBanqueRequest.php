<?php

namespace App\Http\Requests\QuestionBanque;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation du choix des questions de la banque par un groupe.
 */
class ChoisirQuestionBanqueRequest extends FormRequest
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
     * Retourne les règles de validation pour le choix des questions par un groupe.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'question_ids' => ['present', 'array'],
            'question_ids.*' => ['integer', 'exists:question_banques,id'],
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
            'question_ids.array' => __('question_banque.question_ids.array'),
            'question_ids.*.integer' => __('question_banque.question_ids.integer'),
            'question_ids.*.exists' => __('question_banque.question_ids.exists'),
        ];
    }
}
