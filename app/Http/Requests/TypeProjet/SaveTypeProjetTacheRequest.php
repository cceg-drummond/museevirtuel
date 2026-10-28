<?php

namespace App\Http\Requests\TypeProjet;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation de la création et de la mise à jour d'une tâche de type de projet.
 */
class SaveTypeProjetTacheRequest extends FormRequest
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
     * Retourne les règles de validation pour la création ou la mise à jour d'une tâche.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
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
            'titre.required' => __('type_projet_tache.titre.required'),
            'titre.string' => __('type_projet_tache.titre.string'),
            'titre.max' => __('type_projet_tache.titre.max'),
            'description.string' => __('type_projet_tache.description.string'),
            'description.max' => __('type_projet_tache.description.max'),
        ];
    }
}
