<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FileQuestion } from 'lucide-vue-next';
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
import { show as questionsShow } from '@/routes/enseignant/questions';
import type { BreadcrumbItem } from '@/types';
import type { BanqueQuestions } from '@/types/enseignant';

defineProps<{
    banques: (BanqueQuestions & { questions_count: number })[];
}>();

const { t } = useI18n();
const filAriane = useFilAriane();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    filAriane.accueil(),
    filAriane.questions(),
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('enseignant.questions.title')" />

        <div class="flex flex-col gap-5 p-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    {{ t('enseignant.questions.title') }}
                </h1>
                <p class="mt-1 text-muted-foreground">
                    {{ t('enseignant.questions.subtitle') }}
                </p>
            </div>

            <p
                v-if="banques.length === 0"
                class="py-6 text-center text-sm text-muted-foreground"
            >
                {{ t('enseignant.questions.empty') }}
            </p>

            <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Card
                    v-for="banque in banques"
                    :key="banque.id"
                    class="flex flex-col"
                >
                    <CardHeader class="flex flex-row items-center gap-2">
                        <FileQuestion class="h-5 w-5 shrink-0" />
                        <CardTitle class="font-mono text-sm">
                            {{ banque.cours.code }}-{{ banque.cours.groupe }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="flex-1 space-y-1">
                        <p class="truncate font-semibold" :title="banque.label">
                            {{ banque.label }}
                        </p>
                        <p
                            class="truncate text-sm text-muted-foreground"
                            :title="banque.type_projet.nom"
                        >
                            {{ banque.type_projet.nom }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{
                                t('enseignant.questions.count', {
                                    n: banque.questions_count,
                                })
                            }}
                        </p>
                    </CardContent>
                    <CardFooter>
                        <Button class="w-full" variant="outline" as-child>
                            <Link :href="questionsShow.url(banque.id)">
                                {{ t('enseignant.questions.open') }}
                            </Link>
                        </Button>
                    </CardFooter>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
