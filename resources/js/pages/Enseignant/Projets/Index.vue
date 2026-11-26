<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ExternalLink, FolderKanban } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import BoutonTooltip from '@/components/ui/BoutonTooltip.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useFilAriane } from '@/composables/useFilAriane';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit as projetEdit } from '@/routes/projets';
import type { BreadcrumbItem } from '@/types';
import type { ProjetEnseignant, StatutProjet } from '@/types/enseignant';

const props = defineProps<{ projets: ProjetEnseignant[] }>();

const { t, locale } = useI18n();
const filAriane = useFilAriane();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    filAriane.accueil(),
    filAriane.projets(),
]);

const STATUTS: StatutProjet['valeur'][] = [
    'en_cours',
    'remis',
    'en_retard',
    'remis_en_retard',
];

// Mêmes couleurs que l'anneau des statuts du tableau de bord
const CLASSES_STATUT: Record<StatutProjet['valeur'], string> = {
    en_cours:
        'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-300',
    remis: 'border-green-200 bg-green-50 text-green-700 dark:border-green-900 dark:bg-green-950 dark:text-green-300',
    en_retard:
        'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300',
    remis_en_retard:
        'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300',
};

const filtreStatut = ref<StatutProjet['valeur'] | null>(null);

const projetsFiltres = computed(() =>
    filtreStatut.value === null
        ? props.projets
        : props.projets.filter((p) => p.statut === filtreStatut.value),
);

/**
 * Compte les projets d'un statut, pour le libellé des filtres.
 */
function nombre(statut: StatutProjet['valeur']): number {
    return props.projets.filter((p) => p.statut === statut).length;
}

/**
 * Formate une date ISO en date courte, ou un tiret si absente.
 */
function date(iso: string | null): string {
    return iso ? new Date(iso).toLocaleDateString(locale.value) : '—';
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('enseignant.projets.title')" />

        <div class="flex flex-col gap-5 p-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    {{ t('enseignant.projets.title') }}
                </h1>
                <p class="mt-1 text-muted-foreground">
                    {{ t('enseignant.projets.subtitle') }}
                </p>
            </div>

            <Card>
                <CardHeader class="flex flex-row flex-wrap items-center gap-2">
                    <FolderKanban class="h-5 w-5" />
                    <CardTitle>{{ t('enseignant.projets.title') }}</CardTitle>
                    <span class="text-sm text-muted-foreground">
                        ({{ projetsFiltres.length }})
                    </span>
                    <div class="ml-auto flex flex-wrap gap-1.5">
                        <button
                            type="button"
                            class="rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                            :class="
                                filtreStatut === null
                                    ? 'bg-primary text-primary-foreground'
                                    : 'hover:bg-muted'
                            "
                            @click="filtreStatut = null"
                        >
                            {{ t('enseignant.projets.filter_all') }} ({{
                                projets.length
                            }})
                        </button>
                        <button
                            v-for="statut in STATUTS"
                            :key="statut"
                            type="button"
                            class="rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                            :class="
                                filtreStatut === statut
                                    ? 'bg-primary text-primary-foreground'
                                    : 'hover:bg-muted'
                            "
                            @click="filtreStatut = statut"
                        >
                            {{ t(`enseignant.projets.status.${statut}`) }} ({{
                                nombre(statut)
                            }})
                        </button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left">
                                    <th class="pr-4 pb-3 font-medium">
                                        {{
                                            t('enseignant.projets.header_title')
                                        }}
                                    </th>
                                    <th class="pr-4 pb-3 font-medium">
                                        {{
                                            t('enseignant.projets.header_type')
                                        }}
                                    </th>
                                    <th class="pr-4 pb-3 font-medium">
                                        {{
                                            t('enseignant.groupes.header_group')
                                        }}
                                    </th>
                                    <th class="pr-4 pb-3 font-medium">
                                        {{
                                            t(
                                                'enseignant.classes.header_course',
                                            )
                                        }}
                                    </th>
                                    <th class="pr-4 pb-3 font-medium">
                                        {{
                                            t(
                                                'enseignant.projets.header_status',
                                            )
                                        }}
                                    </th>
                                    <th class="pr-4 pb-3 font-medium">
                                        {{
                                            t(
                                                'enseignant.projets.header_deadline',
                                            )
                                        }}
                                    </th>
                                    <th class="pr-4 pb-3 font-medium">
                                        {{
                                            t(
                                                'enseignant.projets.header_submitted',
                                            )
                                        }}
                                    </th>
                                    <th class="pb-3 font-medium">
                                        {{
                                            t(
                                                'enseignant.index.table_header_actions',
                                            )
                                        }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="projet in projetsFiltres"
                                    :key="projet.id"
                                    class="border-b last:border-0"
                                >
                                    <td
                                        class="max-w-xs truncate py-3 pr-4 font-medium"
                                    >
                                        {{
                                            projet.titre_projet ||
                                            t('enseignant.projets.untitled')
                                        }}
                                    </td>
                                    <td class="py-3 pr-4 text-muted-foreground">
                                        {{ projet.type_projet.nom }}
                                    </td>
                                    <td class="py-3 pr-4 whitespace-nowrap">
                                        {{
                                            t('classes.groupes.group_number', {
                                                n: projet.groupe.numero,
                                            })
                                        }}
                                    </td>
                                    <td
                                        class="py-3 pr-4 whitespace-nowrap text-muted-foreground"
                                    >
                                        <span class="font-mono text-xs">
                                            {{ projet.cours.code }}-{{
                                                projet.cours.groupe
                                            }}
                                        </span>
                                        ·
                                        {{
                                            projet.classe.nom ||
                                            t('breadcrumbs.section', {
                                                n: projet.classe.numero,
                                            })
                                        }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        <span
                                            class="rounded-full border px-2 py-0.5 text-xs font-medium whitespace-nowrap"
                                            :class="
                                                CLASSES_STATUT[projet.statut]
                                            "
                                        >
                                            {{
                                                t(
                                                    `enseignant.projets.status.${projet.statut}`,
                                                )
                                            }}
                                        </span>
                                    </td>
                                    <td class="py-3 pr-4 tabular-nums">
                                        {{ date(projet.date_remise) }}
                                    </td>
                                    <td class="py-3 pr-4 tabular-nums">
                                        {{ date(projet.remis_le) }}
                                    </td>
                                    <td class="py-3">
                                        <BoutonTooltip
                                            :texte="
                                                t('enseignant.projets.open')
                                            "
                                            size="sm"
                                            variant="outline"
                                            as-child
                                        >
                                            <Link
                                                :href="
                                                    projetEdit.url({
                                                        cours: projet.cours.id,
                                                        classe: projet.classe
                                                            .id,
                                                        groupe: projet.groupe
                                                            .id,
                                                        typeProjet:
                                                            projet.type_projet
                                                                .id,
                                                    })
                                                "
                                            >
                                                <ExternalLink class="h-4 w-4" />
                                            </Link>
                                        </BoutonTooltip>
                                    </td>
                                </tr>
                                <tr v-if="projetsFiltres.length === 0">
                                    <td
                                        colspan="8"
                                        class="py-6 text-center text-muted-foreground"
                                    >
                                        {{ t('enseignant.projets.empty') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
