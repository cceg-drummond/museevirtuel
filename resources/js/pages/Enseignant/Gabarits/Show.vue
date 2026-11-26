<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useFilAriane } from '@/composables/useFilAriane';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { GabaritCoursDetail } from '@/types/enseignant';

const props = defineProps<{ gabarit: GabaritCoursDetail }>();

type Etape = GabaritCoursDetail['etapes'][number];

const { t } = useI18n();
const filAriane = useFilAriane();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    filAriane.accueil(),
    filAriane.gabarits(),
    filAriane.courant(props.gabarit.nom),
]);

/**
 * Étapes de l'échéancier regroupées par semaine, dans l'ordre des semaines.
 */
const etapesParSemaine = computed((): [number, Etape[]][] => {
    const semaines = new Map<number, Etape[]>();

    for (const etape of props.gabarit.etapes) {
        semaines.set(etape.semaine, [
            ...(semaines.get(etape.semaine) ?? []),
            etape,
        ]);
    }

    return [...semaines.entries()].sort(([a], [b]) => a - b);
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="gabarit.nom" />

        <div class="flex flex-col gap-5 p-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    {{ gabarit.nom }}
                </h1>
                <p class="mt-1 text-muted-foreground">
                    {{ t('enseignant.gabarits.how_to_use') }}
                </p>
            </div>

            <div class="grid gap-5 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>
                            {{ t('enseignant.gabarits.objectives') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p
                            v-if="gabarit.objectifs.length === 0"
                            class="text-sm text-muted-foreground"
                        >
                            {{ t('enseignant.gabarits.none') }}
                        </p>
                        <ul v-else class="list-disc space-y-1 pl-5 text-sm">
                            <li
                                v-for="objectif in gabarit.objectifs"
                                :key="objectif.id"
                            >
                                {{ objectif.contenu }}
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>
                            {{ t('enseignant.gabarits.references') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p
                            v-if="gabarit.references.length === 0"
                            class="text-sm text-muted-foreground"
                        >
                            {{ t('enseignant.gabarits.none') }}
                        </p>
                        <ul v-else class="space-y-1 text-sm">
                            <li
                                v-for="reference in gabarit.references"
                                :key="reference.id"
                                class="truncate"
                            >
                                <a
                                    v-if="reference.url"
                                    :href="reference.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="underline underline-offset-2"
                                >
                                    {{ reference.nom }}
                                </a>
                                <span v-else>{{ reference.nom }}</span>
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>
                        {{ t('enseignant.gabarits.project_types') }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="gabarit.types_projets.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        {{ t('enseignant.gabarits.none') }}
                    </p>
                    <ul v-else class="divide-y">
                        <li
                            v-for="typeProjet in gabarit.types_projets"
                            :key="typeProjet.id"
                            class="space-y-1 py-3"
                        >
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold">
                                    {{ typeProjet.nom }}
                                </p>
                                <span
                                    class="rounded-full border px-2 text-xs text-muted-foreground"
                                >
                                    {{
                                        typeProjet.is_sommatif
                                            ? t('enseignant.gabarits.summative')
                                            : t('enseignant.gabarits.formative')
                                    }}
                                </span>
                                <span
                                    v-if="typeProjet.ponderation !== null"
                                    class="rounded-full border px-2 text-xs text-muted-foreground"
                                >
                                    {{
                                        t('enseignant.gabarits.weight', {
                                            n: typeProjet.ponderation,
                                        })
                                    }}
                                </span>
                            </div>
                            <p
                                v-if="typeProjet.description"
                                class="text-sm text-muted-foreground"
                            >
                                {{ typeProjet.description }}
                            </p>
                            <p
                                v-if="typeProjet.sections.length > 0"
                                class="text-xs text-muted-foreground"
                            >
                                {{ t('enseignant.gabarits.sections') }} :
                                {{
                                    typeProjet.sections
                                        .map((s) => s.label)
                                        .join(' · ')
                                }}
                            </p>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>
                        {{ t('enseignant.gabarits.schedule') }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="etapesParSemaine.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        {{ t('enseignant.gabarits.none') }}
                    </p>
                    <div
                        v-else
                        class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
                    >
                        <div
                            v-for="[semaine, etapes] in etapesParSemaine"
                            :key="semaine"
                        >
                            <p
                                class="mb-1 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                {{
                                    t('enseignant.gabarits.week', {
                                        n: semaine,
                                    })
                                }}
                            </p>
                            <ul class="list-disc space-y-0.5 pl-5 text-sm">
                                <li v-for="etape in etapes" :key="etape.id">
                                    {{ etape.etape }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
