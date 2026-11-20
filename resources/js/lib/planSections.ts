/**
 * Logique du plan hiérarchique d'un type de projet (chapitre → section → sous-section).
 *
 * Le plan est une liste plate dans l'ordre de lecture : un élément appartient au dernier
 * élément de niveau inférieur qui le précède. Un « bloc » est un élément suivi de ses
 * descendants. Les fonctions qui modifient le plan agissent en place, pour fonctionner
 * directement sur le tableau réactif d'un formulaire Inertia.
 */

/** 0 = chapitre, 1 = section, 2 = sous-section (miroir de l'enum PHP NiveauSection). */
export type NiveauSection = 0 | 1 | 2;

export const NIVEAU_CHAPITRE: NiveauSection = 0;
export const NIVEAU_MAX: NiveauSection = 2;

export type ElementPlan = { niveau: NiveauSection };

/**
 * Retourne la numérotation de chaque élément : « 2 », « 2.1 », « 2.1.3 ».
 */
export function numerotation(plan: ElementPlan[]): string[] {
    const compteurs: number[] = [];

    return plan.map(({ niveau }) => {
        compteurs.length = niveau + 1;
        compteurs[niveau] = (compteurs[niveau] ?? 0) + 1;

        return compteurs.map((c) => c ?? 1).join('.');
    });
}

/**
 * Retourne l'index qui suit le bloc commençant à `index` (exclusif).
 */
export function finDuBloc(plan: ElementPlan[], index: number): number {
    let fin = index + 1;

    while (fin < plan.length && plan[fin].niveau > plan[index].niveau) {
        fin++;
    }

    return fin;
}

/**
 * Nombre de descendants de l'élément (sections et sous-sections qu'il contient).
 */
export function nombreDescendants(plan: ElementPlan[], index: number): number {
    return finDuBloc(plan, index) - index - 1;
}

/**
 * Indique si l'élément peut recevoir un enfant (un chapitre ou une section).
 */
export function peutAvoirEnfant(plan: ElementPlan[], index: number): boolean {
    return plan[index].niveau < NIVEAU_MAX;
}

/**
 * Insère un enfant à la fin du bloc de l'élément et retourne son index.
 */
export function ajouterEnfant<T extends ElementPlan>(
    plan: T[],
    index: number,
    nouveau: Omit<T, 'niveau'>,
): number {
    const position = finDuBloc(plan, index);

    plan.splice(position, 0, {
        ...nouveau,
        niveau: (plan[index].niveau + 1) as NiveauSection,
    } as T);

    return position;
}

/**
 * Retire l'élément et tous ses descendants.
 */
export function supprimerBloc(plan: ElementPlan[], index: number): void {
    plan.splice(index, finDuBloc(plan, index) - index);
}

/**
 * Indique si le bloc peut descendre d'un niveau (devenir l'enfant de l'élément précédent).
 *
 * Il faut un élément précédent de même niveau ou plus profond, et aucun descendant
 * ne doit dépasser la sous-section.
 */
export function peutDescendre(plan: ElementPlan[], index: number): boolean {
    if (index === 0 || plan[index].niveau > plan[index - 1].niveau) {
        return false;
    }

    const bloc = plan.slice(index, finDuBloc(plan, index));

    return Math.max(...bloc.map((e) => e.niveau)) < NIVEAU_MAX;
}

/**
 * Indique si le bloc peut remonter d'un niveau.
 */
export function peutRemonter(plan: ElementPlan[], index: number): boolean {
    return plan[index].niveau > NIVEAU_CHAPITRE;
}

/**
 * Décale d'un niveau l'élément et ses descendants (+1 descend, -1 remonte).
 */
export function decalerBloc(
    plan: ElementPlan[],
    index: number,
    delta: 1 | -1,
): void {
    const fin = finDuBloc(plan, index);

    for (let i = index; i < fin; i++) {
        plan[i].niveau = (plan[i].niveau + delta) as NiveauSection;
    }
}

/**
 * Complète un glisser-déposer d'un seul élément pour déplacer tout son bloc.
 *
 * La liste reçue est celle après le déplacement de l'élément seul, de `ancien` à `nouveau`.
 * Le bloc est réinséré là où l'élément a été déposé, puis le plan est normalisé.
 */
export function deplacerBloc(
    plan: ElementPlan[],
    ancien: number,
    nouveau: number,
): void {
    if (ancien !== nouveau) {
        // On annule le déplacement de l'élément seul pour retrouver le plan d'origine
        plan.splice(ancien, 0, ...plan.splice(nouveau, 1));

        const fin = finDuBloc(plan, ancien);
        const taille = fin - ancien;

        // Déposé dans son propre bloc : rien à déplacer
        if (nouveau < ancien || nouveau >= fin) {
            const bloc = plan.splice(ancien, taille);
            const position = nouveau < ancien ? nouveau : nouveau - taille + 1;

            plan.splice(position, 0, ...bloc);
        }

        normaliser(plan);
    }
}

/**
 * Corrige les niveaux pour que le plan reste cohérent : il commence par un chapitre
 * et ne descend jamais de plus d'un niveau à la fois.
 */
export function normaliser(plan: ElementPlan[]): void {
    plan.forEach((element, i) => {
        const maximum = i === 0 ? NIVEAU_CHAPITRE : plan[i - 1].niveau + 1;

        if (element.niveau > maximum) {
            element.niveau = maximum as NiveauSection;
        }
    });
}
