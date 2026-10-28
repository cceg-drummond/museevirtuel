<?php

namespace App\Http\Requests\Cours;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide la création et la mise à jour d'un objectif pédagogique de cours.
 */
class SaveCoursObjectifRequest extends FormRequest
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
     * Retourne les règles de validation d'un objectif pédagogique.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'contenu' => ['required', 'string', 'max:1000'],
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
            'contenu.required' => __('cours_objectif.contenu.required'),
            'contenu.string' => __('cours_objectif.contenu.string'),
            'contenu.max' => __('cours_objectif.contenu.max'),
        ];
    }
}
