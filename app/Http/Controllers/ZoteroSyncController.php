<?php

namespace App\Http\Controllers;

use App\Services\ZoteroService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Synchronisation de la bibliothèque Zotero de l'étudiant.
 */
class ZoteroSyncController extends Controller
{
    /**
     * Synchronise la bibliothèque Zotero de l'étudiant avec ses références en base.
     *
     * Utilise un upsert sur (user_id, zotero_item_key) pour éviter les doublons.
     * Si les credentials sont absents ou invalides, redirige avec un message d'erreur.
     *
     * @throws ConnectionException
     */
    public function __invoke(ZoteroService $zotero): RedirectResponse
    {
        $user = auth()->user();
        $credential = $user->zoteroCredential;

        if (! $credential) {
            return back()->withErrors(['zotero' => __('zotero.non_configure')]);
        }

        try {
            $items = $zotero->fetchItems($credential->zotero_user_id, $credential->api_key);
        } catch (ConnectionException) {
            return back()->withErrors(['zotero' => __('zotero.connexion_impossible')]);
        } catch (RequestException $e) {
            if ($e->response->status() === 403) {
                return back()->withErrors(['zotero' => __('zotero.cle_revoquee')]);
            }

            return back()->withErrors(['zotero' => __('zotero.erreur_sync')]);
        }

        DB::transaction(function () use ($user, $items, $credential) {
            $maxOrdre = $user->etudiantReferences()->max('ordre') ?? 0;
            $existantes = $user->etudiantReferences()
                ->whereNotNull('zotero_item_key')
                ->get()
                ->keyBy('zotero_item_key');

            foreach ($items as $item) {
                if ($reference = $existantes->get($item['zotero_item_key'])) {
                    $reference->update($item);
                } else {
                    $user->etudiantReferences()->create(array_merge($item, ['ordre' => ++$maxOrdre]));
                }
            }

            $credential->update(['synchronise_le' => now()]);
        });

        return back()->with('success', __('zotero.synchronise', ['count' => count($items)]));
    }
}
