<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnseignantRequest;
use App\Http\Requests\UpdateEnseignantRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Gestion des comptes enseignants par l'administration.
 */
class EnseignantController extends Controller
{
    /**
     * Crée un nouveau compte enseignant.
     *
     * Le mot de passe initial est hashé explicitement (même si le cast 'hashed' le ferait).
     * L'email est validé pour unicité via StoreEnseignantRequest.
     */
    public function store(StoreEnseignantRequest $request): RedirectResponse
    {
        User::create([
            ...$request->validated(),
            'password' => Hash::make('Enseignant'),
            'role' => 'enseignant',
            'email_verified_at' => now(),
        ]);

        return back()->with('success', __('enseignant.created'));
    }

    /**
     * Met à jour les informations d'un enseignant.
     *
     * @throws AuthorizationException si la cible n'est pas un enseignant
     */
    public function update(UpdateEnseignantRequest $request, User $enseignant): RedirectResponse
    {
        // Protège contre la modification accidentelle d'un admin ou étudiant via route binding
        abort_if($enseignant->role !== 'enseignant', 403);

        $enseignant->update($request->validated());

        return back()->with('success', __('enseignant.updated'));
    }

    /**
     * Supprime un compte enseignant.
     *
     * @throws HttpException si la cible n'est pas un enseignant
     */
    public function destroy(User $enseignant): RedirectResponse
    {
        // Protège contre la suppression accidentelle d'un admin ou étudiant via route binding
        abort_if($enseignant->role !== 'enseignant', 403);

        $enseignant->delete();

        return back()->with('success', __('enseignant.deleted'));
    }
}
