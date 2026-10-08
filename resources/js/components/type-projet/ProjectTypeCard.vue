<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import InputError from '@/components/shared/InputError.vue';
import { Card, CardContent } from '@/components/ui/card';

type ProjectType = 'standard' | 'musee';

type Form = {
    type: ProjectType;
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
                {{ t('types_projet.create.project_type_title') }}
            </h2>

            <div class="grid grid-cols-2 gap-3">
                <button
                    type="button"
                    :class="[
                        'flex flex-col rounded-md border px-4 py-3 text-left text-sm transition-colors',
                        form.type === 'standard'
                            ? 'border-primary bg-primary/5 text-primary'
                            : 'border-border text-muted-foreground hover:border-muted-foreground/40',
                    ]"
                    @click="form.type = 'standard'"
                >
                    <span class="font-semibold">
                        {{ t('types_projet.create.project_type_standard') }}
                    </span>
                    <span
                        class="mt-0.5 text-xs leading-snug text-muted-foreground"
                    >
                        {{ t('types_projet.create.project_type_standard_hint') }}
                    </span>
                </button>

                <button
                    type="button"
                    :class="[
                        'flex flex-col rounded-md border px-4 py-3 text-left text-sm transition-colors',
                        form.type === 'musee'
                            ? 'border-primary bg-primary/5 text-primary'
                            : 'border-border text-muted-foreground hover:border-muted-foreground/40',
                    ]"
                    @click="form.type = 'musee'"
                >
                    <span class="font-semibold">
                        {{ t('types_projet.create.project_type_musee') }}
                    </span>
                    <span
                        class="mt-0.5 text-xs leading-snug text-muted-foreground"
                    >
                        {{ t('types_projet.create.project_type_musee_hint') }}
                    </span>
                </button>
            </div>

            <InputError :message="errors.type" />
        </CardContent>
    </Card>
</template>
