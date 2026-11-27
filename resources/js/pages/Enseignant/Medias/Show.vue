<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FileText } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useFilAriane } from '@/composables/useFilAriane';
import AppLayout from '@/layouts/AppLayout.vue';
import { show as groupeShow } from '@/routes/groupes';
import type { BreadcrumbItem } from '@/types';
import type { GroupeMedias, MediaGroupe } from '@/types/enseignant';

const props = defineProps<{
    groupe: GroupeMedias & { medias: MediaGroupe[] };
}>();

const { t, locale } = useI18n();
const filAriane = useFilAriane();

const titre = computed(() =>
    t('classes.groupes.group_number', { n: props.groupe.groupe.numero }),
);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    filAriane.accueil(),
    filAriane.medias(),
    filAriane.courant(titre.value),
]);

const photos = computed(() =>
    props.groupe.medias.filter((m) => m.type === 'photo'),
);
const audios = computed(() =>
    props.groupe.medias.filter((m) => m.type === 'audio'),
);
const documents = computed(() =>
    props.groupe.medias.filter((m) => m.type === 'document'),
);

/**
 * Indique qui a déposé le média et quand.
 */
function depot(media: MediaGroupe): string {
    return t('enseignant.medias.by', {
        nom: media.auteur ? `${media.auteur.prenom} ${media.auteur.nom}` : '—',
        date: new Date(media.depose_le).toLocaleDateString(locale.value, {
            dateStyle: 'medium',
        }),
    });
}

/**
 * Formate une taille en octets pour l'affichage (Ko ou Mo).
 */
function taille(octets: number): string {
    return octets >= 1024 * 1024
        ? `${(octets / 1024 / 1024).toFixed(1)} Mo`
        : `${Math.max(1, Math.round(octets / 1024))} Ko`;
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`${t('enseignant.medias.title')} · ${titre}`" />

        <div class="flex flex-col gap-5 p-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">
                        {{ titre }}
                    </h1>
                    <p class="mt-1 text-muted-foreground">
                        <span class="font-mono text-sm">
                            {{ groupe.cours.code }}-{{ groupe.cours.groupe }}
                        </span>
                        ·
                        {{
                            groupe.classe.nom ||
                            t('breadcrumbs.section', {
                                n: groupe.classe.numero,
                            })
                        }}
                    </p>
                </div>
                <Button size="sm" variant="outline" as-child>
                    <Link
                        :href="
                            groupeShow.url({
                                cours: groupe.cours.id,
                                classe: groupe.classe.id,
                                groupe: groupe.groupe.id,
                            })
                        "
                    >
                        {{ t('enseignant.medias.manage') }}
                    </Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>
                        {{ t('enseignant.medias.photos') }} ({{
                            photos.length
                        }})
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="photos.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        {{ t('enseignant.medias.none') }}
                    </p>
                    <div
                        v-else
                        class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4"
                    >
                        <a
                            v-for="photo in photos"
                            :key="photo.id"
                            :href="photo.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="group block overflow-hidden rounded-lg border"
                            :title="depot(photo)"
                        >
                            <img
                                :src="photo.url"
                                :alt="photo.nom_original"
                                loading="lazy"
                                class="aspect-square w-full object-cover transition group-hover:opacity-90"
                            />
                            <p class="truncate px-2 py-1 text-xs">
                                {{ photo.nom_original }}
                            </p>
                        </a>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>
                        {{ t('enseignant.medias.audios') }} ({{
                            audios.length
                        }})
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="audios.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        {{ t('enseignant.medias.none') }}
                    </p>
                    <ul v-else class="divide-y">
                        <li
                            v-for="audio in audios"
                            :key="audio.id"
                            class="space-y-2 py-3"
                        >
                            <div class="flex flex-wrap items-baseline gap-2">
                                <p class="text-sm font-medium">
                                    {{ audio.nom_original }}
                                </p>
                                <span class="text-xs text-muted-foreground">
                                    {{ depot(audio) }}
                                </span>
                                <span
                                    v-if="
                                        audio.transcription_statut === 'terminé'
                                    "
                                    class="rounded-full border px-2 text-xs text-muted-foreground"
                                >
                                    {{ t('enseignant.medias.transcribed') }}
                                </span>
                            </div>
                            <audio
                                :src="audio.url"
                                controls
                                preload="none"
                                class="w-full max-w-md"
                            />
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>
                        {{ t('enseignant.medias.documents') }} ({{
                            documents.length
                        }})
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="documents.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        {{ t('enseignant.medias.none') }}
                    </p>
                    <ul v-else class="divide-y">
                        <li
                            v-for="document in documents"
                            :key="document.id"
                            class="flex items-center gap-3 py-2"
                        >
                            <FileText
                                class="h-4 w-4 shrink-0 text-muted-foreground"
                            />
                            <div class="min-w-0 flex-1">
                                <a
                                    :href="document.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="block truncate text-sm font-medium underline-offset-2 hover:underline"
                                >
                                    {{ document.nom_original }}
                                </a>
                                <p class="text-xs text-muted-foreground">
                                    {{ depot(document) }} ·
                                    {{ taille(document.taille) }}
                                </p>
                            </div>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
