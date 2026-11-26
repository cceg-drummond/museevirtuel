<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SectionCours from '@/components/enseignant/SectionCours.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as enseignantIndex } from '@/routes/enseignant';
import { index as coursIndex } from '@/routes/enseignant/cours';
import type { BreadcrumbItem } from '@/types';
import type { CoursEnseignant } from '@/types/enseignant';

defineProps<{ cours: CoursEnseignant[] }>();

const { t } = useI18n();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('sidebar.home'), href: enseignantIndex.url() },
    { title: t('enseignant.cours.title'), href: coursIndex.url() },
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('enseignant.cours.title')" />

        <div class="flex flex-col gap-5 p-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    {{ t('enseignant.cours.title') }}
                </h1>
                <p class="mt-1 text-muted-foreground">
                    {{ t('enseignant.cours.subtitle') }}
                </p>
            </div>

            <SectionCours :cours="cours" :repliable="false" />
        </div>
    </AppLayout>
</template>
