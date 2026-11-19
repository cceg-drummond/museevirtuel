<?php

namespace App\Models;

use App\Enums\NiveauSection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeProjetSection extends Model
{
    protected $table = 'type_projet_sections';

    protected $fillable = [
        'type_projet_id',
        'label',
        'description',
        'ordre',
        'niveau',
        'type',
        'musee_contraintes',
        'musee_layout',
        'musee_canevas',
        'est_obligatoire',
        'est_reutilisable',
        'min_occurrences',
        'max_occurrences',
    ];

    /**
     * Retourne les casts de colonnes pour l'hydratation automatique.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'niveau' => NiveauSection::class,
            'musee_contraintes' => 'array',
            'musee_layout' => 'array',
            'musee_canevas' => 'array',
            'est_obligatoire' => 'boolean',
            'est_reutilisable' => 'boolean',
            'min_occurrences' => 'integer',
            'max_occurrences' => 'integer',
        ];
    }

    /**
     * Retourne le type de projet auquel appartient cette section.
     */
    public function typeProjet(): BelongsTo
    {
        return $this->belongsTo(TypeProjet::class, 'type_projet_id');
    }

    /**
     * Retourne les contenus enregistrés pour cette section dans les projets.
     */
    public function contenus(): HasMany
    {
        return $this->hasMany(ProjetSectionContenu::class, 'section_id');
    }

    /**
     * Retourne les paragraphes de section enregistrés pour cette section dans les projets.
     */
    public function sectionParagraphes(): HasMany
    {
        return $this->hasMany(ProjetSectionParagraphe::class, 'section_id');
    }

    /**
     * Retourne les questions de la banque définies par l'enseignant pour cette section.
     */
    public function questionsBanque(): HasMany
    {
        return $this->hasMany(QuestionBanque::class, 'section_id')->orderBy('ordre');
    }

    /**
     * Retourne les médias uploadés pour cette section dans les projets.
     */
    public function medias(): HasMany
    {
        return $this->hasMany(ProjetSectionMedia::class, 'section_id');
    }

    /**
     * Retourne les blocs de contenu musée rattachés à cette section.
     *
     * Les blocs sont partagés entre projets — filtrer par projet_recherche_id à l'usage
     * (ex : `->with(['blocs' => fn($q) => $q->where('projet_recherche_id', $id)])`).
     */
    public function blocs(): HasMany
    {
        return $this->hasMany(MuseeBloc::class, 'section_id')->orderBy('ordre');
    }

    /**
     * Retourne les critères de correction définis pour cette section, triés par ordre.
     */
    public function criteres(): HasMany
    {
        return $this->hasMany(TypeProjetCritere::class, 'section_id')->orderBy('ordre');
    }
}
