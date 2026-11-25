<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import {
    ChevronDown,
    ExternalLink,
    Lock,
    Pencil,
    Plus,
    Trash2,
    Unlock,
} from 'lucide-vue-next';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import BoutonTooltip from '@/components/ui/BoutonTooltip.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { destroy, show } from '@/routes/cours';
import { toggle as toggleVerrouillageRoute } from '@/routes/cours/verrouillage';
import { create, edit } from '@/routes/enseignant/cours';
import type { CoursEnseignant } from '@/types/enseignant';

defineProps<{ cours: CoursEnseignant[] }>();

const { t } = useI18n();

const ouvert = ref(true);

const SESSIONS: Record<CoursEnseignant['session'], string> = {
    hiver: 'Hiver',
    ete: 'Été',
    automne: 'Automne',
};

const deleteCoursForm = useForm({});
const verrouillageForm = useForm({});

/**
 * Supprime le cours après confirmation.
 */
function deleteCours(unCours: CoursEnseignant): void {
    if (
        confirm(
            t('enseignant.index.confirm_delete_course', {
                nom: unCours.nom_cours,
            }),
        )
    ) {
        deleteCoursForm.delete(destroy.url(unCours.id));
    }
}

/**
 * Verrouille ou déverrouille le cours pour les étudiants.
 */
function toggleVerrouillage(unCours: CoursEnseignant): void {
    verrouillageForm.patch(toggleVerrouillageRoute.url(unCours.id));
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
                <CardTitle>{{ $t('enseignant.index.my_courses') }}</CardTitle>
                <span class="text-sm font-normal text-muted-foreground">
                    ({{ cours.length }})
                </span>
                <ChevronDown
                    class="h-4 w-4 text-muted-foreground transition-transform"
                    :class="{ '-rotate-180': ouvert }"
                />
            </button>
            <Button size="sm" as-child>
                <Link :href="create.url()">
                    <Plus class="mr-2 h-4 w-4" />
                    {{ $t('enseignant.index.new_course') }}
                </Link>
            </Button>
        </CardHeader>
        <CardContent v-show="ouvert">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="pr-4 pb-3 font-medium">
                                {{ $t('enseignant.index.table_header_code') }}
                            </th>
                            <th class="pr-4 pb-3 font-medium">
                                {{ $t('enseignant.index.table_header_group') }}
                            </th>
                            <th class="pr-4 pb-3 font-medium">Session</th>
                            <th class="pr-4 pb-3 font-medium">
                                {{
                                    $t(
                                        'enseignant.index.table_header_course_name',
                                    )
                                }}
                            </th>
                            <th class="pr-4 pb-3 text-center font-medium">
                                {{
                                    $t('enseignant.index.table_header_students')
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
                            v-for="unCours in cours"
                            :key="unCours.id"
                            class="border-b last:border-0"
                        >
                            <td class="py-3 pr-4 font-mono text-xs">
                                {{ unCours.code }}
                            </td>
                            <td class="py-3 pr-4 font-mono text-xs">
                                {{ unCours.groupe }}
                            </td>
                            <td class="py-3 pr-4 text-xs text-muted-foreground">
                                {{
                                    SESSIONS[unCours.session] ?? unCours.session
                                }}
                                {{ unCours.annee }}
                            </td>
                            <td class="py-3 pr-4">{{ unCours.nom_cours }}</td>
                            <td class="py-3 pr-4 text-center">
                                {{ unCours.etudiants_count }}
                            </td>
                            <td class="py-3">
                                <div class="flex gap-2">
                                    <BoutonTooltip
                                        texte="Accéder au détail de ce cours"
                                        size="sm"
                                        variant="outline"
                                        as-child
                                    >
                                        <Link :href="show.url(unCours.id)">
                                            <ExternalLink class="h-4 w-4" />
                                        </Link>
                                    </BoutonTooltip>
                                    <BoutonTooltip
                                        texte="Modifier les informations de ce cours"
                                        size="sm"
                                        variant="outline"
                                        as-child
                                    >
                                        <Link :href="edit.url(unCours.id)">
                                            <Pencil class="h-4 w-4" />
                                        </Link>
                                    </BoutonTooltip>
                                    <BoutonTooltip
                                        :texte="
                                            unCours.is_verrouille
                                                ? 'Déverrouiller — rendre accessible aux étudiants'
                                                : 'Verrouiller — masquer aux étudiants'
                                        "
                                        size="sm"
                                        :variant="
                                            unCours.is_verrouille
                                                ? 'default'
                                                : 'outline'
                                        "
                                        @click="toggleVerrouillage(unCours)"
                                    >
                                        <Lock
                                            v-if="unCours.is_verrouille"
                                            class="h-4 w-4"
                                        />
                                        <Unlock v-else class="h-4 w-4" />
                                    </BoutonTooltip>
                                    <BoutonTooltip
                                        texte="Supprimer ce cours définitivement"
                                        size="sm"
                                        variant="destructive"
                                        @click="deleteCours(unCours)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </BoutonTooltip>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="cours.length === 0">
                            <td
                                colspan="6"
                                class="py-6 text-center text-muted-foreground"
                            >
                                {{ $t('enseignant.index.no_courses') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </CardContent>
    </Card>
</template>
