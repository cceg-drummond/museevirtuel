# Plan — Refonte de l'accueil enseignant (issue #35, maquette A · Sobre)

Branche : `35-reformer-accueil-enseignant`
Maquette : https://claude.ai/artifact/JDKWabzz6RhNQuJVwipEJu#a

## Décisions (25 nov. 2026)

- Maquette A retenue ; texte « Musée virtuel · Projet » retiré de l'illustration.
- **Pas** de graphique / indicateur de moyenne des groupes (pas de note par groupe ni d'état « corrigé »).
- Anneau « Statut des projets » : les 4 statuts réels de `StatutProjetRecherche`
  (en cours, remis, en retard, remis en retard), calculés via `statutActuel()` (le statut stocké peut être périmé).
- Tableaux de gestion **Mes cours** et **Thématiques** conservés sous le tableau de bord.
- ~~Barre latérale et en-tête hors périmètre~~ → revu : barre latérale **complète** de la maquette
  (avec création des pages manquantes) ; en-tête : avatar + nom + rôle + Déconnexion (pas de recherche
  ni de notifications).
- Travail en **mini-tâches** : une à la fois, l'utilisateur vérifie et commite avant la suivante.

## Mini-tâches

| # | Mini-tâche | État |
|---|---|---|
| 1 | Tableau de bord (contenu) + fil d'Ariane « Accueil › Tableau de bord » | Fait, à valider |
| 2 | En-tête : avatar, nom, rôle, bouton Déconnexion | |
| 3 | Structure de la barre latérale : marque, groupes, pied Paramètres / Aide (entrées ajoutées au fil des pages ; l'arborescence des cours reste jusqu'à l'étape 15) | |
| 4 | Page Cours | |
| 5 | Page Thématiques | |
| 6 | Page Classes | |
| 7 | Page Groupes | |
| 8 | Page Projets | |
| 9 | Page Aînés | |
| 10 | Page Visioconférences | |
| 11 | Page Échanges | |
| 12 | Page Échéancier | |
| 13 | Page Banque de questions | |
| 14 | Pages Gabarits et Médias | |
| 15 | Retirer l'arborescence des cours de la barre latérale, alléger l'accueil (sections déplacées vers leurs pages) | |
- Pas de tendances (« ▲ 2,1 pts », « +3 ce mois-ci ») : aucun historique disponible.

## Étapes

1. `TableauBordEnseignantProps` : props `indicateurs` et `statutProjets` (props existantes inchangées) + tests Pest.
2. Composants `resources/js/components/enseignant/tableau-bord/` : CarteIndicateur, AnneauStatuts, CarteListe, BandeauIllustration.
3. Extraction des sections de gestion (cours, thématiques, témoins) en composants.
4. Réécriture de `Enseignant/Index.vue` : en-tête, bandeau, 2 × 4 indicateurs, anneau + listes courtes, sections de gestion.
5. Validation : tests, Pint, types, lint, essai manuel (sans cours, admin, sombre, mobile).
