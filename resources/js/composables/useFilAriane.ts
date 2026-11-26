import { usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { show as coursShow } from '@/routes/cours';
import { index as enseignantIndex } from '@/routes/enseignant';
import { index as coursIndex } from '@/routes/enseignant/cours';
import type { BreadcrumbItem } from '@/types';

/**
 * Éléments de fil d'Ariane partagés, pour que chaque page nomme ses niveaux de la même façon :
 * Accueil › Collection (ex. Cours) › Élément (ex. 330-A-01) › Action (Nouveau, Modifier).
 */
export function useFilAriane() {
    const { t } = useI18n();
    const page = usePage();

    /**
     * Retourne l'accueil du rôle connecté : le tableau de bord enseignant,
     * ou le tableau de bord étudiant (/cours).
     */
    function accueil(): BreadcrumbItem {
        const role = page.props.auth.user?.role;
        const href = role === 'etudiant' ? '/cours' : enseignantIndex.url();

        return { title: t('breadcrumbs.home'), href };
    }

    /**
     * Retourne la collection « Cours » de l'enseignant.
     */
    function cours(): BreadcrumbItem {
        return { title: t('breadcrumbs.courses'), href: coursIndex.url() };
    }

    /**
     * Retourne un cours, nommé par son code et son groupe (ex. 330-A-01).
     */
    function unCours(c: {
        id: number;
        code: string;
        groupe: string;
    }): BreadcrumbItem {
        return { title: `${c.code}-${c.groupe}`, href: coursShow.url(c.id) };
    }

    /**
     * Retourne un niveau final (action ou élément sans page propre), qui pointe vers la page courante.
     */
    function courant(title: string): BreadcrumbItem {
        return { title, href: page.url };
    }

    return { accueil, cours, unCours, courant, t };
}
