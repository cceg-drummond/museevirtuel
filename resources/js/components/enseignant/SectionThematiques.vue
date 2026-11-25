<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { BookOpen, ChevronDown, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import BoutonTooltip from '@/components/ui/BoutonTooltip.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { create, edit } from '@/routes/enseignant/thematiques';
import { destroy } from '@/routes/thematiques';
import type { ThematiqueEnseignant } from '@/types/enseignant';

defineProps<{ thematiques: ThematiqueEnseignant[] }>();

const { t } = useI18n();

const ouvert = ref(true);

const deleteThematiqueForm = useForm({});

/**
 * Supprime la thématique après confirmation.
 */
function deleteThematique(thematique: ThematiqueEnseignant): void {
    if (
        confirm(
            t('enseignant.index.confirm_delete_thematic', {
                nom: thematique.nom,
            }),
        )
    ) {
        deleteThematiqueForm.delete(destroy.url(thematique.id));
    }
}
</script>

<template>
    <Card>
        <CardHeader class="flex flex-row items-center justify-between">
            <button
                type="button"
                class="flex flex-1 cursor-pointer items-center gap-2 text-left select-none"
                @click="ouvert = !ouvert"
            >
                <BookOpen class="h-5 w-5" />
                <CardTitle>{{ $t('enseignant.index.my_thematic') }}</CardTitle>
                <span class="text-sm font-normal text-muted-foreground">
                    ({{ thematiques.length }})
                </span>
                <ChevronDown
                    class="h-4 w-4 text-muted-foreground transition-transform"
                    :class="{ '-rotate-180': ouvert }"
                />
            </button>
            <Button size="sm" as-child>
                <Link :href="create.url()">
                    <Plus class="mr-2 h-4 w-4" />
                    {{ $t('enseignant.index.new_thematic') }}
                </Link>
            </Button>
        </CardHeader>
        <CardContent v-show="ouvert">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="pr-4 pb-3 font-medium">
                                {{
                                    $t(
                                        'enseignant.index.table_header_thematic_name',
                                    )
                                }}
                            </th>
                            <th class="pr-4 pb-3 font-medium">
                                {{
                                    $t(
                                        'enseignant.index.table_header_historical_period',
                                    )
                                }}
                            </th>
                            <th class="pr-4 pb-3 font-medium">
                                {{
                                    $t(
                                        'enseignant.index.table_header_description',
                                    )
                                }}
                            </th>
                            <th class="pb-3 font-medium">
                                {{
                                    $t('enseignant.index.table_header_actions')
                                }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="thematique in thematiques"
                            :key="thematique.id"
                            class="border-b last:border-0"
                        >
                            <td class="py-3 pr-4 font-medium">
                                {{ thematique.nom }}
                            </td>
                            <td class="py-3 pr-4 text-muted-foreground">
                                {{ thematique.periode_historique ?? '—' }}
                            </td>
                            <td
                                class="max-w-xs truncate py-3 pr-4 text-muted-foreground"
                            >
                                {{ thematique.description ?? '—' }}
                            </td>
                            <td class="py-3">
                                <div class="flex gap-2">
                                    <BoutonTooltip
                                        texte="Modifier cette thématique"
                                        size="sm"
                                        variant="outline"
                                        as-child
                                    >
                                        <Link :href="edit.url(thematique.id)">
                                            <Pencil class="h-4 w-4" />
                                        </Link>
                                    </BoutonTooltip>
                                    <BoutonTooltip
                                        texte="Supprimer cette thématique"
                                        size="sm"
                                        variant="destructive"
                                        @click="deleteThematique(thematique)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </BoutonTooltip>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="thematiques.length === 0">
                            <td
                                colspan="4"
                                class="py-6 text-center text-muted-foreground"
                            >
                                {{ $t('enseignant.index.no_thematic') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </CardContent>
    </Card>
</template>
