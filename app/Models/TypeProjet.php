<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TypeProjet extends Model
{
    protected $table = 'types_projets';

    protected $fillable = [
        'enseignant_id',
        'cours_id',
        'nom',
        'type',
        'description',
        'accessible',
        'date_remise',
        'remises_multiples',
        'retard_permis',
        'generer_page_titre',
        'generer_table_matieres',
        'aide_reference',
        'has_introduction',
        'has_conclusion_individuelle',
        'ponderation',
        'is_sommatif',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'string',
            'accessible' => 'boolean',
            'date_remise' => 'datetime',
            'remises_multiples' => 'boolean',
            'retard_permis' => 'boolean',
            'generer_page_titre' => 'boolean',
            'generer_table_matieres' => 'boolean',
            'aide_reference' => 'boolean',
            'has_introduction' => 'boolean',
            'has_conclusion_individuelle' => 'boolean',
            'ponderation' => 'decimal:2',
            'is_sommatif' => 'boolean',
        ];
    }

    /**
     * Retourne l'enseignant propriétaire de ce type de projet.
     */
    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enseignant_id');
    }

    /**
     * Retourne le cours auquel ce type de projet est rattaché.
     */
    public function cours(): BelongsTo
    {
        return $this->belongsTo(Cours::class);
    }

    /**
     * Retourne les classes ayant accès à ce type de projet quand il n'est pas accessible à toutes.
     */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(Classe::class, 'classe_type_projet', 'type_projet_id', 'classe_id')
            ->withTimestamps();
    }

    /**
     * Limite la requête aux types de projet accessibles pour la classe donnée.
     *
     * Accessible = ouvert à toutes les classes (booléen) ou à cette classe précise (pivot).
     */
    public function scopeAccessiblesPour(Builder $query, Classe $classe): void
    {
        $query->where(fn (Builder $q) => $q
            ->where('accessible', true)
            ->orWhereHas('classes', fn (Builder $c) => $c->where('classes.id', $classe->id)));
    }

    /**
     * Indique si ce type de projet est accessible aux étudiants de la classe donnée.
     */
    public function isAccessiblePour(Classe $classe): bool
    {
        return $this->accessible
            || $this->classes()->where('classes.id', $classe->id)->exists();
    }

    /**
     * Retourne les sections définies par le professeur pour ce type de projet, triées par ordre.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(TypeProjetSection::class, 'type_projet_id')->orderBy('ordre');
    }

    /**
     * Retourne les projets de recherche utilisant ce type.
     */
    public function projets(): HasMany
    {
        return $this->hasMany(ProjetRecherche::class, 'type_projet_id');
    }

    /**
     * Retourne les tâches définies par l'enseignant pour ce type de projet, triées par ordre.
     */
    public function taches(): HasMany
    {
        return $this->hasMany(TypeProjetTache::class, 'type_projet_id')->orderBy('ordre');
    }

    /**
     * Retourne tous les critères de correction (globaux et par section), triés par ordre.
     */
    public function criteres(): HasMany
    {
        return $this->hasMany(TypeProjetCritere::class, 'type_projet_id')->orderBy('ordre');
    }

    /**
     * Retourne uniquement les critères globaux (non liés à une section), triés par ordre.
     *
     * Ces critères sont affichés avant les sections dans la vue de correction.
     */
    public function criteresGlobaux(): HasMany
    {
        return $this->hasMany(TypeProjetCritere::class, 'type_projet_id')
            ->whereNull('section_id')
            ->orderBy('ordre');
    }

    /**
     * Retourne le template visuel associé à ce type de projet musée.
     */
    public function museeTemplate(): HasOne
    {
        return $this->hasOne(MuseeTemplate::class, 'type_projet_id');
    }

    /**
     * Indique si ce type de projet est un musée virtuel.
     */
    public function isMusee(): bool
    {
        return $this->type === 'musee';
    }

    /**
     * Retourne le critère auto-généré représentant la publication du musée, ou null.
     *
     * Ce critère est coché automatiquement à l'approbation du musée
     * et décoché en cas de rejet.
     */
    public function critereMuseePublication(): ?TypeProjetCritere
    {
        return $this->criteres()->where('is_musee_publication', true)->first();
    }
}
