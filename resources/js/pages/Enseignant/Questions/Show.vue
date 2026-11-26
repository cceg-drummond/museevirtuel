<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FileQuestion } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useFilAriane } from '@/composables/useFilAriane';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit as typeProjetEdit } from '@/routes/types-projets';
import type { BreadcrumbItem } from '@/types';
import type { BanqueQuestions } from '@/types/enseignant';

const props = defineProps<{
    banque: BanqueQuestions & { questions: { id: number; contenu: string }[] };
}>();

const { t } = useI18n();
const filAriane = useFilAriane();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    filAriane.accueil(),
    filAriane.questions(),
    filAriane.courant(props.banque.label),
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="banque.label" />

        <div class="flex flex-col gap-5 p-6">
            <Card>
                <CardHeader class="flex flex-row flex-wrap items-center gap-2">
                    <FileQuestion class="h-5 w-5 shrink-0" />
                    <div class="min-w-0 flex-1">
                        <CardTitle class="truncate">{{
                            banque.label
                        }}</CardTitle>
                        <p class="truncate text-sm text-muted-foreground">
                            <span class="font-mono text-xs">
                                {{ banque.cours.code }}-{{
                                    banque.cours.groupe
                                }}
                            </span>
                            · {{ banque.type_projet.nom }}
                        </p>
                    </div>
                    <Button size="sm" variant="outline" as-child>
                        <Link
                            :href="
                                typeProjetEdit.url({
                                    cours: banque.cours.id,
                                    typeProjet: banque.type_projet.id,
                                })
                            "
                        >
                            {{ t('enseignant.questions.manage') }}
                        </Link>
                    </Button>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="banque.questions.length === 0"
                        class="py-2 text-sm text-muted-foreground"
                    >
                        {{ t('enseignant.questions.no_question') }}
                    </p>
                    <ol v-else class="list-decimal space-y-2 pl-5 text-sm">
                        <li
                            v-for="question in banque.questions"
                            :key="question.id"
                        >
                            {{ question.contenu }}
                        </li>
                    </ol>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
