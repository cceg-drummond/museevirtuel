<?php

namespace App\Http\Requests\Cours;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide la création et la mise à jour d'un lien d'entrevue de cours.
 */
class SaveCoursLienEntrevueRequest extends FormRequest
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
     * Retourne les règles de validation d'un lien d'entrevue.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:2048'],
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
            'label.required' => __('cours_lien_entrevue.label.required'),
            'label.string' => __('cours_lien_entrevue.label.string'),
            'label.max' => __('cours_lien_entrevue.label.max'),
            'url.required' => __('cours_lien_entrevue.url.required'),
            'url.url' => __('cours_lien_entrevue.url.url'),
            'url.max' => __('cours_lien_entrevue.url.max'),
        ];
    }
}
