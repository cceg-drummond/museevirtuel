<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SectionTemoins from '@/components/enseignant/SectionTemoins.vue';
import { useFilAriane } from '@/composables/useFilAriane';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Temoin } from '@/types/enseignant';

defineProps<{
    temoinsEnAttente: Temoin[];
    temoinsApprouves: Temoin[];
    thematiques: { id: number; nom: string }[];
}>();

const { t } = useI18n();
const filAriane = useFilAriane();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    filAriane.accueil(),
    filAriane.temoins(),
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('enseignant.temoins.title')" />

        <div class="flex flex-col gap-5 p-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    {{ t('enseignant.temoins.title') }}
                </h1>
                <p class="mt-1 text-muted-foreground">
                    {{ t('enseignant.temoins.subtitle') }}
                </p>
            </div>

            <SectionTemoins
                :temoins-en-attente="temoinsEnAttente"
                :temoins-approuves="temoinsApprouves"
                :thematiques="thematiques"
                :repliable="false"
            />
        </div>
    </AppLayout>
</template>
