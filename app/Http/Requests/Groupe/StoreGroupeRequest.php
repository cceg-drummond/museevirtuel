<?php

namespace App\Http\Requests\Groupe;

use App\Models\Groupe;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valide la création d'un groupe dans une classe.
 */
class StoreGroupeRequest extends FormRequest
{
    /**
     * Un étudiant inscrit, l'enseignant du cours peut créer un groupe.
     */
    public function authorize(): bool
    {
        $classe = $this->route('classe');
        abort_if($classe->cours_id !== $this->route('cours')->id, 404);

        return $this->user()->can('create', [Groupe::class, $classe]);
    }

    /**
     * Retourne les règles de validation pour la création d'un groupe (membres, thématiques et témoin).
     *
     * Le témoin est réservé à l'enseignant du cours et aux admins, et doit partager
     * au moins une thématique avec le groupe.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'membres' => ['array'],
            'membres.*' => ['integer', 'exists:users,id', 'min:1'],
            'thematiques' => ['required', 'array', 'min:1', 'max:3'],
            'thematiques.*' => ['integer', 'exists:thematiques,id'],
            'personne_agee_id' => [
                Rule::prohibitedIf(fn () => $this->user()->cannot('update', $this->route('cours'))),
                'nullable', 'integer',
                Rule::exists('users', 'id')->where('role', 'personne_agee')->where('statut', 'actif'),
                $this->partageUneThematique(...),
            ],
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
            'membres.array' => __('groupe.membres.array'),
            'membres.*.integer' => __('groupe.membres.integer'),
            'membres.*.exists' => __('groupe.membres.exists'),
            'thematiques.required' => __('groupe.thematiques.required'),
            'thematiques.array' => __('groupe.thematiques.array'),
            'thematiques.min' => __('groupe.thematiques.min'),
            'thematiques.max' => __('groupe.thematiques.max'),
            'thematiques.*.integer' => __('groupe.thematiques.integer'),
            'thematiques.*.exists' => __('groupe.thematiques.exists'),
            'personne_agee_id.prohibited' => __('groupe.personne_agee_id.prohibited'),
            'personne_agee_id.integer' => __('groupe_temoin.personne_agee_id.integer'),
            'personne_agee_id.exists' => __('groupe_temoin.personne_agee_id.exists'),
        ];
    }

    /**
     * Refuse un témoin qui ne partage aucune thématique avec celles choisies pour le groupe.
     */
    private function partageUneThematique(string $attribute, mixed $value, Closure $fail): void
    {
        $partage = User::whereKey($value)
            ->whereHas('thematiquesChoisies', fn ($query) => $query->whereIn('thematiques.id', (array) $this->input('thematiques')))
            ->exists();

        if (! $partage) {
            $fail(__('groupe.personne_agee_id.thematique'));
        }
    }
}
