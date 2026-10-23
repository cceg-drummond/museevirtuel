<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;

/**
 * Gère la colonne `ordre` d'une liste d'éléments (ajout en fin, suppression, tri).
 *
 * La « portée » est la requête qui délimite la liste : par exemple les développements
 * d'un projet, ou les paragraphes d'une section d'un projet. Elle est clonée à chaque
 * usage pour ne jamais être modifiée par l'appelant.
 */
class OrdreElements
{
    /**
     * Retourne l'ordre à donner à un nouvel élément placé en fin de liste.
     *
     * @param  Builder<Model>|Relation<Model, Model, mixed>  $portee
     */
    public function prochain(Builder|Relation $portee): int
    {
        return ((clone $portee)->max('ordre') ?? 0) + 1;
    }

    /**
     * Supprime l'élément puis renumérote les suivants pour garder une suite 1..n sans trou.
     *
     * @param  Builder<Model>|Relation<Model, Model, mixed>  $portee
     */
    public function supprimer(Model $element, Builder|Relation $portee): void
    {
        DB::transaction(function () use ($element, $portee): void {
            $element->delete();

            (clone $portee)->orderBy('ordre')->each(function (Model $e, int $index): void {
                $e->update(['ordre' => $index + 1]);
            });
        });
    }

    /**
     * Applique l'ordre reçu du client ; les IDs hors de la portée sont ignorés.
     *
     * @param  Builder<Model>|Relation<Model, Model, mixed>  $portee
     * @param  list<int>  $ids
     */
    public function reordonner(Builder|Relation $portee, array $ids): void
    {
        DB::transaction(function () use ($portee, $ids): void {
            foreach ($ids as $index => $id) {
                (clone $portee)->whereKey($id)->update(['ordre' => $index + 1]);
            }
        });
    }
}
