<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ExternalLink, PlayCircle, Video } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import BoutonTooltip from '@/components/ui/BoutonTooltip.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useFilAriane } from '@/composables/useFilAriane';
import AppLayout from '@/layouts/AppLayout.vue';
import { show as coursShow } from '@/routes/cours';
import type { BreadcrumbItem } from '@/types';
import type { VisioEnseignant } from '@/types/enseignant';

const props = defineProps<{ visios: VisioEnseignant[] }>();

const { t, locale } = useI18n();
const filAriane = useFilAriane();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    filAriane.accueil(),
    filAriane.visios(),
]);

type Etat = 'en_cours' | 'a_venir' | 'passee';

const CLASSES_ETAT: Record<Etat, string> = {
    en_cours:
        'border-green-200 bg-green-50 text-green-700 dark:border-green-900 dark:bg-green-950 dark:text-green-300',
    a_venir:
        'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-300',
    passee: 'bg-muted text-muted-foreground',
};

/**
 * Détermine l'état d'une visio : démarrée et non terminée, planifiée dans le futur, ou passée.
 */
function etat(visio: VisioEnseignant): Etat {
    if (visio.started_at && !visio.ended_at) {
        return 'en_cours';
    }

    return !visio.ended_at && new Date(visio.scheduled_at) >= new Date()
        ? 'a_venir'
        : 'passee';
}

const onglet = ref<'a_venir' | 'passees'>('a_venir');

// À venir : la plus proche d'abord ; passées : la plus récente d'abord (ordre reçu du serveur)
const aVenir = computed(() =>
    props.visios
        .filter((v) => etat(v) !== 'passee')
        .slice()
        .reverse(),
);
const passees = computed(() =>
    props.visios.filter((v) => etat(v) === 'passee'),
);
const visiosAffichees = computed(() =>
    onglet.value === 'a_venir' ? aVenir.value : passees.value,
);

/**
 * Formate la date et l'heure d'une visio.
 */
function dateHeure(iso: string): string {
    return new Date(iso).toLocaleString(locale.value, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

/**
 * Ouvre la salle Jitsi d'une visio démarrée dans un nouvel onglet.
 */
function rejoindre(jitsiRoom: string): void {
    window.open(
        `https://meet.jit.si/${jitsiRoom}`,
        '_blank',
        'noopener,noreferrer',
    );
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('enseignant.visios.title')" />

        <div class="flex flex-col gap-5 p-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    {{ t('enseignant.visios.title') }}
                </h1>
                <p class="mt-1 text-muted-foreground">
                    {{ t('enseignant.visios.subtitle') }}
                </p>
            </div>

            <Card>
                <CardHeader class="flex flex-row flex-wrap items-center gap-2">
                    <Video class="h-5 w-5" />
                    <CardTitle>{{ t('enseignant.visios.title') }}</CardTitle>
                    <div class="ml-auto flex gap-1.5">
                        <button
                            v-for="choix in ['a_venir', 'passees'] as const"
                            :key="choix"
                            type="button"
                            class="rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                            :class="
                                onglet === choix
                                    ? 'bg-primary text-primary-foreground'
                                    : 'hover:bg-muted'
                            "
                            @click="onglet = choix"
                        >
                            {{ t(`enseignant.visios.tab_${choix}`) }} ({{
                                choix === 'a_venir'
                                    ? aVenir.length
                                    : passees.length
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
                                        {{ t('enseignant.visios.header_date') }}
                                    </th>
                                    <th class="pr-4 pb-3 font-medium">
                                        {{
                                            t('enseignant.projets.header_title')
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
                                            t('enseignant.groupes.header_group')
                                        }}
                                    </th>
                                    <th class="pr-4 pb-3 font-medium">
                                        {{ t('enseignant.visios.header_host') }}
                                    </th>
                                    <th class="pr-4 pb-3 font-medium">
                                        {{
                                            t(
                                                'enseignant.projets.header_status',
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
                                    v-for="visio in visiosAffichees"
                                    :key="visio.id"
                                    class="border-b last:border-0"
                                >
                                    <td
                                        class="py-3 pr-4 whitespace-nowrap tabular-nums"
                                    >
                                        {{ dateHeure(visio.scheduled_at) }}
                                    </td>
                                    <td
                                        class="max-w-xs truncate py-3 pr-4 font-medium"
                                    >
                                        {{ visio.titre }}
                                    </td>
                                    <td
                                        class="py-3 pr-4 font-mono text-xs whitespace-nowrap"
                                    >
                                        {{ visio.cours.code }}-{{
                                            visio.cours.groupe
                                        }}
                                    </td>
                                    <td
                                        class="py-3 pr-4 whitespace-nowrap text-muted-foreground"
                                    >
                                        {{
                                            visio.groupe_numero
                                                ? t(
                                                      'classes.groupes.group_number',
                                                      {
                                                          n: visio.groupe_numero,
                                                      },
                                                  )
                                                : t(
                                                      'enseignant.index.dashboard.all_groups',
                                                  )
                                        }}
                                    </td>
                                    <td
                                        class="py-3 pr-4 whitespace-nowrap text-muted-foreground"
                                    >
                                        {{ visio.animateur.prenom }}
                                        {{ visio.animateur.nom }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        <span
                                            class="rounded-full border px-2 py-0.5 text-xs font-medium whitespace-nowrap"
                                            :class="CLASSES_ETAT[etat(visio)]"
                                        >
                                            {{
                                                t(
                                                    `enseignant.visios.state_${etat(visio)}`,
                                                )
                                            }}
                                        </span>
                                    </td>
                                    <td class="py-3">
                                        <div class="flex gap-2">
                                            <Button
                                                v-if="
                                                    etat(visio) === 'en_cours'
                                                "
                                                size="sm"
                                                @click="
                                                    rejoindre(visio.jitsi_room)
                                                "
                                            >
                                                <Video class="h-4 w-4" />
                                                {{
                                                    t(
                                                        'enseignant.index.dashboard.join',
                                                    )
                                                }}
                                            </Button>
                                            <BoutonTooltip
                                                v-if="
                                                    visio.has_recording &&
                                                    visio.recording_stream_url
                                                "
                                                :texte="
                                                    t(
                                                        'enseignant.visios.recording',
                                                    )
                                                "
                                                size="sm"
                                                variant="outline"
                                                as-child
                                            >
                                                <a
                                                    :href="
                                                        visio.recording_stream_url
                                                    "
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                >
                                                    <PlayCircle
                                                        class="h-4 w-4"
                                                    />
                                                </a>
                                            </BoutonTooltip>
                                            <BoutonTooltip
                                                :texte="
                                                    t(
                                                        'enseignant.visios.manage',
                                                    )
                                                "
                                                size="sm"
                                                variant="outline"
                                                as-child
                                            >
                                                <Link
                                                    :href="
                                                        coursShow.url(
                                                            visio.cours.id,
                                                        )
                                                    "
                                                >
                                                    <ExternalLink
                                                        class="h-4 w-4"
                                                    />
                                                </Link>
                                            </BoutonTooltip>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="visiosAffichees.length === 0">
                                    <td
                                        colspan="7"
                                        class="py-6 text-center text-muted-foreground"
                                    >
                                        {{ t('enseignant.visios.empty') }}
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
