<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Copy } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useFilAriane } from '@/composables/useFilAriane';
import AppLayout from '@/layouts/AppLayout.vue';
import { show as gabaritsShow } from '@/routes/enseignant/gabarits';
import type { BreadcrumbItem } from '@/types';
import type { GabaritCoursResume } from '@/types/enseignant';

defineProps<{ gabarits: GabaritCoursResume[] }>();

const { t } = useI18n();
const filAriane = useFilAriane();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    filAriane.accueil(),
    filAriane.gabarits(),
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('enseignant.gabarits.title')" />

        <div class="flex flex-col gap-5 p-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    {{ t('enseignant.gabarits.title') }}
                </h1>
                <p class="mt-1 text-muted-foreground">
                    {{ t('enseignant.gabarits.subtitle') }}
                </p>
            </div>

            <p
                v-if="gabarits.length === 0"
                class="py-6 text-center text-sm text-muted-foreground"
            >
                {{ t('enseignant.gabarits.empty') }}
            </p>

            <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Card
                    v-for="gabarit in gabarits"
                    :key="gabarit.id"
                    class="flex flex-col"
                >
                    <CardHeader class="flex flex-row items-center gap-2">
                        <Copy class="h-5 w-5 shrink-0" />
                        <CardTitle class="truncate" :title="gabarit.nom">
                            {{ gabarit.nom }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent
                        class="flex-1 space-y-1 text-sm text-muted-foreground"
                    >
                        <p>
                            {{
                                t('enseignant.gabarits.objectives_count', {
                                    n: gabarit.objectifs_count,
                                })
                            }}
                            ·
                            {{
                                t('enseignant.gabarits.references_count', {
                                    n: gabarit.references_count,
                                })
                            }}
                        </p>
                        <p>
                            {{
                                t('enseignant.gabarits.steps_count', {
                                    n: gabarit.echeancier_etapes_count,
                                })
                            }}
                            ·
                            {{
                                t('enseignant.gabarits.project_types_count', {
                                    n: gabarit.types_projets_count,
                                })
                            }}
                        </p>
                    </CardContent>
                    <CardFooter>
                        <Button class="w-full" variant="outline" as-child>
                            <Link :href="gabaritsShow.url(gabarit.id)">
                                {{ t('enseignant.gabarits.open') }}
                            </Link>
                        </Button>
                    </CardFooter>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
