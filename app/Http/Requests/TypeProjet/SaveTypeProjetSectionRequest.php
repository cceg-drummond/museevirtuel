<?php

namespace App\Http\Requests\TypeProjet;

use App\Enums\TypeSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation de la création et de la mise à jour d'une section de type de projet.
 */
class SaveTypeProjetSectionRequest extends FormRequest
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
     * Retourne les règles de validation pour la création ou la mise à jour d'une section.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['nullable', Rule::enum(TypeSection::class)],
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
            'label.required' => __('type_projet_section.label.required'),
            'label.string' => __('type_projet_section.label.string'),
            'label.max' => __('type_projet_section.label.max'),
            'description.string' => __('type_projet_section.description.string'),
            'description.max' => __('type_projet_section.description.max'),
            'type.enum' => __('type_projet_section.type.enum'),
        ];
    }
}
