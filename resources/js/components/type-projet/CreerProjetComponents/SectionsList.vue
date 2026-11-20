<script setup lang="ts">
import { Plus, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/shared/InputError.vue';
import PlanSectionControles from '@/components/type-projet/PlanSectionControles.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    ajouterEnfant,
    decalerBloc,
    NIVEAU_CHAPITRE,
    numerotation,
    supprimerBloc,
} from '@/lib/planSections';
import type { NiveauSection } from '@/lib/planSections';

type SectionType = 'texte' | 'paragraphes' | 'individuel' | 'entrevue';

type SectionFormItem = {
    label: string;
    description: string;
    type: SectionType;
    niveau: NiveauSection;
};

type SectionTypeOption = {
    value: SectionType;
    label: string;
    description: string;
};

/**
 * Plan du type de projet à la création : chapitres, sections et sous-sections.
 *
 * Le plan est lié au formulaire parent par v-model:sections et modifié en place.
 */
const props = defineProps<{
    errors: Record<string, string | undefined>;
    sectionTypes: SectionTypeOption[];
}>();

const sections = defineModel<SectionFormItem[]>('sections', { required: true });

const { t } = useI18n();

const numeros = computed(() => numerotation(sections.value));

/** Décalage visuel par niveau : la hiérarchie se lit d'un coup d'œil. */
const retraits: Record<NiveauSection, string> = {
    0: '',
    1: 'ml-6',
    2: 'ml-12',
};

/**
 * Retourne un élément vide du plan (sans niveau, fixé par l'appelant).
 */
function elementVide(): Omit<SectionFormItem, 'niveau'> {
    return { label: '', description: '', type: 'texte' };
}

/**
 * Ajoute un chapitre vide à la fin du plan.
 */
function ajouterChapitre() {
    sections.value.push({ ...elementVide(), niveau: NIVEAU_CHAPITRE });
}
</script>

<template>
    <div class="flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold">
                    {{ t('types_projet.edit.sections_title') }}
                </h2>
                <p class="text-xs text-muted-foreground">
                    {{ t('types_projet.edit.sections_hint') }}
                </p>
            </div>
        </div>

        <InputError :message="props.errors.sections" />

        <Card v-if="sections.length === 0">
            <CardContent class="py-8 text-center text-sm text-muted-foreground">
                {{ t('types_projet.edit.no_sections') }}
            </CardContent>
        </Card>

        <Card
            v-for="(section, idx) in sections"
            :key="`new-${idx}`"
            :class="['border', retraits[section.niveau]]"
        >
            <CardContent class="grid gap-4 pt-5">
                <PlanSectionControles
                    :plan="sections"
                    :index="idx"
                    :numero="numeros[idx]"
                    @remonter="decalerBloc(sections, idx, -1)"
                    @descendre="decalerBloc(sections, idx, 1)"
                    @ajouter-enfant="
                        ajouterEnfant(sections, idx, elementVide())
                    "
                />
                <div class="flex items-start gap-2">
                    <div class="flex-1 space-y-1.5">
                        <Input
                            v-model="section.label"
                            :placeholder="
                                t(
                                    `types_projet.edit.titre_placeholder_${section.niveau}`,
                                )
                            "
                            required
                        />
                        <InputError
                            :message="props.errors[`sections.${idx}.label`]"
                        />
                        <Input
                            v-model="section.description"
                            :placeholder="
                                t(
                                    'types_projet.edit.section_instruction_placeholder',
                                )
                            "
                        />
                    </div>
                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        class="mt-0.5 h-8 w-8 shrink-0 text-muted-foreground hover:text-destructive"
                        :aria-label="
                            t('types_projet.edit.confirm_delete_section_titre')
                        "
                        @click="supprimerBloc(sections, idx)"
                    >
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>

                <div>
                    <p class="mb-2 text-xs font-medium text-muted-foreground">
                        {{ t('types_projet.edit.input_mode_label') }}
                    </p>
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            v-for="sectionType in props.sectionTypes"
                            :key="sectionType.value"
                            type="button"
                            :class="[
                                'flex flex-col rounded-md border px-3 py-2.5 text-left text-xs transition-colors',
                                section.type === sectionType.value
                                    ? 'border-primary bg-primary/5 text-primary'
                                    : 'border-border bg-background text-muted-foreground hover:border-muted-foreground/40',
                            ]"
                            @click="section.type = sectionType.value"
                        >
                            <span class="font-medium">
                                {{ sectionType.label }}
                            </span>
                            <span
                                class="mt-0.5 leading-tight text-muted-foreground/70"
                            >
                                {{ sectionType.description }}
                            </span>
                        </button>
                    </div>
                </div>
            </CardContent>
        </Card>
        <div class="flex justify-end">
            <Button
                type="button"
                size="sm"
                variant="outline"
                @click="ajouterChapitre"
            >
                <Plus class="mr-2 h-3.5 w-3.5" />
                {{ t('types_projet.edit.add_section') }}
            </Button>
        </div>
    </div>
</template>
