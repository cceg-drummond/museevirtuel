<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays } from 'lucide-vue-next';
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
import { show as echeancierShow } from '@/routes/enseignant/echeancier';
import type { BreadcrumbItem } from '@/types';
import type { CoursEcheancier } from '@/types/enseignant';

defineProps<{ cours: CoursEcheancier[] }>();

const { t } = useI18n();
const filAriane = useFilAriane();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    filAriane.accueil(),
    filAriane.echeancier(),
]);
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

            <p
                v-if="cours.length === 0"
                class="py-6 text-center text-sm text-muted-foreground"
            >
                {{ t('enseignant.echeancier.empty') }}
            </p>

            <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Card v-for="c in cours" :key="c.id" class="flex flex-col">
                    <CardHeader class="flex flex-row items-center gap-2">
                        <CalendarDays class="h-5 w-5 shrink-0" />
                        <CardTitle class="font-mono text-sm">
                            {{ c.code }}-{{ c.groupe }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="flex-1 space-y-1">
                        <p class="truncate font-semibold" :title="c.nom_cours">
                            {{ c.nom_cours }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{
                                c.etapes_count === 0
                                    ? t('enseignant.echeancier.no_step')
                                    : t('enseignant.echeancier.steps', {
                                          n: c.etapes_terminees_count,
                                          total: c.etapes_count,
                                      })
                            }}
                        </p>
                    </CardContent>
                    <CardFooter>
                        <Button class="w-full" variant="outline" as-child>
                            <Link :href="echeancierShow.url(c.id)">
                                {{ t('enseignant.echeancier.open') }}
                            </Link>
                        </Button>
                    </CardFooter>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
