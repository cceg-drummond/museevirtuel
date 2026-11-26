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

## Fil d'Ariane — règle de nommage

Construit par `resources/js/composables/useFilAriane.ts`. **Court, sur une seule ligne** (décision du 25 nov.) :

- Le fil s'arrête **à la classe** : rien après (groupes, projets, notes, vidéos… finissent sur la classe).
- Dans un cours, on saute la collection « Cours » : Accueil › 330-A-01 › Section 1.
  « Cours » n'apparaît que sur la liste et sur « Nouveau ».
- Le composant ne passe jamais à la ligne ; les libellés longs sont tronqués avec « … » (titre complet au survol).

- **Accueil** : tableau de bord du rôle (`/enseignant` pour enseignant et admin, `/cours` pour étudiant).
- **Collection** : nom de la page de liste (« Cours », « Thématiques »…).
- **Élément** : identifiant court — cours `code-groupe` (ex. 330-A-01), classe `nom` ou « Section N »,
  groupe « Groupe N », projet nom du type de projet, témoin « Prénom Nom ».
- **Action** : « Nouveau » / « Nouvelle … », « Modifier ».
- Tant qu'une collection n'a pas de page, on la saute (ex. Accueil › Nouvelle thématique).
- `Musee/*` exclu (hors périmètre).

## Mini-tâches

| # | Mini-tâche | État |
|---|---|---|
| 1 | Tableau de bord (contenu) + fil d'Ariane « Accueil › Tableau de bord » | Fait, à valider |
| 2 | En-tête : avatar, nom, rôle, bouton Déconnexion | |
| 3 | Structure de la barre latérale : marque, groupes, pied Paramètres / Aide (entrées ajoutées au fil des pages ; l'arborescence des cours reste jusqu'à l'étape 15) | |
| 4 | Page Cours | Fait |
| 4b | Fil d'Ariane : composable `useFilAriane` + pages enseignant (accueil, Cours, cours nouveau/modifier, thématique nouvelle/modifier, témoin) | Fait, à valider |
| 4c | Fil d'Ariane : Cours/Show, Classes/Show + aperçus de notes, TypeProjet/* (Classes/Index, Echanges, Groupes : jamais rendues, ignorées) | Fait, à valider |
| 4d | Fil d'Ariane : Groupes/*, Projets/*, Groupe/Videos/Show — chemin lu depuis l'URL + navData, arrêté à la classe | Fait, à valider |
| 4e | Fil d'Ariane : Administration/*, PersonneAgee/Index, pages étudiant (Cours/Index, Cours/Verrouille) | Fait, à valider |
| 5 | Page Thématiques (+ fil d'Ariane Accueil › Thématiques › …) | Fait |
| 6 | Page Classes (toutes les classes de l'enseignant, triées par cours) | Fait |
| 7 | Page Groupes (contrôleur à action unique : GroupeController::index existe déjà) | Fait |
| 8 | Page Projets (statut recalculé, filtre par statut, numéros via Groupe::numerosParClasses) | Fait |
| 9 | Page Aînés (groupe Communauté dans la barre latérale ; ListeTemoinsEnseignant partagé avec l'accueil) | Fait |
| 10 | Page Visioconférences (onglets à venir / passées ; gestion reste dans le cours) | Fait |
| 11 | Page Échanges (conversations groupe-aîné, GroupeEchange ; ClasseEchange non utilisé) | Fait, à valider |
| 12 | Page Échéancier : une carte par cours → « Consulter l'échéancier » (étapes par semaine + avancement des étudiants, lecture seule ; gestion reste dans le cours) | Fait, à valider |
| 13 | Page Banque de questions : une carte par section « choix de questions » → « Consulter les questions » (lecture seule ; gestion reste dans le type de projet). Groupe Ressources ajouté à la barre latérale | Fait, à valider |
| 14 | Pages Gabarits et Médias | |
| 15 | Retirer l'arborescence des cours de la barre latérale, alléger l'accueil (sections déplacées vers leurs pages) | |
- Pas de tendances (« ▲ 2,1 pts », « +3 ce mois-ci ») : aucun historique disponible.

## Étapes

1. `TableauBordEnseignantProps` : props `indicateurs` et `statutProjets` (props existantes inchangées) + tests Pest.
2. Composants `resources/js/components/enseignant/tableau-bord/` : CarteIndicateur, AnneauStatuts, CarteListe, BandeauIllustration.
3. Extraction des sections de gestion (cours, thématiques, témoins) en composants.
4. Réécriture de `Enseignant/Index.vue` : en-tête, bandeau, 2 × 4 indicateurs, anneau + listes courtes, sections de gestion.
5. Validation : tests, Pint, types, lint, essai manuel (sans cours, admin, sombre, mobile).
