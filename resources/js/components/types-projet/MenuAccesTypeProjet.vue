<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ChevronDown, EyeOff } from 'lucide-vue-next';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import BoutonTooltip from '@/components/ui/BoutonTooltip.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import typesProjetsRoutes from '@/routes/types-projets';
import accessibiliteRoutes from '@/routes/types-projets/accessibilite';
import { estVisible, libelleClasse } from './acces';
import type { ClasseAcces, TypeProjetAcces } from './acces';

/**
 * Menu de choix des classes ayant accès à un type de projet (« Tous » ou une sélection),
 * accompagné du bouton « Masquer » quand le type est visible.
 */
const props = defineProps<{
    coursId: number;
    typeProjet: TypeProjetAcces;
    classes: ClasseAcces[];
    // Classes supplémentaires des boutons (ex. hauteur réduite dans Cours/Show)
    boutonClass?: string;
}>();

const { t } = useI18n();

type Selection = { tous: boolean; classes: number[] };

// Brouillon local : rien n'est envoyé avant « Rendre accessible »
const selection = ref<Selection>({ tous: false, classes: [] });
const ouvert = ref(false);

const accessForm = useForm<Selection>({ tous: false, classes: [] });
const masquerForm = useForm({});

function changerOuverture(estOuvert: boolean) {
    if (estOuvert) {
        selection.value = {
            tous: props.typeProjet.accessible,
            classes: [...props.typeProjet.classes],
        };
    }

    ouvert.value = estOuvert;
}

function basculerTous(coche: boolean) {
    selection.value = { tous: coche, classes: [] };
}

function basculerClasse(classeId: number, coche: boolean) {
    selection.value = {
        tous: false,
        classes: coche
            ? [...selection.value.classes, classeId]
            : selection.value.classes.filter((id) => id !== classeId),
    };
}

function peutConfirmer(): boolean {
    return selection.value.tous || selection.value.classes.length > 0;
}

function rendreAccessible() {
    accessForm.tous = selection.value.tous;
    accessForm.classes = selection.value.classes;
    accessForm.put(
        accessibiliteRoutes.update.url({
            cours: props.coursId,
            typeProjet: props.typeProjet.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                ouvert.value = false;
            },
        },
    );
}

function masquer() {
    masquerForm.patch(
        typesProjetsRoutes.toggleAccessible.url({
            cours: props.coursId,
            typeProjet: props.typeProjet.id,
        }),
        { preserveScroll: true },
    );
}
</script>

<template>
    <DropdownMenu :open="ouvert" @update:open="changerOuverture">
        <DropdownMenuTrigger as-child>
            <Button
                size="sm"
                :variant="estVisible(typeProjet) ? 'outline' : 'secondary'"
                :class="['text-xs', boutonClass]"
                :title="$t('types_projet.acces.tooltip_choose')"
            >
                <ChevronDown class="mr-1 h-3 w-3" />
                {{
                    estVisible(typeProjet)
                        ? $t('types_projet.acces.btn_manage')
                        : $t('types_projet.acces.btn_make_accessible')
                }}
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-64">
            <DropdownMenuLabel>
                {{ $t('types_projet.acces.menu_label') }}
            </DropdownMenuLabel>
            <DropdownMenuSeparator />

            <!-- @select.prevent : garde le menu ouvert pendant la sélection -->
            <DropdownMenuCheckboxItem
                :model-value="selection.tous"
                @update:model-value="(coche) => basculerTous(coche === true)"
                @select.prevent
            >
                {{ $t('types_projet.acces.all') }}
            </DropdownMenuCheckboxItem>

            <DropdownMenuCheckboxItem
                v-for="classe in classes"
                :key="classe.id"
                :model-value="
                    selection.tous || selection.classes.includes(classe.id)
                "
                @update:model-value="
                    (coche) => basculerClasse(classe.id, coche === true)
                "
                @select.prevent
            >
                {{ libelleClasse(classe, t) }}
            </DropdownMenuCheckboxItem>

            <p
                v-if="classes.length === 0"
                class="px-2 py-1.5 text-xs text-muted-foreground"
            >
                {{ $t('types_projet.acces.no_classes') }}
            </p>

            <DropdownMenuSeparator />
            <div class="p-1">
                <Button
                    size="sm"
                    class="w-full text-xs"
                    :disabled="!peutConfirmer() || accessForm.processing"
                    @click="rendreAccessible"
                >
                    {{ $t('types_projet.acces.btn_make_accessible') }}
                </Button>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>

    <BoutonTooltip
        v-if="estVisible(typeProjet)"
        size="sm"
        variant="outline"
        :texte="$t('types_projet.acces.tooltip_hide')"
        :class="['text-xs', boutonClass]"
        :disabled="masquerForm.processing"
        @click="masquer"
    >
        <EyeOff class="mr-1 h-3 w-3" />
        {{ $t('types_projet.acces.btn_hide') }}
    </BoutonTooltip>
</template>
