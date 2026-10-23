<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Cours;
use App\Models\VisioConference;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Démarrage et fin d'une session de visioconférence.
 */
class VisioSessionController extends Controller
{
    /**
     * Démarre une visioconférence en enregistrant l'heure de début.
     *
     * Accessible à l'enseignant du cours et aux membres de ses groupes,
     * car l'étudiant peut aussi initier la rencontre.
     *
     * @throws HttpException si non autorisé
     */
    public function demarrer(Cours $cours, VisioConference $visio): RedirectResponse
    {
        abort_if($visio->cours_id !== $cours->id, 404);
        abort_if($visio->started_at !== null, 422);

        $user = auth()->user();

        $estAutorise = ($user->isEnseignant() && $cours->enseignant_id === $user->id)
            || $user->isAdmin()
            || Classe::where('cours_id', $cours->id)
                ->whereHas('groupes', fn ($q) => $q
                    ->whereHas('membres', fn ($q2) => $q2->where('users.id', $user->id))
                )
                ->exists();

        abort_unless($estAutorise, 403);

        $visio->update(['started_at' => now()]);

        return back()->with('success', __('visio.started'));
    }

    /**
     * Termine une visioconférence en enregistrant l'heure de fin.
     *
     * Accessible à l'enseignant du cours et aux membres de ses groupes.
     *
     * @throws HttpException si non autorisé
     */
    public function terminer(Cours $cours, VisioConference $visio): RedirectResponse
    {
        abort_if($visio->cours_id !== $cours->id, 404);
        abort_if($visio->ended_at !== null, 422);

        $user = auth()->user();

        $estAutorise = ($user->isEnseignant() && $cours->enseignant_id === $user->id)
            || $user->isAdmin()
            || Classe::where('cours_id', $cours->id)
                ->whereHas('groupes', fn ($q) => $q
                    ->whereHas('membres', fn ($q2) => $q2->where('users.id', $user->id))
                )
                ->exists();

        abort_unless($estAutorise, 403);

        $visio->update(['ended_at' => now()]);

        return back()->with('success', __('visio.ended'));
    }
}
