<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SectionThematiques from '@/components/enseignant/SectionThematiques.vue';
import { useFilAriane } from '@/composables/useFilAriane';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { ThematiqueEnseignant } from '@/types/enseignant';

defineProps<{ thematiques: ThematiqueEnseignant[] }>();

const { t } = useI18n();
const filAriane = useFilAriane();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    filAriane.accueil(),
    filAriane.thematiques(),
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('enseignant.thematiques.title')" />

        <div class="flex flex-col gap-5 p-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    {{ t('enseignant.thematiques.title') }}
                </h1>
                <p class="mt-1 text-muted-foreground">
                    {{ t('enseignant.thematiques.subtitle') }}
                </p>
            </div>

            <SectionThematiques :thematiques="thematiques" :repliable="false" />
        </div>
    </AppLayout>
</template>
