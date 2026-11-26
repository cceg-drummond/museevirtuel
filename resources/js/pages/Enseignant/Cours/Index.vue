<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SectionCours from '@/components/enseignant/SectionCours.vue';
import { useFilAriane } from '@/composables/useFilAriane';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { CoursEnseignant } from '@/types/enseignant';

defineProps<{ cours: CoursEnseignant[] }>();

const { t } = useI18n();
const filAriane = useFilAriane();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    filAriane.accueil(),
    filAriane.cours(),
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
