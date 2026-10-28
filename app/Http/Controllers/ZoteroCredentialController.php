<?php

namespace App\Http\Controllers;

use App\Http\Requests\Zotero\StoreZoteroCredentialRequest;
use App\Services\ZoteroService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;

/**
 * Identifiants Zotero de l'étudiant (clé API + identifiant utilisateur).
 */
class ZoteroCredentialController extends Controller
{
    /**
     * Sauvegarde les credentials Zotero de l'étudiant (clé API + identifiant utilisateur).
     *
     * Le format est validé par StoreZoteroCredentialRequest, puis la clé API
     * est validée contre l'API Zotero avant d'être chiffrée et stockée.
     *
     * @throws ConnectionException
     */
    public function store(StoreZoteroCredentialRequest $request, ZoteroService $zotero): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $valide = $zotero->validateApiKey($validated['zotero_user_id'], $validated['api_key']);
        } catch (ConnectionException) {
            return back()->withErrors(['api_key' => 'Impossible de joindre l\'API Zotero. Réessayez dans quelques instants.']);
        }

        if (! $valide) {
            return back()->withErrors(['api_key' => 'Clé API ou identifiant Zotero invalide. Vérifiez vos informations.']);
        }

        // La clé est chiffrée automatiquement par le cast 'encrypted' du modèle
        auth()->user()->zoteroCredential()->updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'zotero_user_id' => $validated['zotero_user_id'],
                'api_key' => $validated['api_key'],
            ],
        );

        return back()->with('success', 'Compte Zotero configuré avec succès.');
    }

    /**
     * Supprime les credentials Zotero et toutes les références importées de Zotero.
     *
     * Les références ajoutées manuellement (zotero_item_key = null) sont conservées.
     */
    public function destroy(): RedirectResponse
    {
        $user = auth()->user();

        // Supprimer uniquement les références provenant de Zotero
        $user->etudiantReferences()->whereNotNull('zotero_item_key')->delete();

        $user->zoteroCredential()?->delete();

        return back()->with('success', 'Compte Zotero déconnecté et références Zotero supprimées.');
    }
}
