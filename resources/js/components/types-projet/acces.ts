/** Classe d'un cours telle qu'affichée dans les choix d'accès. */
export type ClasseAcces = {
    id: number;
    numero: string;
    nom: string | null;
};

/** Accès d'un type de projet : « Tous » (booléen) ou une sélection de classes. */
export type TypeProjetAcces = {
    id: number;
    accessible: boolean;
    classes: number[];
};

/**
 * Retourne le nom de la classe, ou « Classe {numero} » quand elle n'a pas de nom.
 */
export function libelleClasse(
    classe: ClasseAcces,
    t: (cle: string, valeurs: Record<string, unknown>) => string,
): string {
    return (
        classe.nom ?? t('types_projet.acces.class', { numero: classe.numero })
    );
}

/**
 * Indique si le type de projet est visible pour au moins une classe.
 */
export function estVisible(typeProjet: TypeProjetAcces): boolean {
    return typeProjet.accessible || typeProjet.classes.length > 0;
}
