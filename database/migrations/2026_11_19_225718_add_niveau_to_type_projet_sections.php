<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute la colonne `niveau` aux sections des types de projet et des gabarits.
     *
     * 0 = chapitre, 1 = section, 2 = sous-section. La hiérarchie se déduit de `ordre`
     * (ordre de lecture global) : un élément appartient au dernier élément de niveau
     * inférieur qui le précède. Le défaut 0 fait des sections existantes des chapitres.
     */
    public function up(): void
    {
        Schema::table('type_projet_sections', function (Blueprint $table) {
            $table->unsignedTinyInteger('niveau')->default(0)->after('ordre');
        });

        // Le gabarit doit conserver la hiérarchie pour la recopier lors de son application
        Schema::table('gabarit_types_projets_sections', function (Blueprint $table) {
            $table->unsignedTinyInteger('niveau')->default(0)->after('ordre');
        });
    }

    /**
     * Retire la colonne `niveau` des deux tables.
     */
    public function down(): void
    {
        Schema::table('type_projet_sections', function (Blueprint $table) {
            $table->dropColumn('niveau');
        });

        Schema::table('gabarit_types_projets_sections', function (Blueprint $table) {
            $table->dropColumn('niveau');
        });
    }
};
