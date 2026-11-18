import type { Directive, DirectiveBinding } from 'vue';

/**
 * Directive v-grise — grise un contrôle et le rend inerte (ni clic, ni focus, ni saisie).
 *
 * Sert à l'aperçu étudiant : l'enseignant voit les mêmes contrôles qu'un membre
 * du groupe, sans pouvoir déclencher d'action en son nom.
 */
function appliquer(el: HTMLElement, binding: DirectiveBinding<boolean>): void {
    const grise = binding.value;

    el.inert = grise;
    el.classList.toggle('opacity-50', grise);
    el.classList.toggle('grayscale', grise);
}

export const vGrise: Directive<HTMLElement, boolean> = {
    mounted: appliquer,
    updated: appliquer,
};
