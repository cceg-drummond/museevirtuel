<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée le pivot des classes ayant accès à un type de projet.
     *
     * Utilisé seulement quand l'enseignant choisit des classes précises : le booléen
     * types_projets.accessible reste la règle « toutes les classes, y compris les futures ».
     */
    public function up(): void
    {
        Schema::create('classe_type_projet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classe_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('type_projet_id')->constrained('types_projets')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['classe_id', 'type_projet_id']);
        });
    }

    /**
     * Supprime le pivot des classes ayant accès à un type de projet.
     */
    public function down(): void
    {
        Schema::dropIfExists('classe_type_projet');
    }
};
