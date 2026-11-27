<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ImageIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useFilAriane } from '@/composables/useFilAriane';
import AppLayout from '@/layouts/AppLayout.vue';
import { show as mediasShow } from '@/routes/enseignant/medias';
import type { BreadcrumbItem } from '@/types';
import type { GroupeMedias } from '@/types/enseignant';

defineProps<{
    groupes: (GroupeMedias & {
        photos_count: number;
        audios_count: number;
        documents_count: number;
    })[];
}>();

const { t } = useI18n();
const filAriane = useFilAriane();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    filAriane.accueil(),
    filAriane.medias(),
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('enseignant.medias.title')" />

        <div class="flex flex-col gap-5 p-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    {{ t('enseignant.medias.title') }}
                </h1>
                <p class="mt-1 text-muted-foreground">
                    {{ t('enseignant.medias.subtitle') }}
                </p>
            </div>

            <p
                v-if="groupes.length === 0"
                class="py-6 text-center text-sm text-muted-foreground"
            >
                {{ t('enseignant.medias.empty') }}
            </p>

            <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Card
                    v-for="item in groupes"
                    :key="item.groupe.id"
                    class="flex flex-col"
                >
                    <CardHeader class="flex flex-row items-center gap-2">
                        <ImageIcon class="h-5 w-5 shrink-0" />
                        <CardTitle class="font-mono text-sm">
                            {{ item.cours.code }}-{{ item.cours.groupe }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="flex-1 space-y-1">
                        <p class="truncate font-semibold">
                            {{
                                t('classes.groupes.group_number', {
                                    n: item.groupe.numero,
                                })
                            }}
                            <span class="font-normal text-muted-foreground">
                                ·
                                {{
                                    item.classe.nom ||
                                    t('breadcrumbs.section', {
                                        n: item.classe.numero,
                                    })
                                }}
                            </span>
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{
                                t('enseignant.medias.photos_count', {
                                    n: item.photos_count,
                                })
                            }}
                            ·
                            {{
                                t('enseignant.medias.audios_count', {
                                    n: item.audios_count,
                                })
                            }}
                            ·
                            {{
                                t('enseignant.medias.documents_count', {
                                    n: item.documents_count,
                                })
                            }}
                        </p>
                    </CardContent>
                    <CardFooter>
                        <Button class="w-full" variant="outline" as-child>
                            <Link :href="mediasShow.url(item.groupe.id)">
                                {{ t('enseignant.medias.open') }}
                            </Link>
                        </Button>
                    </CardFooter>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
