<?php

namespace App\Http\Requests\Classe;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide l'import CSV d'étudiants dans une section.
 */
class ImportClasseEtudiantRequest extends FormRequest
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
     * Retourne les règles de validation du fichier CSV d'import.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'csv' => ['required', 'file', 'mimes:csv,txt'],
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
            'csv.required' => __('classe_etudiant.csv.required'),
            'csv.file' => __('classe_etudiant.csv.file'),
            'csv.mimes' => __('classe_etudiant.csv.mimes'),
        ];
    }
}
