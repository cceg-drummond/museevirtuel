<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import type { StatutProjet } from '@/types/enseignant';

const props = defineProps<{ statuts: StatutProjet[] }>();

const { t } = useI18n();

const COULEURS: Record<StatutProjet['valeur'], string> = {
    en_cours: '#3b82f6',
    remis: '#16a34a',
    en_retard: '#dc2626',
    remis_en_retard: '#f59e0b',
};

const RAYON = 54;
const CIRCONFERENCE = 2 * Math.PI * RAYON;

const total = computed(() => props.statuts.reduce((s, st) => s + st.total, 0));

// Chaque arc commence là où le précédent s'arrête ; 2 px d'écart les séparent visuellement
const arcs = computed(() => {
    let decalage = 0;

    return props.statuts
        .filter((st) => st.total > 0)
        .map((st) => {
            const longueur = (st.total / total.value) * CIRCONFERENCE;
            const arc = {
                valeur: st.valeur,
                couleur: COULEURS[st.valeur],
                dasharray: `${Math.max(longueur - 2, 0)} ${CIRCONFERENCE - longueur + 2}`,
                dashoffset: -decalage,
            };
            decalage += longueur;

            return arc;
        });
});
</script>

<template>
    <div class="flex flex-wrap items-center gap-6">
        <svg viewBox="0 0 140 140" class="size-36 shrink-0" role="img">
            <circle
                cx="70"
                cy="70"
                :r="RAYON"
                fill="none"
                class="stroke-muted"
                stroke-width="16"
            />
            <circle
                v-for="arc in arcs"
                :key="arc.valeur"
                cx="70"
                cy="70"
                :r="RAYON"
                fill="none"
                :stroke="arc.couleur"
                stroke-width="16"
                :stroke-dasharray="arc.dasharray"
                :stroke-dashoffset="arc.dashoffset"
                transform="rotate(-90 70 70)"
            />
            <text
                x="70"
                y="68"
                text-anchor="middle"
                class="fill-foreground text-[26px] font-bold"
            >
                {{ total }}
            </text>
            <text
                x="70"
                y="88"
                text-anchor="middle"
                class="fill-muted-foreground text-[11px]"
            >
                {{ t('enseignant.index.dashboard.projects_unit') }}
            </text>
        </svg>
        <ul class="flex min-w-40 flex-1 flex-col gap-2 text-sm">
            <li
                v-for="statut in statuts"
                :key="statut.valeur"
                class="flex items-center gap-2"
            >
                <i
                    class="inline-block size-2.5 rounded-sm"
                    :style="{ background: COULEURS[statut.valeur] }"
                />
                {{ statut.libelle }}
                <b class="ml-auto tabular-nums">{{ statut.total }}</b>
            </li>
        </ul>
    </div>
</template>
