<?php

namespace App\Http\Controllers;

use App\Models\Thematique;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Fiche et approbation des témoins (personnes âgées) par un enseignant lié par ses thématiques, ou par un admin.
 */
class TemoinApprobationController extends Controller
{
    /**
     * Affiche la fiche détaillée d'un témoin lié aux thématiques de l'enseignant connecté.
     *
     * @throws HttpException si le témoin n'est pas une personne âgée ou n'est pas lié à cet enseignant
     */
    public function show(User $user): Response
    {
        abort_if($user->role !== 'personne_agee', 403);
        $this->verifierLienAvecTemoin($user);

        $user->load(['thematiquesChoisies:id,nom', 'etablissementsChoisis']);

        return Inertia::render('Enseignant/TemoinShow', [
            'temoin' => [
                'id' => $user->id,
                'prenom' => $user->prenom,
                'nom' => $user->nom,
                'email' => $user->email,
                'description' => $user->description,
                'provenance' => $user->provenance,
                'theme_libre' => $user->themeLibre(),
                'statut' => $user->statut,
                'created_at' => $user->created_at->toIso8601String(),
                'thematiques_choisies' => $user->thematiquesChoisies->map->only('id', 'nom')->values(),
                'engagements_acceptes_le' => $user->engagements_acceptes_le?->toIso8601String(),
                'signature_electronique' => $user->signature_electronique,
            ],
        ]);
    }

    /**
     * Approuve l'inscription d'un témoin lié aux thématiques de l'enseignant connecté (ou par un admin).
     *
     * Vérifie que le témoin est bien associé à une thématique de l'enseignant
     * avant d'activer le compte.
     *
     * @throws HttpException si le témoin n'est pas lié à cet enseignant ou n'est pas en attente
     */
    public function approuver(User $user): RedirectResponse
    {
        $this->autoriserActionTemoin($user);

        $user->statut = 'actif';
        $user->approuve_par_id = auth()->id();
        $user->email_verified_at = now();
        $user->save();

        return back()->with('success', __('temoin_approbation.approuve', ['nom' => $user->prenom.' '.$user->nom]));
    }

    /**
     * Refuse l'inscription d'un témoin lié aux thématiques de l'enseignant connecté.
     *
     * @throws HttpException si le témoin n'est pas lié à cet enseignant ou n'est pas en attente
     */
    public function decliner(User $user): RedirectResponse
    {
        $this->autoriserActionTemoin($user);

        $user->statut = 'refuse';
        $user->save();

        return back()->with('success', __('temoin_approbation.decline', ['nom' => $user->prenom.' '.$user->nom]));
    }

    /**
     * Révoque l'approbation d'un témoin actif lié aux thématiques de l'enseignant connecté.
     *
     * Remet le statut à « en_attente » et retire la vérification de l'email.
     *
     * @throws HttpException si le témoin n'est pas actif ou n'est pas lié à cet enseignant
     */
    public function desapprouver(User $user): RedirectResponse
    {
        abort_if(
            $user->role !== 'personne_agee' || $user->statut !== 'actif' || $user->approuve_par_id !== auth()->id(),
            403
        );

        $user->statut = 'en_attente';
        $user->approuve_par_id = null;
        $user->email_verified_at = null;
        $user->save();

        return back()->with('success', __('temoin_approbation.remis_en_attente', ['nom' => $user->prenom.' '.$user->nom]));
    }

    /**
     * Vérifie que l'utilisateur connecté peut approuver ou décliner ce témoin.
     *
     * @throws HttpException si le témoin n'est pas une PA en attente ou sans thématique commune
     */
    private function autoriserActionTemoin(User $user): void
    {
        abort_if($user->role !== 'personne_agee' || ! $user->estEnAttente(), 403);
        $this->verifierLienAvecTemoin($user);
    }

    /**
     * Un enseignant n'agit que sur les témoins qui ont choisi une de ses thématiques ;
     * l'admin, lui, gère tous les témoins depuis son tableau de bord.
     *
     * @throws HttpException
     */
    private function verifierLienAvecTemoin(User $user): void
    {
        if (auth()->user()->isAdmin()) {
            return;
        }

        $thematiqueIds = Thematique::visiblesPar(auth()->user())->pluck('id');

        abort_if(
            $user->thematiquesChoisies()->whereIn('thematiques.id', $thematiqueIds)->doesntExist(),
            403
        );
    }
}
