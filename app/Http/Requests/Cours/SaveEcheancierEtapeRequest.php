<?php

namespace App\Http\Requests\Cours;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide la création et la mise à jour d'une étape de l'échéancier d'un cours.
 */
class SaveEcheancierEtapeRequest extends FormRequest
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
     * Retourne les règles de validation d'une étape de l'échéancier.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'semaine' => ['required', 'integer', 'min:1', 'max:15'],
            'periode' => ['nullable', 'integer', 'in:1,2'],
            'etape' => ['required', 'string', 'max:500'],
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
            'semaine.required' => __('echeancier_etape.semaine.required'),
            'semaine.integer' => __('echeancier_etape.semaine.integer'),
            'semaine.min' => __('echeancier_etape.semaine.min'),
            'semaine.max' => __('echeancier_etape.semaine.max'),
            'periode.integer' => __('echeancier_etape.periode.integer'),
            'periode.in' => __('echeancier_etape.periode.in'),
            'etape.required' => __('echeancier_etape.etape.required'),
            'etape.string' => __('echeancier_etape.etape.string'),
            'etape.max' => __('echeancier_etape.etape.max'),
        ];
    }
}
