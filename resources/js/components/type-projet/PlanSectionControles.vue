<script setup lang="ts">
import { IndentDecrease, IndentIncrease, Plus } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import {
    peutAvoirEnfant,
    peutDescendre,
    peutRemonter,
} from '@/lib/planSections';
import type { ElementPlan } from '@/lib/planSections';

/**
 * Barre d'actions d'un élément du plan : niveau, numéro, changement de niveau et ajout d'enfant.
 *
 * Le composant ne modifie pas le plan : il émet l'action et la page l'applique.
 */
const props = defineProps<{
    plan: ElementPlan[];
    index: number;
    numero: string;
}>();

const emit = defineEmits<{
    remonter: [];
    descendre: [];
    ajouterEnfant: [];
}>();

const { t } = useI18n();

const niveau = computed(() => props.plan[props.index].niveau);
</script>

<template>
    <div class="flex flex-wrap items-center gap-1">
        <span class="mr-auto text-xs font-medium text-muted-foreground">
            {{ t(`types_projet.edit.niveau_${niveau}`) }} {{ numero }}
        </span>
        <Button
            type="button"
            size="icon"
            variant="ghost"
            class="h-7 w-7 text-muted-foreground"
            :title="t('types_projet.edit.remonter_niveau')"
            :aria-label="t('types_projet.edit.remonter_niveau')"
            :disabled="!peutRemonter(props.plan, props.index)"
            @click="emit('remonter')"
        >
            <IndentDecrease class="h-4 w-4" />
        </Button>
        <Button
            type="button"
            size="icon"
            variant="ghost"
            class="h-7 w-7 text-muted-foreground"
            :title="t('types_projet.edit.descendre_niveau')"
            :aria-label="t('types_projet.edit.descendre_niveau')"
            :disabled="!peutDescendre(props.plan, props.index)"
            @click="emit('descendre')"
        >
            <IndentIncrease class="h-4 w-4" />
        </Button>
        <Button
            v-if="peutAvoirEnfant(props.plan, props.index)"
            type="button"
            size="sm"
            variant="ghost"
            class="h-7 text-xs text-muted-foreground"
            @click="emit('ajouterEnfant')"
        >
            <Plus class="mr-1 h-3.5 w-3.5" />
            {{ t(`types_projet.edit.ajouter_enfant_${niveau + 1}`) }}
        </Button>
    </div>
</template>
