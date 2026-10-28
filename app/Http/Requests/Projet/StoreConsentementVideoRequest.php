<?php

namespace App\Http\Requests\Projet;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide l'enregistrement du consentement vidéo d'un utilisateur pour un projet.
 */
class StoreConsentementVideoRequest extends FormRequest
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
     * Retourne les règles de validation du consentement vidéo.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'accepte' => ['required', 'boolean'],
            'signature' => ['nullable', 'string', 'max:65535'],
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
            'accepte.required' => __('consentement_video.accepte.required'),
            'accepte.boolean' => __('consentement_video.accepte.boolean'),
            'signature.string' => __('consentement_video.signature.string'),
            'signature.max' => __('consentement_video.signature.max'),
        ];
    }
}
