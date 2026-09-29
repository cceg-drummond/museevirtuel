<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    public function changerRole(string $role)
    {
        $user = Auth::user();

        if ($role === 'etudiant' && $user->isEnseignant()) {
            $user->role = 'etudiant';
        }elseif ($role === 'enseignant' && $user->isEtudiant()) {
            $user->role = 'enseignant';
        }

        return to_route('')
    }
}
