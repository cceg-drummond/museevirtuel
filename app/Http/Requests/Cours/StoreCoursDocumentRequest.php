<?php

namespace App\Http\Requests\Cours;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide l'upload d'un document associé à un cours.
 */
class StoreCoursDocumentRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     * L'autorisation reste vérifiée dans le contrôleur (sera centralisée dans une Policy).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Retourne les règles de validation pour l'ajout d'un document.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'document' => ['required', 'max:10240', 'mimes:pdf,doc,docx'],
        ];
    }

    /**
     * Retourne les messages d'erreur de validation personnalisés pour un document.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document.required' => __('document.required'),
            'document.mimes' => __('document.format_error'),
            'document.max' => __('document.max'),
        ];
    }
}
