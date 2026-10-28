<?php

namespace App\Http\Requests\Visio;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation du dépôt du fichier vidéo d'une visioconférence.
 */
class StoreVisioEnregistrementRequest extends FormRequest
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
     * Retourne les règles de validation pour le dépôt d'un enregistrement vidéo.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'recording' => ['required', 'mimes:mp4,mov,webm,avi', 'max:1048576'],
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
            'recording.required' => __('visio_enregistrement.recording.required'),
            'recording.mimes' => __('visio_enregistrement.recording.mimes'),
            'recording.max' => __('visio_enregistrement.recording.max'),
        ];
    }
}
