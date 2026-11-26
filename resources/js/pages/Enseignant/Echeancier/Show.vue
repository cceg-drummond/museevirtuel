<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, CircleCheck } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useFilAriane } from '@/composables/useFilAriane';
import AppLayout from '@/layouts/AppLayout.vue';
import { show as coursShow } from '@/routes/cours';
import type { BreadcrumbItem } from '@/types';
import type { EcheancierEnseignant } from '@/types/enseignant';

const props = defineProps<{ echeancier: EcheancierEnseignant }>();

type Etape = EcheancierEnseignant['etapes'][number];

const { t } = useI18n();
const filAriane = useFilAriane();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    filAriane.accueil(),
    filAriane.echeancier(),
    filAriane.courant(
        `${props.echeancier.cours.code}-${props.echeancier.cours.groupe}`,
    ),
]);

/**
 * Regroupe les étapes d'un cours par semaine, dans l'ordre des semaines.
 */
function parSemaine(etapes: Etape[]): [number, Etape[]][] {
    const semaines = new Map<number, Etape[]>();

    for (const etape of etapes) {
        semaines.set(etape.semaine, [
            ...(semaines.get(etape.semaine) ?? []),
            etape,
        ]);
    }

    return [...semaines.entries()].sort(([a], [b]) => a - b);
}

/**
 * Pourcentage des étudiants du cours ayant terminé l'étape.
 */
function pourcentage(etape: Etape, total: number): number {
    return total > 0 ? Math.round((etape.etudiants_termines / total) * 100) : 0;
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('enseignant.echeancier.title')" />

        <div class="flex flex-col gap-5 p-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    {{ t('enseignant.echeancier.title') }}
                </h1>
                <p class="mt-1 text-muted-foreground">
                    {{ t('enseignant.echeancier.subtitle') }}
                </p>
            </div>

            <Card>
                <CardHeader class="flex flex-row flex-wrap items-center gap-2">
                    <CalendarDays class="h-5 w-5" />
                    <CardTitle class="min-w-0 flex-1 truncate">
                        <span class="font-mono text-sm">
                            {{ echeancier.cours.code }}-{{
                                echeancier.cours.groupe
                            }}
                        </span>
                        · {{ echeancier.cours.nom_cours }}
                    </CardTitle>
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="coursShow.url(echeancier.cours.id)">
                            {{ t('enseignant.echeancier.manage') }}
                        </Link>
                    </Button>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="echeancier.etapes.length === 0"
                        class="py-2 text-sm text-muted-foreground"
                    >
                        {{ t('enseignant.echeancier.no_step') }}
                    </p>
                    <div v-else class="space-y-5">
                        <div
                            v-for="[semaine, etapes] in parSemaine(
                                echeancier.etapes,
                            )"
                            :key="semaine"
                        >
                            <p
                                class="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                {{
                                    t('enseignant.echeancier.week', {
                                        n: semaine,
                                    })
                                }}
                            </p>
                            <ul class="divide-y">
                                <li
                                    v-for="etape in etapes"
                                    :key="etape.id"
                                    class="flex flex-wrap items-center gap-3 py-2"
                                >
                                    <div
                                        class="flex min-w-0 flex-1 items-center gap-2 text-sm"
                                    >
                                        <span
                                            v-if="etape.periode"
                                            class="shrink-0 rounded-full border px-2 text-xs text-muted-foreground"
                                        >
                                            P{{ etape.periode }}
                                        </span>
                                        <span
                                            class="truncate"
                                            :class="
                                                etape.is_done
                                                    ? 'text-muted-foreground line-through'
                                                    : ''
                                            "
                                            :title="etape.etape"
                                        >
                                            {{ etape.etape }}
                                        </span>
                                        <CircleCheck
                                            v-if="etape.is_done"
                                            class="h-4 w-4 shrink-0 text-green-600"
                                            :aria-label="
                                                t('enseignant.echeancier.done')
                                            "
                                        />
                                    </div>
                                    <div
                                        class="flex w-full items-center gap-2 sm:w-56"
                                    >
                                        <div
                                            class="h-2 flex-1 overflow-hidden rounded-full border bg-muted"
                                        >
                                            <div
                                                class="h-full rounded-full bg-primary"
                                                :style="{
                                                    width: `${pourcentage(etape, echeancier.etudiants_count)}%`,
                                                }"
                                            />
                                        </div>
                                        <span
                                            class="w-24 shrink-0 text-right text-xs text-muted-foreground"
                                        >
                                            {{
                                                t(
                                                    'enseignant.echeancier.students_done',
                                                    {
                                                        n: etape.etudiants_termines,
                                                        total: echeancier.etudiants_count,
                                                    },
                                                )
                                            }}
                                        </span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
