<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ExternalLink, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import BoutonTooltip from '@/components/ui/BoutonTooltip.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useFilAriane } from '@/composables/useFilAriane';
import AppLayout from '@/layouts/AppLayout.vue';
import { show as groupeShow } from '@/routes/groupes';
import type { BreadcrumbItem } from '@/types';
import type { GroupeEnseignant } from '@/types/enseignant';

defineProps<{ groupes: GroupeEnseignant[] }>();

const { t } = useI18n();
const filAriane = useFilAriane();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    filAriane.accueil(),
    filAriane.groupes(),
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('enseignant.groupes.title')" />

        <div class="flex flex-col gap-5 p-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    {{ t('enseignant.groupes.title') }}
                </h1>
                <p class="mt-1 text-muted-foreground">
                    {{ t('enseignant.groupes.subtitle') }}
                </p>
            </div>

            <Card>
                <CardHeader class="flex flex-row items-center gap-2">
                    <Users class="h-5 w-5" />
                    <CardTitle>{{ t('enseignant.groupes.title') }}</CardTitle>
                    <span class="text-sm text-muted-foreground">
                        ({{ groupes.length }})
                    </span>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left">
                                    <th class="pr-4 pb-3 font-medium">
                                        {{
                                            t('enseignant.groupes.header_group')
                                        }}
                                    </th>
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
                                    <th
                                        class="pr-4 pb-3 text-center font-medium"
                                    >
                                        {{
                                            t(
                                                'enseignant.groupes.header_members',
                                            )
                                        }}
                                    </th>
                                    <th
                                        class="pr-4 pb-3 text-center font-medium"
                                    >
                                        {{
                                            t(
                                                'enseignant.groupes.header_projects',
                                            )
                                        }}
                                    </th>
                                    <th class="pr-4 pb-3 font-medium">
                                        {{
                                            t('enseignant.groupes.header_elder')
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
                                    v-for="groupe in groupes"
                                    :key="groupe.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-3 pr-4 font-medium">
                                        {{
                                            t('classes.groupes.group_number', {
                                                n: groupe.numero,
                                            })
                                        }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        <span class="font-mono text-xs">
                                            {{ groupe.cours.code }}-{{
                                                groupe.cours.groupe
                                            }}
                                        </span>
                                        <span
                                            class="ml-1 text-muted-foreground"
                                        >
                                            {{ groupe.cours.nom_cours }}
                                        </span>
                                    </td>
                                    <td class="py-3 pr-4 text-muted-foreground">
                                        {{
                                            groupe.classe.nom ||
                                            t('breadcrumbs.section', {
                                                n: groupe.classe.numero,
                                            })
                                        }}
                                    </td>
                                    <td class="py-3 pr-4 text-center">
                                        {{ groupe.membres_count }}
                                    </td>
                                    <td class="py-3 pr-4 text-center">
                                        {{ groupe.projets_count }}
                                    </td>
                                    <td class="py-3 pr-4 whitespace-nowrap">
                                        <span v-if="groupe.temoin">
                                            {{ groupe.temoin.prenom }}
                                            {{ groupe.temoin.nom }}
                                        </span>
                                        <span
                                            v-else
                                            class="text-muted-foreground"
                                        >
                                            {{
                                                t('enseignant.groupes.no_elder')
                                            }}
                                        </span>
                                    </td>
                                    <td class="py-3">
                                        <BoutonTooltip
                                            :texte="
                                                t('enseignant.groupes.open')
                                            "
                                            size="sm"
                                            variant="outline"
                                            as-child
                                        >
                                            <Link
                                                :href="
                                                    groupeShow.url({
                                                        cours: groupe.cours.id,
                                                        classe: groupe.classe
                                                            .id,
                                                        groupe: groupe.id,
                                                    })
                                                "
                                            >
                                                <ExternalLink class="h-4 w-4" />
                                            </Link>
                                        </BoutonTooltip>
                                    </td>
                                </tr>
                                <tr v-if="groupes.length === 0">
                                    <td
                                        colspan="7"
                                        class="py-6 text-center text-muted-foreground"
                                    >
                                        {{ t('enseignant.groupes.empty') }}
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
