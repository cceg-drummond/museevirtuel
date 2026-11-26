<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ChevronDown, Search, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { show as showTemoin } from '@/routes/enseignant/temoins';
import type { Temoin } from '@/types/enseignant';

const props = withDefaults(
    defineProps<{
        temoinsEnAttente: Temoin[];
        temoinsApprouves: Temoin[];
        thematiques: { id: number; nom: string }[];
        repliable?: boolean;
    }>(),
    { repliable: true },
);

// Replié par défaut sur l'accueil : les témoins en attente y sont déjà mis en avant
const ouvert = ref(!props.repliable);

/**
 * Replie ou déplie le tableau, seulement s'il est repliable.
 */
function basculer(): void {
    if (props.repliable) {
        ouvert.value = !ouvert.value;
    }
}
const afficherApprouves = ref(false);
const rechercheTexte = ref('');
const filtreThematiqueId = ref<number | null>(null);

const temoinsFiltres = computed(() => {
    const texte = rechercheTexte.value.trim().toLowerCase();
    const liste = afficherApprouves.value
        ? props.temoinsApprouves
        : props.temoinsEnAttente;

    return liste.filter((temoin) => {
        const correspondTexte =
            texte === '' ||
            `${temoin.prenom} ${temoin.nom} ${temoin.email}`
                .toLowerCase()
                .includes(texte);
        const correspondThematique =
            filtreThematiqueId.value === null ||
            temoin.thematiques_choisies.some(
                (th) => th.id === filtreThematiqueId.value,
            );

        return correspondTexte && correspondThematique;
    });
});

/**
 * Ouvre la fiche du témoin.
 */
function voirTemoin(temoin: Temoin): void {
    router.visit(showTemoin.url(temoin.id));
}
</script>

<template>
    <Card>
        <CardHeader class="flex flex-row items-center justify-between">
            <button
                type="button"
                class="flex flex-1 items-center gap-2 text-left select-none"
                :class="{ 'cursor-pointer': repliable }"
                @click="basculer"
            >
                <Users class="h-5 w-5" />
                <CardTitle>{{
                    $t('administration.index.temoins_table')
                }}</CardTitle>
                <span class="text-sm font-normal text-muted-foreground">
                    ({{ temoinsFiltres.length }})
                </span>
                <ChevronDown
                    v-if="repliable"
                    class="h-4 w-4 text-muted-foreground transition-transform"
                    :class="{ '-rotate-180': ouvert }"
                />
            </button>
            <label class="flex cursor-pointer items-center gap-2 text-sm">
                <span class="text-muted-foreground">{{
                    $t('enseignant.index.temoins_show_approved')
                }}</span>
                <button
                    type="button"
                    role="switch"
                    :aria-checked="afficherApprouves"
                    class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    :class="afficherApprouves ? 'bg-green-500' : 'bg-input'"
                    @click="afficherApprouves = !afficherApprouves"
                >
                    <span
                        class="inline-block h-3 w-3 rounded-full bg-white shadow-sm transition-transform"
                        :class="
                            afficherApprouves
                                ? 'translate-x-5'
                                : 'translate-x-1'
                        "
                    />
                </button>
            </label>
        </CardHeader>
        <CardContent v-show="ouvert">
            <div class="mb-4 flex flex-wrap gap-3">
                <div class="relative min-w-48 flex-1">
                    <Search
                        class="absolute top-2.5 left-2.5 h-4 w-4 text-muted-foreground"
                    />
                    <Input
                        v-model="rechercheTexte"
                        class="pl-8"
                        :placeholder="
                            $t(
                                'enseignant.index.temoins_filter_search_placeholder',
                            )
                        "
                    />
                </div>
                <select
                    v-model="filtreThematiqueId"
                    class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                >
                    <option :value="null">
                        {{
                            $t(
                                'enseignant.index.temoins_filter_all_thematiques',
                            )
                        }}
                    </option>
                    <option
                        v-for="th in thematiques"
                        :key="th.id"
                        :value="th.id"
                    >
                        {{ th.nom }}
                    </option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="pr-4 pb-3 font-medium">
                                {{
                                    $t(
                                        'administration.index.temoins_header_first_name',
                                    )
                                }}
                            </th>
                            <th class="pr-4 pb-3 font-medium">
                                {{
                                    $t(
                                        'administration.index.temoins_header_last_name',
                                    )
                                }}
                            </th>
                            <th class="pr-4 pb-3 font-medium">
                                {{
                                    $t(
                                        'administration.index.temoins_header_email',
                                    )
                                }}
                            </th>
                            <th class="pb-3 font-medium">
                                {{
                                    $t(
                                        'administration.index.temoins_header_theme',
                                    )
                                }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="temoin in temoinsFiltres"
                            :key="temoin.id"
                            class="cursor-pointer border-b last:border-0 hover:bg-muted/50"
                            @click="voirTemoin(temoin)"
                        >
                            <td class="py-3 pr-4">{{ temoin.prenom }}</td>
                            <td class="py-3 pr-4">{{ temoin.nom }}</td>
                            <td class="py-3 pr-4 text-muted-foreground">
                                {{ temoin.email }}
                            </td>
                            <td class="py-3">
                                <span v-if="temoin.thematiques_choisies.length">
                                    {{
                                        temoin.thematiques_choisies
                                            .map((th) => th.nom)
                                            .join(', ')
                                    }}
                                </span>
                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
                            </td>
                        </tr>
                        <tr v-if="temoinsFiltres.length === 0">
                            <td
                                colspan="4"
                                class="py-6 text-center text-muted-foreground"
                            >
                                {{
                                    afficherApprouves
                                        ? $t(
                                              'enseignant.index.temoins_no_approved',
                                          )
                                        : $t(
                                              'administration.index.temoins_no_pending',
                                          )
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </CardContent>
    </Card>
</template>
