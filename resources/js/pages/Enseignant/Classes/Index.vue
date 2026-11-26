<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ExternalLink, School } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import BoutonTooltip from '@/components/ui/BoutonTooltip.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useFilAriane } from '@/composables/useFilAriane';
import AppLayout from '@/layouts/AppLayout.vue';
import { show as classeShow } from '@/routes/classes';
import type { BreadcrumbItem } from '@/types';
import type { ClasseEnseignant } from '@/types/enseignant';

defineProps<{ classes: ClasseEnseignant[] }>();

const { t } = useI18n();
const filAriane = useFilAriane();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    filAriane.accueil(),
    filAriane.classes(),
]);

const SESSIONS: Record<ClasseEnseignant['cours']['session'], string> = {
    hiver: 'Hiver',
    ete: 'Été',
    automne: 'Automne',
};

/**
 * Retourne l'horaire lisible d'une classe, ou un tiret s'il n'est pas renseigné.
 */
function horaire(classe: ClasseEnseignant): string {
    return (
        [classe.jour_semaine, classe.plage_horaire]
            .filter(Boolean)
            .join(' · ') || '—'
    );
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('enseignant.classes.title')" />

        <div class="flex flex-col gap-5 p-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    {{ t('enseignant.classes.title') }}
                </h1>
                <p class="mt-1 text-muted-foreground">
                    {{ t('enseignant.classes.subtitle') }}
                </p>
            </div>

            <Card>
                <CardHeader class="flex flex-row items-center gap-2">
                    <School class="h-5 w-5" />
                    <CardTitle>{{ t('enseignant.classes.title') }}</CardTitle>
                    <span class="text-sm text-muted-foreground">
                        ({{ classes.length }})
                    </span>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left">
                                    <th class="pr-4 pb-3 font-medium">
                                        {{
                                            t(
                                                'enseignant.classes.header_course',
                                            )
                                        }}
                                    </th>
                                    <th class="pr-4 pb-3 font-medium">
                                        {{
                                            t('enseignant.classes.header_class')
                                        }}
                                    </th>
                                    <th class="pr-4 pb-3 font-medium">
                                        Session
                                    </th>
                                    <th class="pr-4 pb-3 font-medium">
                                        {{
                                            t(
                                                'enseignant.classes.header_schedule',
                                            )
                                        }}
                                    </th>
                                    <th
                                        class="pr-4 pb-3 text-center font-medium"
                                    >
                                        {{
                                            t(
                                                'enseignant.index.table_header_students',
                                            )
                                        }}
                                    </th>
                                    <th
                                        class="pr-4 pb-3 text-center font-medium"
                                    >
                                        {{
                                            t('enseignant.cours.header_groups')
                                        }}
                                    </th>
                                    <th class="pb-3 font-medium">
                                        {{
                                            t(
                                                'enseignant.index.table_header_actions',
                                            )
                                        }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="classe in classes"
                                    :key="classe.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-3 pr-4">
                                        <span class="font-mono text-xs">
                                            {{ classe.cours.code }}-{{
                                                classe.cours.groupe
                                            }}
                                        </span>
                                        <span
                                            class="ml-1 text-muted-foreground"
                                        >
                                            {{ classe.cours.nom_cours }}
                                        </span>
                                    </td>
                                    <td class="py-3 pr-4 font-medium">
                                        {{
                                            classe.nom ||
                                            t('breadcrumbs.section', {
                                                n: classe.numero,
                                            })
                                        }}
                                    </td>
                                    <td
                                        class="py-3 pr-4 text-xs whitespace-nowrap text-muted-foreground"
                                    >
                                        {{
                                            SESSIONS[classe.cours.session] ??
                                            classe.cours.session
                                        }}
                                        {{ classe.cours.annee }}
                                    </td>
                                    <td class="py-3 pr-4 text-muted-foreground">
                                        {{ horaire(classe) }}
                                    </td>
                                    <td class="py-3 pr-4 text-center">
                                        {{ classe.etudiants_count }}
                                    </td>
                                    <td class="py-3 pr-4 text-center">
                                        {{ classe.groupes_count }}
                                    </td>
                                    <td class="py-3">
                                        <BoutonTooltip
                                            :texte="
                                                t('enseignant.classes.open')
                                            "
                                            size="sm"
                                            variant="outline"
                                            as-child
                                        >
                                            <Link
                                                :href="
                                                    classeShow.url({
                                                        cours: classe.cours.id,
                                                        classe: classe.id,
                                                    })
                                                "
                                            >
                                                <ExternalLink class="h-4 w-4" />
                                            </Link>
                                        </BoutonTooltip>
                                    </td>
                                </tr>
                                <tr v-if="classes.length === 0">
                                    <td
                                        colspan="7"
                                        class="py-6 text-center text-muted-foreground"
                                    >
                                        {{ t('enseignant.classes.empty') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
