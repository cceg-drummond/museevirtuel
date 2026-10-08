<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import InputError from '@/components/shared/InputError.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Form = {
    type: 'standard' | 'musee';
    date_remise: string;
    remises_multiples: boolean;
    retard_permis: boolean;
    generer_page_titre: boolean;
    generer_table_matieres: boolean;
    aide_reference: boolean;
    has_introduction: boolean;
    has_conclusion_individuelle: boolean;
};

defineProps<{
    form: Form;
    errors: Record<string, string | undefined>;
}>();

const { t } = useI18n();
</script>

<template>
    <Card>
        <CardContent class="grid gap-4 pt-6">
            <h2 class="text-sm font-semibold">
                {{ t('types_projet.edit.submission_section') }}
            </h2>

            <div class="grid gap-2">
                <Label for="date_remise">
                    {{ t('types_projet.edit.label_deadline') }}
                </Label>
                <Input
                    id="date_remise"
                    v-model="form.date_remise"
                    type="datetime-local"
                    required
                />
                <InputError :message="errors.date_remise" />
            </div>

            <div class="flex items-center gap-3">
                <Checkbox
                    id="remises_multiples"
                    v-model:checked="form.remises_multiples"
                />
                <div class="grid gap-0.5">
                    <Label for="remises_multiples" class="cursor-pointer">
                        {{ t('types_projet.edit.label_multiple_submissions') }}
                    </Label>
                    <p class="text-xs text-muted-foreground">
                        {{ t('types_projet.edit.multiple_submissions_hint') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <Checkbox
                    id="retard_permis"
                    v-model:checked="form.retard_permis"
                />
                <div class="grid gap-0.5">
                    <Label for="retard_permis" class="cursor-pointer">
                        {{ t('types_projet.edit.label_late_submission') }}
                    </Label>
                    <p class="text-xs text-muted-foreground">
                        {{ t('types_projet.edit.late_submission_hint') }}
                    </p>
                </div>
            </div>

            <template v-if="form.type === 'standard'">
                <h2 class="mt-2 text-sm font-semibold">
                    {{ t('types_projet.edit.export_options_title') }}
                </h2>

                <div class="flex items-start gap-3">
                    <Checkbox
                        id="generer_page_titre"
                        v-model:checked="form.generer_page_titre"
                    />
                    <div class="grid gap-0.5">
                        <Label for="generer_page_titre" class="cursor-pointer">
                            {{
                                t(
                                    'types_projet.edit.label_generer_page_titre',
                                )
                            }}
                        </Label>
                        <p class="text-xs text-muted-foreground">
                            {{
                                form.generer_page_titre
                                    ? t('types_projet.edit.hint_auto')
                                    : ''
                            }}
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <Checkbox
                        id="generer_table_matieres"
                        v-model:checked="form.generer_table_matieres"
                    />
                    <div class="grid gap-0.5">
                        <Label
                            for="generer_table_matieres"
                            class="cursor-pointer"
                        >
                            {{
                                t(
                                    'types_projet.edit.label_generer_table_matieres',
                                )
                            }}
                        </Label>
                        <p class="text-xs text-muted-foreground">
                            {{
                                form.generer_table_matieres
                                    ? t('types_projet.edit.hint_auto')
                                    : ''
                            }}
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <Checkbox
                        id="aide_reference"
                        v-model:checked="form.aide_reference"
                    />
                    <div class="grid gap-0.5">
                        <Label for="aide_reference" class="cursor-pointer">
                            {{
                                t('types_projet.edit.label_aide_reference')
                            }}
                        </Label>
                        <p class="text-xs text-muted-foreground">
                            {{
                                form.aide_reference
                                    ? t('types_projet.edit.hint_aide_reference')
                                    : ''
                            }}
                        </p>
                    </div>
                </div>

                <h2 class="mt-2 text-sm font-semibold">
                    {{ t('types_projet.edit.structure_title') }}
                </h2>

                <div class="flex items-start gap-3">
                    <Checkbox
                        id="has_introduction"
                        v-model:checked="form.has_introduction"
                    />
                    <div class="grid gap-0.5">
                        <Label for="has_introduction" class="cursor-pointer">
                            {{
                                t('types_projet.edit.label_has_introduction')
                            }}
                        </Label>
                        <p class="text-xs text-muted-foreground">
                            {{
                                t('types_projet.edit.hint_has_introduction')
                            }}
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <Checkbox
                        id="has_conclusion_individuelle"
                        v-model:checked="form.has_conclusion_individuelle"
                    />
                    <div class="grid gap-0.5">
                        <Label
                            for="has_conclusion_individuelle"
                            class="cursor-pointer"
                        >
                            {{
                                t(
                                    'types_projet.edit.label_has_conclusion_individuelle',
                                )
                            }}
                        </Label>
                        <p class="text-xs text-muted-foreground">
                            {{
                                t(
                                    'types_projet.edit.hint_has_conclusion_individuelle',
                                )
                            }}
                        </p>
                    </div>
                </div>
            </template>
        </CardContent>
    </Card>
</template>
