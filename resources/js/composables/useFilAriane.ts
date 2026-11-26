import { usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { index as administrationIndex } from '@/routes/administration';
import { show as classeShow } from '@/routes/classes';
import { show as coursShow } from '@/routes/cours';
import { index as enseignantIndex } from '@/routes/enseignant';
import { index as classesIndex } from '@/routes/enseignant/classes';
import { index as coursIndex } from '@/routes/enseignant/cours';
import { index as echangesIndex } from '@/routes/enseignant/echanges';
import { index as echeancierIndex } from '@/routes/enseignant/echeancier';
import { index as groupesIndex } from '@/routes/enseignant/groupes';
import { index as projetsIndex } from '@/routes/enseignant/projets';
import { index as questionsIndex } from '@/routes/enseignant/questions';
import { index as temoinsIndex } from '@/routes/enseignant/temoins';
import { index as thematiquesIndex } from '@/routes/enseignant/thematiques';
import { index as visiosIndex } from '@/routes/enseignant/visios';
import { index as typesProjetsIndex } from '@/routes/types-projets';
import type { BreadcrumbItem, NavData } from '@/types';

type CoursAriane = { id: number; code: string; groupe: string };
type ClasseAriane = { id: number; numero: number | string; nom: string | null };

/**
 * Éléments de fil d'Ariane partagés, pour que chaque page nomme ses niveaux de la même façon.
 */
export function useFilAriane() {
    const { t } = useI18n();
    const page = usePage();

    const estEtudiant = (): boolean =>
        page.props.auth.user?.role === 'etudiant';

    /**
     * Retourne l'accueil du rôle connecté : tableau de bord étudiant (/cours),
     * espace témoin (/temoin) ou tableau de bord enseignant (enseignant et admin).
     */
    function accueil(): BreadcrumbItem {
        const role = page.props.auth.user?.role;
        const href =
            role === 'etudiant'
                ? '/cours'
                : role === 'personne_agee'
                  ? '/temoin'
                  : enseignantIndex.url();

        return { title: t('breadcrumbs.home'), href };
    }

    /**
     * Retourne la page Administration.
     */
    function administration(): BreadcrumbItem {
        return {
            title: t('breadcrumbs.administration'),
            href: administrationIndex.url(),
        };
    }

    /**
     * Retourne la collection « Cours » de l'enseignant.
     */
    function cours(): BreadcrumbItem {
        return { title: t('breadcrumbs.courses'), href: coursIndex.url() };
    }

    /**
     * Retourne la collection « Classes » de l'enseignant.
     */
    function classes(): BreadcrumbItem {
        return { title: t('breadcrumbs.classes'), href: classesIndex.url() };
    }

    /**
     * Retourne la collection « Groupes » de l'enseignant.
     */
    function groupes(): BreadcrumbItem {
        return { title: t('breadcrumbs.groups'), href: groupesIndex.url() };
    }

    /**
     * Retourne la collection « Projets » de l'enseignant.
     */
    function projets(): BreadcrumbItem {
        return { title: t('breadcrumbs.projects'), href: projetsIndex.url() };
    }

    /**
     * Retourne la collection « Aînés » (témoins) de l'enseignant.
     */
    function temoins(): BreadcrumbItem {
        return { title: t('breadcrumbs.elders'), href: temoinsIndex.url() };
    }

    /**
     * Retourne la collection « Visioconférences » de l'enseignant.
     */
    function visios(): BreadcrumbItem {
        return { title: t('breadcrumbs.visios'), href: visiosIndex.url() };
    }

    /**
     * Retourne la collection « Échanges » de l'enseignant.
     */
    function echanges(): BreadcrumbItem {
        return { title: t('breadcrumbs.exchanges'), href: echangesIndex.url() };
    }

    /**
     * Retourne la page « Échéancier » de l'enseignant.
     */
    function echeancier(): BreadcrumbItem {
        return { title: t('breadcrumbs.schedule'), href: echeancierIndex.url() };
    }

    /**
     * Retourne la collection « Banque de questions » de l'enseignant.
     */
    function questions(): BreadcrumbItem {
        return { title: t('breadcrumbs.questions'), href: questionsIndex.url() };
    }

    /**
     * Retourne la collection « Thématiques » de l'enseignant.
     */
    function thematiques(): BreadcrumbItem {
        return {
            title: t('breadcrumbs.thematics'),
            href: thematiquesIndex.url(),
        };
    }

    /**
     * Retourne un cours, nommé par son code et son groupe (ex. 330-A-01).
     */
    function unCours(c: CoursAriane): BreadcrumbItem {
        return { title: `${c.code}-${c.groupe}`, href: coursShow.url(c.id) };
    }

    /**
     * Retourne le chemin jusqu'à un cours : Accueil › 330-A-01 (seulement Accueil pour un étudiant).
     *
     * Une fois dans un cours, on saute la collection « Cours » pour que le fil tienne sur une ligne.
     */
    function cheminCours(c: CoursAriane): BreadcrumbItem[] {
        return estEtudiant() ? [accueil()] : [accueil(), unCours(c)];
    }

    /**
     * Retourne une classe : son nom, sinon « Section N ».
     * Pour un étudiant, la classe porte le nom du cours puisqu'il n'est que dans une classe par cours.
     */
    function uneClasse(c: CoursAriane, classe: ClasseAriane): BreadcrumbItem {
        const title = estEtudiant()
            ? `${c.code}-${c.groupe}`
            : classe.nom || t('breadcrumbs.section', { n: classe.numero });

        return {
            title,
            href: classeShow.url({ cours: c.id, classe: classe.id }),
        };
    }

    /**
     * Retourne le chemin jusqu'à une classe : Accueil › 330-A-01 › Section 1 (le fil s'arrête là).
     */
    function cheminClasse(
        c: CoursAriane,
        classe: ClasseAriane,
    ): BreadcrumbItem[] {
        return [...cheminCours(c), uneClasse(c, classe)];
    }

    /**
     * Retourne le chemin du cours ou de la classe de l'URL courante.
     */
    function cheminDepuisUrl(): BreadcrumbItem[] {
        const chemin = new URL(page.url, 'http://localhost').pathname;
        const ids = chemin.match(/^\/cours\/(\d+)(?:\/classes\/(\d+))?/);
        const navData = page.props.navData as NavData | null;
        const c = ids
            ? navData?.cours.find((x) => x.id === Number(ids[1]))
            : undefined;

        if (!ids || !c) {
            return [accueil()];
        }

        const classe = ids[2]
            ? c.classes.find((x) => x.id === Number(ids[2]))
            : undefined;

        return classe ? cheminClasse(c, classe) : cheminCours(c);
    }

    /**
     * Retourne la collection « Types de projet » d'un cours.
     */
    function typesProjets(c: CoursAriane): BreadcrumbItem {
        return {
            title: t('breadcrumbs.project_types'),
            href: typesProjetsIndex.url(c.id),
        };
    }

    /**
     * Retourne un niveau final qui pointe vers la page courante.
     */
    function courant(title: string): BreadcrumbItem {
        return { title, href: page.url };
    }

    return {
        accueil, administration, cours, classes, groupes,
        projets, temoins, visios, echanges, echeancier, questions, thematiques, unCours,
        cheminCours, uneClasse, cheminClasse,
        cheminDepuisUrl, typesProjets,
        courant,
        t,
    };
}
