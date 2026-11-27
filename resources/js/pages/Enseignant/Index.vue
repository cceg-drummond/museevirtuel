<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    FolderKanban,
    Layers,
    Landmark,
    Plus,
    School,
    UserCheck,
    Users,
    Video,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AnneauStatuts from '@/components/enseignant/tableau-bord/AnneauStatuts.vue';
import BandeauIllustration from '@/components/enseignant/tableau-bord/BandeauIllustration.vue';
import CarteIndicateur from '@/components/enseignant/tableau-bord/CarteIndicateur.vue';
import CarteListe from '@/components/enseignant/tableau-bord/CarteListe.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useFilAriane } from '@/composables/useFilAriane';
import AppLayout from '@/layouts/AppLayout.vue';
import { create as createCours } from '@/routes/enseignant/cours';
import { show as showTemoin } from '@/routes/enseignant/temoins';
import { index as projetsGroupe } from '@/routes/projets';
import type { BreadcrumbItem } from '@/types';
import type {
    IndicateursEnseignant,
    ProchaineVisio,
    StatutProjet,
    Temoin,
    TravailRemis,
} from '@/types/enseignant';

const props = defineProps<{
    travauxRemis: TravailRemis[];
    temoinsEnAttente: Temoin[];
    prochainesVisios: ProchaineVisio[];
    indicateurs: IndicateursEnseignant;
    statutProjets: StatutProjet[];
}>();

const { t, locale } = useI18n();
const page = usePage();

// Nombre d'éléments affichés dans les listes courtes du tableau de bord
const TAILLE_LISTE = 4;

const filAriane = useFilAriane();
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    filAriane.accueil(),
    filAriane.courant(t('enseignant.index.dashboard.breadcrumb')),
]);

const totalProjets = computed(() =>
    props.statutProjets.reduce((somme, statut) => somme + statut.total, 0),
);
const projetsEnRetard = computed(
    () =>
        props.statutProjets.find((statut) => statut.valeur === 'en_retard')
            ?.total ?? 0,
);

/**
 * Formate le jour abrégé et le numéro du jour d'une visio pour l'encadré de date.
 */
function jourVisio(iso: string): { jour: string; numero: string } {
    const date = new Date(iso);

    return {
        jour: date.toLocaleDateString(locale.value, { weekday: 'short' }),
        numero: date.toLocaleDateString(locale.value, { day: 'numeric' }),
    };
}

/**
 * Formate l'heure de début d'une visio.
 */
function heureVisio(iso: string): string {
    return new Date(iso).toLocaleTimeString(locale.value, {
        hour: '2-digit',
        minute: '2-digit',
    });
}

/**
 * Ouvre la salle Jitsi d'une visio déjà démarrée dans un nouvel onglet.
 */
function rejoindreVisio(jitsiRoom: string): void {
    window.open(
        `https://meet.jit.si/${jitsiRoom}`,
        '_blank',
        'noopener,noreferrer',
    );
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('enseignant.index.page_title')" />

        <div class="flex flex-col gap-5 p-6">
            <!-- ─── En-tête ─────────────────────────────────────────────── -->
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">
                        {{
                            t('enseignant.index.dashboard.greeting', {
                                prenom: page.props.auth.user.prenom,
                            })
                        }}
                    </h1>
                    <p class="mt-1 text-muted-foreground">
                        {{ t('enseignant.index.dashboard.subtitle') }}
                    </p>
                </div>
                <Button as-child>
                    <Link :href="createCours.url()">
                        <Plus class="h-4 w-4" />
                        {{ t('enseignant.index.new_course') }}
                    </Link>
                </Button>
            </div>

            <BandeauIllustration />

            <!-- ─── Indicateurs ─────────────────────────────────────────── -->
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <CarteIndicateur
                    :icone="BookOpen"
                    :libelle="t('enseignant.index.dashboard.kpi_courses')"
                    :valeur="indicateurs.cours"
                    :detail="
                        t('enseignant.index.dashboard.kpi_courses_detail', {
                            n: indicateurs.coursVerrouilles,
                        })
                    "
                />
                <CarteIndicateur
                    :icone="School"
                    :libelle="t('enseignant.index.dashboard.kpi_classes')"
                    :valeur="indicateurs.classes"
                    :detail="
                        t('enseignant.index.dashboard.kpi_classes_detail', {
                            n: indicateurs.etudiants,
                        })
                    "
                />
                <CarteIndicateur
                    :icone="Users"
                    :libelle="t('enseignant.index.dashboard.kpi_groups')"
                    :valeur="indicateurs.groupes"
                    :detail="
                        t('enseignant.index.dashboard.kpi_groups_detail', {
                            n: indicateurs.groupesAvecTemoin,
                        })
                    "
                />
                <CarteIndicateur
                    :icone="FolderKanban"
                    :libelle="t('enseignant.index.dashboard.kpi_projects')"
                    :valeur="totalProjets"
                    :detail="
                        t('enseignant.index.dashboard.kpi_projects_detail', {
                            n: projetsEnRetard,
                        })
                    "
                    :alerte="projetsEnRetard > 0"
                />
                <CarteIndicateur
                    :icone="UserCheck"
                    :libelle="t('enseignant.index.dashboard.kpi_elders')"
                    :valeur="indicateurs.temoinsApprouves"
                    :detail="
                        t('enseignant.index.dashboard.kpi_elders_detail', {
                            n: indicateurs.temoinsEnAttente,
                        })
                    "
                    :alerte="indicateurs.temoinsEnAttente > 0"
                />
                <CarteIndicateur
                    :icone="Video"
                    :libelle="t('enseignant.index.dashboard.kpi_visios')"
                    :valeur="indicateurs.visiosAVenir"
                    :detail="
                        t('enseignant.index.dashboard.kpi_visios_detail', {
                            n: indicateurs.visiosCetteSemaine,
                        })
                    "
                />
                <CarteIndicateur
                    :icone="Landmark"
                    :libelle="t('enseignant.index.dashboard.kpi_museum')"
                    :valeur="indicateurs.publicationsMusee"
                    :detail="
                        t('enseignant.index.dashboard.kpi_museum_detail', {
                            n: indicateurs.publicationsAApprouver,
                        })
                    "
                    :alerte="indicateurs.publicationsAApprouver > 0"
                />
                <CarteIndicateur
                    :icone="Layers"
                    :libelle="t('enseignant.index.dashboard.kpi_thematics')"
                    :valeur="indicateurs.thematiques"
                    :detail="
                        t('enseignant.index.dashboard.kpi_thematics_detail')
                    "
                />
            </div>

            <!-- ─── Statuts et listes courtes ───────────────────────────── -->
            <div class="grid gap-4 lg:grid-cols-2">
                <Card class="gap-2">
                    <CardHeader>
                        <CardTitle class="text-base">
                            {{ t('enseignant.index.dashboard.project_status') }}
                        </CardTitle>
                        <p class="text-xs text-muted-foreground">
                            {{
                                t(
                                    'enseignant.index.dashboard.project_status_subtitle',
                                )
                            }}
                        </p>
                    </CardHeader>
                    <CardContent>
                        <AnneauStatuts :statuts="statutProjets" />
                    </CardContent>
                </Card>

                <CarteListe
                    :titre="t('enseignant.index.recent_submissions')"
                    :vide="travauxRemis.length === 0"
                    :message-vide="t('enseignant.index.no_submissions')"
                >
                    <li
                        v-for="travail in travauxRemis.slice(0, TAILLE_LISTE)"
                        :key="travail.id"
                        class="flex items-center gap-3 py-2.5"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold">
                                {{ travail.titre_projet ?? '—' }}
                            </p>
                            <p class="truncate text-xs text-muted-foreground">
                                {{
                                    t('classes.groupes.group_number', {
                                        n: travail.classe.numero,
                                    })
                                }}
                                ·
                                {{
                                    new Date(
                                        travail.remis_le,
                                    ).toLocaleDateString(locale)
                                }}
                            </p>
                        </div>
                        <Button size="sm" variant="outline" as-child>
                            <Link
                                :href="
                                    projetsGroupe.url({
                                        cours: travail.classe.cours_id,
                                        classe: travail.classe_id,
                                        groupe: travail.classe.id,
                                    })
                                "
                            >
                                {{ t('enseignant.index.view_project') }}
                            </Link>
                        </Button>
                    </li>
                </CarteListe>

                <CarteListe
                    :titre="t('enseignant.index.dashboard.elders_to_validate')"
                    :vide="temoinsEnAttente.length === 0"
                    :message-vide="t('enseignant.index.dashboard.no_elders')"
                >
                    <li
                        v-for="temoin in temoinsEnAttente.slice(
                            0,
                            TAILLE_LISTE,
                        )"
                        :key="temoin.id"
                        class="flex items-center gap-3 py-2.5"
                    >
                        <div
                            class="grid size-8 shrink-0 place-items-center rounded-full border bg-muted text-xs font-semibold"
                        >
                            {{ temoin.prenom.charAt(0)
                            }}{{ temoin.nom.charAt(0) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold">
                                {{ temoin.prenom }} {{ temoin.nom }}
                            </p>
                            <div class="flex flex-wrap gap-1">
                                <span
                                    v-for="th in temoin.thematiques_choisies"
                                    :key="th.id"
                                    class="rounded-full border bg-muted px-2 text-[11px] text-muted-foreground"
                                >
                                    {{ th.nom }}
                                </span>
                            </div>
                        </div>
                        <Button size="sm" variant="outline" as-child>
                            <Link :href="showTemoin.url(temoin.id)">
                                {{ t('enseignant.index.dashboard.review') }}
                            </Link>
                        </Button>
                    </li>
                </CarteListe>

                <CarteListe
                    :titre="t('enseignant.index.dashboard.upcoming_visios')"
                    :vide="prochainesVisios.length === 0"
                    :message-vide="t('enseignant.index.dashboard.no_visios')"
                >
                    <li
                        v-for="visio in prochainesVisios.slice(0, TAILLE_LISTE)"
                        :key="visio.id"
                        class="flex items-center gap-3 py-2.5"
                    >
                        <div
                            class="w-11 shrink-0 overflow-hidden rounded-md border text-center"
                        >
                            <small
                                class="block bg-primary py-0.5 text-[10px] font-semibold text-primary-foreground uppercase"
                            >
                                {{ jourVisio(visio.scheduled_at).jour }}
                            </small>
                            <b class="block py-0.5">
                                {{ jourVisio(visio.scheduled_at).numero }}
                            </b>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold">
                                {{ visio.titre }}
                            </p>
                            <p class="truncate text-xs text-muted-foreground">
                                {{ heureVisio(visio.scheduled_at) }} ·
                                {{ visio.cours.code }}-{{ visio.cours.groupe }}
                                ·
                                {{
                                    visio.groupe_numero
                                        ? t('classes.groupes.group_number', {
                                              n: visio.groupe_numero,
                                          })
                                        : t(
                                              'enseignant.index.dashboard.all_groups',
                                          )
                                }}
                            </p>
                        </div>
                        <Button
                            v-if="visio.started_at"
                            size="sm"
                            @click="rejoindreVisio(visio.jitsi_room)"
                        >
                            <Video class="h-4 w-4" />
                            {{ t('enseignant.index.dashboard.join') }}
                        </Button>
                    </li>
                </CarteListe>
            </div>
        </div>
    </AppLayout>
</template>
