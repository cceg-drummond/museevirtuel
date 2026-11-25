# Plan — Supprimer la colonne `projets_recherche.statut`

Décision (25 nov. 2026) : la colonne est écrite mais jamais relue ; tous les affichages
recalculent le statut via `ProjetRecherche::statutActuel()`. On la supprime au lieu de la synchroniser.

À faire dans une issue / branche séparée de #35.

## Inventaire

| Fichier | Usage | Action |
|---|---|---|
| `database/migrations/2026_10_01_004606_add_statut_to_projets_recherche_table.php` | Crée + remplit la colonne | Ne pas toucher (historique) |
| `app/Models/ProjetRecherche.php:28` | `'statut'` dans `$fillable` | Retirer |
| `app/Models/ProjetRecherche.php:48` | cast `StatutProjetRecherche` | Retirer |
| `app/Models/ProjetRecherche.php:82-90` | `synchroniserStatut()` | Supprimer la méthode |
| `app/Services/RemiseProjetService.php:40, 56, 112` (+ docblock l. 17) | `synchroniserStatut()` après remise / annulation / vote | Retirer les appels |
| `app/Services/ProjetRechercheIndexProps.php:100` | `synchroniserStatut()` dans `resumeProjet()` (écriture en GET) | Remplacer par `statutActuel()` ou réutiliser la valeur de la carte (l. 83) |
| `tests/Feature/EnseignantTableauBordTest.php:144` | `forceFill(['statut' => 'en_cours'])` (cas « périmé ») | Retirer le forceFill ; garder le cas « date passée → en retard » |
| `app/Enums/StatutProjetRecherche.php` | Calcul `fromDates()` + `label()` | Garder |

Aucun `where('statut')` sur les projets, aucune lecture front de `projet.statut` sérialisé
(`ProjetRechercheProps.php:61` envoie le modèle entier, mais la page ne lit pas ce champ).
`Musee/*` n'est pas concerné (hors périmètre).

## Étapes

1. **Migration** `drop_statut_from_projets_recherche_table`
   - `up()` : `dropColumn('statut')`.
   - `down()` : recréer `string('statut', 30)->default('en_cours')->after('remis_le')` et la remplir
     avec `statutActuel()` (projets chargés avec `typeProjet`), pour un rollback cohérent.
2. **Modèle** : retirer `statut` de `$fillable` et de `casts()`, supprimer `synchroniserStatut()`.
3. **Services** : retirer les 3 appels dans `RemiseProjetService` (et la mention dans le docblock) ;
   dans `ProjetRechercheIndexProps`, calculer le statut une seule fois par carte et le passer à `resumeProjet()`.
4. **Tests** : adapter `EnseignantTableauBordTest` ; vérifier `ProjetRechercheTest`,
   `ProjetRecherchePagesTest` et les tests de remise / vote / annulation.
5. **Validation** : `php artisan migrate` puis `migrate:rollback --step=1` et `migrate` sur une base locale,
   `php artisan test --compact`, `./vendor/bin/pint`.

## Risques

- **`statutActuel()` sans `typeProjet` chargé** retombe sur `projets_recherche.date_remise` (ancienne colonne)
  au lieu de la date du TypeProjet → statut faux silencieusement. Aujourd'hui tous les appelants chargent
  `typeProjet`, mais c'est fragile. Option : charger la relation à la demande dans `statutActuel()`
  (`$this->typeProjet` au lieu de `null`) — à valider, coût d'une requête si non chargée.
- **Ordre avec #35** : le test du tableau de bord (l. 144) écrit dans la colonne ; si #35 est fusionnée
  d'abord, l'adapter dans cette branche (déjà prévu à l'étape 4).
- **Perte de la valeur en base** : sans impact, elle n'est lue nulle part ; un futur besoin de filtrer
  par statut en SQL devra passer par une requête sur les dates (date_remise / remis_le).

## Estimation

~1 h : migration + 3 fichiers de code + 1 test à adapter.
