<?php

namespace App\Http\Controllers;

use App\Http\Requests\InscriptionTemoin\StoreInscriptionTemoinEngagementsRequest;
use App\Http\Requests\InscriptionTemoin\StoreInscriptionTemoinRequest;
use App\Models\Etablissement;
use App\Models\Thematique;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InscriptionTemoinController extends Controller
{
    /** Nombre d'engagements à accepter à l'étape 2 de l'inscription. */
    public const NB_ENGAGEMENTS = 6;

    /**
     * Affiche le formulaire d'inscription public pour les témoins (personnes âgées).
     */
    public function show(): Response
    {
        return Inertia::render('auth/InscriptionTemoin', [
            'etablissements' => Etablissement::with(['thematiques' => fn ($q) => $q->orderBy('nom')->select('id', 'nom', 'etablissement_id')])
                ->orderBy('nom')
                ->get(['id', 'nom', 'ville']),
        ]);
    }

    /**
     * Valide les données de l'étape 1, les stocke en session et redirige vers l'étape 2.
     *
     * Le compte n'est pas encore créé à cette étape.
     * Les données sont validées par StoreInscriptionTemoinRequest.
     */
    public function store(StoreInscriptionTemoinRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Valider la cohérence de chaque choix : thème présent + thématiques appartenant au bon cégep
        foreach ($validated['choix'] as $index => $choix) {
            if (empty($choix['thematique_ids']) && empty($choix['theme_libre'])) {
                return back()->withErrors([
                    "choix.{$index}.theme_libre" => __('Veuillez choisir au moins une thématique ou saisir un thème libre pour ce cégep.'),
                ])->withInput();
            }

            foreach ($choix['thematique_ids'] ?? [] as $thematiqueId) {
                if (! Thematique::where('id', $thematiqueId)->where('etablissement_id', $choix['etablissement_id'])->exists()) {
                    return back()->withErrors([
                        "choix.{$index}.thematique_ids" => __('Une thématique sélectionnée n\'appartient pas à ce cégep.'),
                    ])->withInput();
                }
            }
        }

        // Stocker les données en session pour l'étape 2 (le mot de passe reste en clair ;
        // la session Laravel est chiffrée côté serveur et de courte durée)
        session(['inscription_temoin_step1' => $validated]);

        return redirect()->route('inscription.temoin.engagements');
    }

    /**
     * Affiche la page des engagements (étape 2 de l'inscription).
     *
     * Redirige vers l'étape 1 si la session est absente ou expirée.
     */
    public function showEngagements(): Response|RedirectResponse
    {
        if ($redirect = $this->redirectIfStep1Missing()) {
            return $redirect;
        }

        return Inertia::render('auth/InscriptionEngagements');
    }

    /**
     * Crée le compte personne âgée à partir de l'étape 1 et des engagements signés.
     *
     * Les engagements et la signature sont validés par StoreInscriptionTemoinEngagementsRequest.
     */
    public function storeEngagements(StoreInscriptionTemoinEngagementsRequest $request): RedirectResponse
    {
        if ($redirect = $this->redirectIfStep1Missing()) {
            return $redirect;
        }

        $step1 = session('inscription_temoin_step1');

        $validated = $request->validated();

        $user = $this->creerPersonneAgee($step1, $validated['signature']);

        session()->forget('inscription_temoin_step1');

        return redirect()->route('inscription.temoin')
            ->with('success', __('Votre demande a été envoyée. Un administrateur examinera votre inscription prochainement.'));
    }

    /**
     * Retourne une redirection vers l'étape 1 si la session est absente ou expirée, null sinon.
     */
    private function redirectIfStep1Missing(): ?RedirectResponse
    {
        if (! session()->has('inscription_temoin_step1')) {
            return redirect()->route('inscription.temoin')
                ->with('error', __('Votre session a expiré. Veuillez recommencer votre inscription.'));
        }

        return null;
    }

    /**
     * Crée le compte personne âgée avec les données des deux étapes.
     *
     * @param  array<string, mixed>  $step1
     */
    private function creerPersonneAgee(array $step1, string $signature): User
    {
        $user = User::create([
            'prenom' => $step1['prenom'],
            'nom' => $step1['nom'],
            'email' => $step1['email'],
            'password' => $step1['password'],
            'role' => 'personne_agee',
            'statut' => 'en_attente',
            'description' => $step1['description'],
            'disponible_appel_distance' => $step1['disponible_appel_distance'] ?? false,
            'engagements_acceptes_le' => now(),
            'signature_electronique' => $signature,
        ]);

        // Sync toutes les thématiques choisies
        $tousLesIds = collect($step1['choix'])
            ->flatMap(fn ($c) => $c['thematique_ids'] ?? [])
            ->unique()
            ->all();

        if (! empty($tousLesIds)) {
            $user->thematiquesChoisies()->sync($tousLesIds);
        }

        // Insérer le pivot user_etablissement avec thème libre par cégep
        $pivots = collect($step1['choix'])->mapWithKeys(fn ($c) => [
            $c['etablissement_id'] => ['theme_libre' => $c['theme_libre'] ?? null],
        ])->all();

        $user->etablissementsChoisis()->sync($pivots);

        return $user;
    }
}
