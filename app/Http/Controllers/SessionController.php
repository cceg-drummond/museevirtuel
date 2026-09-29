<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    /**
     * Change le rôle de l'utilisateur connecté pour les besoins de test.
     */
    public function changerRole(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:etudiant,enseignant'],
        ]);

        $request->user()->update(['role' => $validated['role']]);

        return to_route('auth/login');
    }
}
