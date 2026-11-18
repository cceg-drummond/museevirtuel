<?php

namespace App\Http\Requests\TypeProjet;

use App\Http\Requests\Concerns\AutoriseGestionTypeProjet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valide le choix des classes ayant accès à un type de projet.
 */
class UpdateAccessibiliteTypeProjetRequest extends FormRequest
{
    use AutoriseGestionTypeProjet;

    /**
     * Seul l'enseignant du cours (ou un admin) peut choisir les classes d'un type de projet.
     */
    public function authorize(): bool
    {
        return $this->peutGererTypeProjet();
    }

    /**
     * Retourne les règles : « tous » ou une liste de classes appartenant au cours de l'URL.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'tous' => ['required', 'boolean'],
            'classes' => ['array', 'required_if:tous,false'],
            // Une classe d'un autre cours ne doit jamais recevoir l'accès, même par un ID forgé.
            'classes.*' => ['integer', Rule::exists('classes', 'id')->where('cours_id', $this->route('cours')->id)],
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
            'tous.required' => __('type_projet.accessibilite.tous.required'),
            'tous.boolean' => __('type_projet.accessibilite.tous.boolean'),
            'classes.array' => __('type_projet.accessibilite.classes.array'),
            'classes.required_if' => __('type_projet.accessibilite.classes.required_if'),
            'classes.*.integer' => __('type_projet.accessibilite.classes.integer'),
            'classes.*.exists' => __('type_projet.accessibilite.classes.exists'),
        ];
    }
}
