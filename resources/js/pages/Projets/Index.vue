<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BookOpen,
    CheckCircle2,
    ChevronRight,
    Clock,
    FileEdit,
    FolderOpen,
    Landmark,
    Settings2,
    XCircle,
} from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import BoutonTooltip from '@/components/ui/BoutonTooltip.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import typesProjets from '@/routes/types-projets';

type Etudiant = {
    id: number;
    prenom: string;
    nom: string;
};

type ConclusionResume = {
    etudiant: Etudiant;
    a_redige: boolean;
};

type TypeProjetResume = {
    id: number;
    nom: string;
    description: string | null;
    type: 'standard' | 'musee';
};

type StatutPublication = 'brouillon' | 'soumis' | 'approuve' | 'rejete';

type ProjetResume = {
    id: number;
    titre_projet: string | null;
    completion: number;
    statut: StatutProjet;
    statut_publication: StatutPublication | null;
} | null;

type StatutProjet = 'en_cours' | 'remis' | 'en_retard' | 'remis_en_retard';

type ProjetCard = {
    typeProjet: TypeProjetResume;
    projet: ProjetResume;
    statut: StatutProjet;
    conclusions: ConclusionResume[];
};

type Groupe = {
    id: number;
    nom: string | null;
    classe_id: number;
};

type Classe = {
    id: number;
    code: string;
    cours_id: number;
    nom_cours?: string;
};

type Props = {
    groupe: Groupe;
    classe: Classe;
    projets: ProjetCard[];
    estEnseignant: boolean;
};

const props = defineProps<Props>();

function projetUrl(typeProjetId: number): string {
    return `/cours/${props.classe.cours_id}/classes/${props.groupe.classe_id}/groupes/${props.groupe.id}/projets/${typeProjetId}/edit`;
}

/** Libellé et classe CSS du badge de statut de publication. */
function statutPublicationLabel(statut: StatutPublication): string {
    return {
        brouillon: 'Brouillon',
        soumis: 'En attente',
        approuve: 'Approuvé',
        rejete: 'Rejeté',
    }[statut];
}

function statutPublicationClass(statut: StatutPublication): string {
    return {
        brouillon: 'bg-muted text-muted-foreground',
        soumis: 'bg-amber-100 text-amber-800',
        approuve: 'bg-emerald-100 text-emerald-800',
        rejete: 'bg-red-100 text-red-800',
    }[statut];
}

function statutProjetLabel(statut: StatutProjet): string {
    return {
        en_cours: 'En cours',
        remis: 'Remis',
        en_retard: 'En retard',
        remis_en_retard: 'Remis en retard',
    }[statut];
}

function statutProjetClass(statut: StatutProjet): string {
    return {
        en_cours: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        remis: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
        en_retard: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
        remis_en_retard: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    }[statut];
}
</script>

<template>
    <AppLayout>
        <Head title="Projets" />

        <div class="flex flex-col gap-6 p-6">
            <!-- Retour à la classe -->
            <div>
                <Button variant="ghost" size="sm" as-child>
                    <Link
                        :href="`/cours/${classe.cours_id}/classes/${classe.id}`"
                    >
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Retour à la classe
                    </Link>
                </Button>
            </div>

            <Heading
                title="Projets"
                :description="`Groupe ${groupe.id} · ${classe.code}`"
            />

            <!-- Aucun projet disponible -->
            <div
                v-if="projets.length === 0"
                class="flex flex-col items-center gap-3 rounded-lg border border-dashed p-10 text-center"
            >
                <FolderOpen class="h-10 w-10 text-muted-foreground" />
                <p class="text-sm text-muted-foreground">
                    Aucun projet disponible pour l'instant.
                </p>
                <p v-if="!estEnseignant" class="text-xs text-muted-foreground">
                    L'enseignant n'a pas encore rendu de projet accessible.
                </p>
            </div>

            <!-- Grille des projets — une carte par TypeProjet -->
            <div
                v-else
                class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
            >
                <Card
                    v-for="card in projets"
                    :key="card.typeProjet.id"
                    class="flex flex-col"
                >
                    <CardHeader class="pb-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex min-w-0 flex-wrap items-center gap-2">
                                <CardTitle class="text-base">
                                    {{ card.typeProjet.nom }}
                                </CardTitle>
                                <span
                                    :class="[
                                        'rounded-full px-2 py-0.5 text-[10px] font-semibold',
                                        statutProjetClass(card.statut),
                                    ]"
                                >
                                    {{ statutProjetLabel(card.statut) }}
                                </span>
                            </div>
                            <!-- Badge type musée -->
                            <span
                                v-if="card.typeProjet.type === 'musee'"
                                class="inline-flex shrink-0 items-center gap-1 rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-semibold text-violet-800 dark:bg-violet-900 dark:text-violet-200"
                            >
                                <Landmark class="h-2.5 w-2.5" />
                                Musée
                            </span>
                        </div>
                        <p
                            v-if="card.typeProjet.description"
                            class="text-xs text-muted-foreground"
                        >
                            {{ card.typeProjet.description }}
                        </p>
                    </CardHeader>

                    <CardContent class="flex flex-1 flex-col gap-4">
                        <!-- Lien configuration sections (enseignant seulement) -->
                        <div
                            v-if="estEnseignant"
                            class="flex items-center justify-end"
                        >
                            <BoutonTooltip
                                texte="Gérer les sections disponibles pour ce type de projet"
                                variant="ghost"
                                size="default"
                                class="h-7 gap-1.5 px-2 text-xs text-muted-foreground"
                                as-child
                            >
                                <Link
                                    :href="
                                        typesProjets.edit.url({
                                            cours: classe.cours_id,
                                            typeProjet: card.typeProjet.id,
                                        })
                                    "
                                >
                                    <Settings2 class="h-3 w-3" />
                                    Configurer les sections
                                </Link>
                            </BoutonTooltip>
                        </div>

                        <!-- ── Musée : statut de publication ── -->
                        <div v-if="card.typeProjet.type === 'musee'">
                            <p class="mb-2 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Publication
                            </p>
                            <div v-if="card.projet && card.projet.statut_publication" class="flex items-center gap-2">
                                <CheckCircle2
                                    v-if="card.projet.statut_publication === 'approuve'"
                                    class="h-4 w-4 shrink-0 text-emerald-500"
                                />
                                <Clock
                                    v-else-if="card.projet.statut_publication === 'soumis'"
                                    class="h-4 w-4 shrink-0 text-amber-500"
                                />
                                <XCircle
                                    v-else
                                    class="h-4 w-4 shrink-0 text-muted-foreground"
                                />
                                <span
                                    :class="[
                                        'rounded-full px-2 py-0.5 text-[10px] font-semibold',
                                        statutPublicationClass(card.projet.statut_publication),
                                    ]"
                                >
                                    {{ statutPublicationLabel(card.projet.statut_publication) }}
                                </span>
                            </div>
                            <p v-else class="text-xs text-muted-foreground italic">
                                Musée non démarré
                            </p>
                        </div>

                        <!-- ── Standard : conclusions individuelles ── -->
                        <div v-else>
                            <p
                                class="mb-2 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                            >
                                Conclusions individuelles
                            </p>
                            <div class="space-y-1">
                                <div
                                    v-for="item in card.conclusions"
                                    :key="item.etudiant.id"
                                    class="flex items-center gap-2 text-sm"
                                >
                                    <CheckCircle2
                                        v-if="item.a_redige"
                                        class="h-4 w-4 shrink-0 text-green-500"
                                    />
                                    <XCircle
                                        v-else
                                        class="h-4 w-4 shrink-0 text-muted-foreground"
                                    />
                                    <span
                                        :class="
                                            item.a_redige
                                                ? ''
                                                : 'text-muted-foreground'
                                        "
                                    >
                                        {{ item.etudiant.prenom }}
                                        {{ item.etudiant.nom }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Bouton d'accès (poussé vers le bas) -->
                        <div class="mt-auto pt-2">
                            <BoutonTooltip
                                size="sm"
                                :texte="
                                    !estEnseignant
                                        ? (card.typeProjet.type === 'musee' ? 'Ouvrir l\'éditeur du musée' : 'Ouvrir et éditer votre projet')
                                        : 'Consulter le projet du groupe'
                                "
                                :variant="
                                    !estEnseignant ? 'default' : 'outline'
                                "
                                class="w-full"
                                as-child
                            >
                                <Link :href="projetUrl(card.typeProjet.id)">
                                    <component
                                        :is="
                                            card.typeProjet.type === 'musee' ? Landmark
                                            : !estEnseignant ? FileEdit : BookOpen
                                        "
                                        class="mr-2 h-4 w-4"
                                    />
                                    {{
                                        card.typeProjet.type === 'musee'
                                            ? (estEnseignant ? 'Voir le musée' : 'Mon musée')
                                            : (!estEnseignant ? 'Ouvrir le projet' : 'Consulter')
                                    }}
                                    <ChevronRight class="ml-auto h-4 w-4" />
                                </Link>
                            </BoutonTooltip>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
