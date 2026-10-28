<?php

namespace App\Http\Requests\Projet;

use App\Models\TypeProjet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valide la sauvegarde de la conclusion individuelle d'un membre du groupe.
 */
class UpdateProjetConclusionRequest extends FormRequest
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
     * Retourne les règles de validation de la conclusion individuelle.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var TypeProjet $typeProjet */
        $typeProjet = $this->route('typeProjet');

        return [
            'contenu' => ['nullable', 'string'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'section_id' => ['nullable', 'integer',
                Rule::exists('type_projet_sections', 'id')->where('type_projet_id', $typeProjet->id)],
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
            'contenu.string' => __('projet_conclusion.contenu.string'),
            'user_id.required' => __('projet_conclusion.user_id.required'),
            'user_id.integer' => __('projet_conclusion.user_id.integer'),
            'user_id.exists' => __('projet_conclusion.user_id.exists'),
            'section_id.integer' => __('projet_conclusion.section_id.integer'),
            'section_id.exists' => __('projet_conclusion.section_id.exists'),
        ];
    }
}
