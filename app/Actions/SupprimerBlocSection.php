<?php

namespace App\Actions;

use App\Exceptions\TransactionEchoueeException;
use App\Models\TypeProjetSection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Supprime une section avec tout ce qu'elle contient dans le plan, puis renumérote.
 *
 * Supprimer un chapitre seul rattacherait ses sections au chapitre précédent
 * (ou laisserait un plan qui commence par une section).
 */
class SupprimerBlocSection
{
    /**
     * Supprime la section et ses descendants : les éléments suivants plus profonds qu'elle,
     * jusqu'au premier de même niveau ou de niveau supérieur.
     *
     * La suppression cascade en base sur le contenu des projets.
     *
     * @throws TransactionEchoueeException
     */
    public function execute(TypeProjetSection $section): void
    {
        $plan = TypeProjetSection::where('type_projet_id', $section->type_projet_id);

        $idsDescendants = (clone $plan)
            ->where('ordre', '>', $section->ordre)
            ->orderBy('ordre')
            ->get(['id', 'niveau'])
            ->takeWhile(fn (TypeProjetSection $suivante) => $suivante->niveau->value > $section->niveau->value)
            ->pluck('id');

        try {
            DB::transaction(function () use ($plan, $section, $idsDescendants): void {
                (clone $plan)->whereKey($idsDescendants->push($section->id))->delete();

                (clone $plan)->orderBy('ordre')->each(function (TypeProjetSection $restante, int $index): void {
                    $restante->update(['ordre' => $index + 1]);
                });
            });
        } catch (QueryException $e) {
            report($e);

            throw TransactionEchoueeException::depuis($e);
        }
    }
}
