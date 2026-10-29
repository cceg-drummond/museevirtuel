<?php

namespace App\Http\Requests\Classe;

use App\Enums\StatutEtudiantCours;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valide l'ajout manuel d'un étudiant dans une section.
 */
class StoreClasseEtudiantRequest extends FormRequest
{
    /**
     * La classe doit appartenir au cours, que l'utilisateur doit pouvoir modifier.
     */
    public function authorize(): bool
    {
        $cours = $this->route('cours');
        abort_if($this->route('classe')->cours_id !== $cours->id, 404);

        return $this->user()->can('update', $cours);
    }

    /**
     * Retourne les règles de validation pour l'ajout d'un étudiant.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'prenom' => ['required', 'string', 'max:255'],
            'nom' => ['required', 'string', 'max:255'],
            'no_da' => ['required', 'integer'],
            'statut_cours' => ['required', Rule::enum(StatutEtudiantCours::class)],
            'email' => ['nullable', 'string', 'email', 'max:255'],
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
            'prenom.required' => __('classe_etudiant.prenom.required'),
            'prenom.string' => __('classe_etudiant.prenom.string'),
            'prenom.max' => __('classe_etudiant.prenom.max'),
            'nom.required' => __('classe_etudiant.nom.required'),
            'nom.string' => __('classe_etudiant.nom.string'),
            'nom.max' => __('classe_etudiant.nom.max'),
            'no_da.required' => __('classe_etudiant.no_da.required'),
            'no_da.integer' => __('classe_etudiant.no_da.integer'),
            'statut_cours.required' => __('classe_etudiant.statut_cours.required'),
            'statut_cours.enum' => __('classe_etudiant.statut_cours.enum'),
            'email.string' => __('classe_etudiant.email.string'),
            'email.email' => __('classe_etudiant.email.email'),
            'email.max' => __('classe_etudiant.email.max'),
        ];
    }

    /**
     * Applique le statut « actif » par défaut quand le formulaire ne fournit pas de statut.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'statut_cours' => $this->input('statut_cours', StatutEtudiantCours::Actif->value),
        ]);
    }
}
