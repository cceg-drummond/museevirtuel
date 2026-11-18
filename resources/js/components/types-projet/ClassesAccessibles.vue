<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { libelleClasse } from './acces';
import type { ClasseAcces, TypeProjetAcces } from './acces';

/**
 * Section listant les classes qui ont accès au type de projet.
 */
const props = defineProps<{
    typeProjet: TypeProjetAcces;
    classes: ClasseAcces[];
}>();

const { t } = useI18n();

const classesAccessibles = computed(() =>
    props.classes.filter((classe) =>
        props.typeProjet.classes.includes(classe.id),
    ),
);
</script>

<template>
    <div>
        <p class="mb-2 text-xs font-medium text-muted-foreground">
            {{ $t('types_projet.acces.section_title') }}
        </p>

        <p v-if="typeProjet.accessible" class="text-xs">
            {{ $t('types_projet.acces.all_classes') }}
        </p>

        <div
            v-else-if="classesAccessibles.length > 0"
            class="flex flex-wrap gap-1.5"
        >
            <span
                v-for="classe in classesAccessibles"
                :key="classe.id"
                class="rounded-md bg-green-100 px-2 py-0.5 text-xs text-green-800 dark:bg-green-900/30 dark:text-green-400"
            >
                {{ libelleClasse(classe, t) }}
            </span>
        </div>

        <p v-else class="text-xs text-muted-foreground italic">
            {{ $t('types_projet.acces.none') }}
        </p>
    </div>
</template>
