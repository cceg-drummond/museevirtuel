<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    CircleHelp,
    FolderKanban,
    Home,
    Landmark,
    LayoutDashboard,
    Layers,
    LayoutGrid,
    Library,
    School,
    Settings,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLogo from '@/components/app/AppLogo.vue';
import NavFooter from '@/components/app/NavFooter.vue';
import NavMain from '@/components/app/NavMain.vue';
import NavUser from '@/components/app/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { index as enseignantIndex } from '@/routes/enseignant';
import { index as classesIndex } from '@/routes/enseignant/classes';
import { index as coursIndex } from '@/routes/enseignant/cours';
import { index as thematiquesIndex } from '@/routes/enseignant/thematiques';
import { index as guide } from '@/routes/guide';
import { accueil as museeAccueil } from '@/routes/musee/public';
import { edit as editProfile } from '@/routes/profile';
import type { CoursSidebar, GroupeSidebar, NavData, NavItem } from '@/types';

const { t } = useI18n();
const page = usePage();
const user = computed(() => page.props.auth.user);
const navData = computed(() => page.props.navData as NavData | null);

const sessionLabels: Record<string, string> = {
    hiver: 'Hiver',
    ete: 'Été',
    automne: 'Automne',
};

/**
 * Construit les items de navigation hiérarchiques pour un enseignant
 * à partir des données navData partagées par le middleware.
 */
function buildEnseignantItems(): NavItem[] {
    const cours = navData.value?.cours ?? [];

    return cours.map((c: CoursSidebar) => ({
        title: `${c.code} ${sessionLabels[c.session] ?? c.session} ${c.annee}`,
        href: `/cours/${c.id}`,
        icon: BookOpen,
        children: [
            {
                title: t('sidebar.project_types'),
                href: `/cours/${c.id}/types-projets`,
                icon: FolderKanban,
            },
            ...c.classes.map((cl) => ({
                title: cl.nom || `${t('sidebar.section')} ${cl.numero}`,
                href: `/cours/${c.id}/classes/${cl.id}`,
                children: cl.groupes.map((g: GroupeSidebar) => ({
                    title: `${t('sidebar.group')} ${g.numero}`,
                    href: `/cours/${c.id}/classes/${cl.id}/groupes/${g.id}`,
                    children: [
                        {
                            title: t('sidebar.projects'),
                            href: `/cours/${c.id}/classes/${cl.id}/groupes/${g.id}/projets`,
                            children: g.projets.map((p) => ({
                                title: p.titre,
                                href: `/cours/${c.id}/classes/${cl.id}/groupes/${g.id}/projets/${p.type_projet_id}/edit`,
                            })),
                        },
                        ...(g.hasTemoin
                            ? [
                                  {
                                      title: t('sidebar.discussion_temoin'),
                                      href: `/cours/${c.id}/classes/${cl.id}/groupes/${g.id}/echanges`,
                                  },
                              ]
                            : []),
                    ],
                })),
            })),
        ],
    }));
}

/**
 * Construit les items de navigation hiérarchiques pour un étudiant
 * à partir des données navData partagées par le middleware.
 */
function buildEtudiantItems(): NavItem[] {
    const cours = navData.value?.cours ?? [];

    // L'étudiant n'est que dans une seule classe par cours :
    // - le cours mène directement à sa classe (href sur la classe)
    // - le groupe est un lien direct vers la page du groupe
    return cours.map((c: CoursSidebar) => ({
        title: `${c.code}–${c.groupe}`,
        href: c.classes[0]
            ? `/cours/${c.id}/classes/${c.classes[0].id}`
            : undefined,
        icon: BookOpen,
        children: c.classes.flatMap((cl) =>
            cl.groupes.map((g: GroupeSidebar) => ({
                title: `${t('sidebar.group')} ${g.numero}`,
                href: `/cours/${c.id}/classes/${cl.id}/groupes/${g.id}`,
                children: [
                    {
                        title: t('sidebar.projects'),
                        href: `/cours/${c.id}/classes/${cl.id}/groupes/${g.id}/projets`,
                        children: g.projets.map((p) => ({
                            title: p.titre,
                            href: `/cours/${c.id}/classes/${cl.id}/groupes/${g.id}/projets/${p.type_projet_id}/edit`,
                        })),
                    },
                    ...(g.hasTemoin
                        ? [
                              {
                                  title: t('sidebar.discussion_temoin'),
                                  href: `/cours/${c.id}/classes/${cl.id}/groupes/${g.id}/echanges`,
                              },
                          ]
                        : []),
                ],
            })),
        ),
    }));
}

type GroupeNav = { label?: string; items: NavItem[] };

/**
 * Construit les groupes de la barre latérale enseignant (maquette A, issue #35).
 * Les groupes Communauté et Ressources s'ajouteront avec leurs pages.
 */
function buildGroupesEnseignant(estAdmin: boolean): GroupeNav[] {
    const general: NavItem[] = [
        {
            title: t('sidebar.dashboard'),
            href: enseignantIndex.url(),
            icon: LayoutDashboard,
        },
    ];

    if (estAdmin) {
        general.unshift({
            title: t('sidebar.administration'),
            href: '/administration',
            icon: LayoutGrid,
        });
    }

    return [
        { label: t('sidebar.group_general'), items: general },
        {
            label: t('sidebar.group_teaching'),
            items: [
                {
                    title: t('sidebar.courses'),
                    href: coursIndex.url(),
                    icon: Library,
                },
                {
                    title: t('sidebar.classes'),
                    href: classesIndex.url(),
                    icon: School,
                },
                {
                    title: t('sidebar.thematics'),
                    href: thematiquesIndex.url(),
                    icon: Layers,
                },
                ...buildEnseignantItems(),
            ],
        },
        {
            label: t('sidebar.group_diffusion'),
            items: [
                {
                    title: t('sidebar.museum'),
                    href: museeAccueil.url(),
                    icon: Landmark,
                },
            ],
        },
    ];
}

const groupesNav = computed((): GroupeNav[] => {
    const role = user.value?.role;

    if (role === 'admin' || role === 'enseignant') {
        return buildGroupesEnseignant(role === 'admin');
    }

    if (role === 'etudiant') {
        return [
            {
                items: [
                    {
                        title: t('sidebar.home'),
                        href: '/cours',
                        icon: Home,
                    },
                    ...buildEtudiantItems(),
                ],
            },
        ];
    }

    return [];
});

const footerNavItems = computed((): NavItem[] => [
    { title: t('nav.settings'), href: editProfile.url(), icon: Settings },
    { title: t('sidebar.help'), href: guide.url(), icon: CircleHelp },
]);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain
                v-for="(groupe, index) in groupesNav"
                :key="groupe.label ?? index"
                :items="groupe.items"
                :label="groupe.label"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
