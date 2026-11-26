<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { MessagesSquare } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useFilAriane } from '@/composables/useFilAriane';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as echangesGroupe } from '@/routes/groupes/echanges';
import type { BreadcrumbItem } from '@/types';
import type { ConversationEnseignant } from '@/types/enseignant';

defineProps<{ conversations: ConversationEnseignant[] }>();

const { t, locale } = useI18n();
const filAriane = useFilAriane();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    filAriane.accueil(),
    filAriane.echanges(),
]);

/**
 * Formate la date d'envoi du dernier message.
 */
function dateMessage(iso: string): string {
    return new Date(iso).toLocaleString(locale.value, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('enseignant.echanges.title')" />

        <div class="flex flex-col gap-5 p-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    {{ t('enseignant.echanges.title') }}
                </h1>
                <p class="mt-1 text-muted-foreground">
                    {{ t('enseignant.echanges.subtitle') }}
                </p>
            </div>

            <Card>
                <CardHeader class="flex flex-row items-center gap-2">
                    <MessagesSquare class="h-5 w-5" />
                    <CardTitle>{{ t('enseignant.echanges.title') }}</CardTitle>
                    <span class="text-sm text-muted-foreground">
                        ({{ conversations.length }})
                    </span>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="conversations.length === 0"
                        class="py-6 text-center text-sm text-muted-foreground"
                    >
                        {{ t('enseignant.echanges.empty') }}
                    </p>
                    <ul v-else class="divide-y">
                        <li
                            v-for="conversation in conversations"
                            :key="conversation.groupe.id"
                            class="flex flex-wrap items-center gap-3 py-3"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold">
                                    {{
                                        t('classes.groupes.group_number', {
                                            n: conversation.groupe.numero,
                                        })
                                    }}
                                    <span
                                        class="font-normal text-muted-foreground"
                                    >
                                        ·
                                        <span class="font-mono text-xs">
                                            {{ conversation.cours.code }}-{{
                                                conversation.cours.groupe
                                            }}
                                        </span>
                                        ·
                                        {{
                                            conversation.classe.nom ||
                                            t('breadcrumbs.section', {
                                                n: conversation.classe.numero,
                                            })
                                        }}
                                    </span>
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ t('enseignant.groupes.header_elder') }} :
                                    {{
                                        conversation.temoin
                                            ? `${conversation.temoin.prenom} ${conversation.temoin.nom}`
                                            : t('enseignant.groupes.no_elder')
                                    }}
                                    ·
                                    {{
                                        t('enseignant.echanges.messages', {
                                            n: conversation.messages_count,
                                        })
                                    }}
                                </p>
                                <p
                                    v-if="conversation.dernier_message"
                                    class="mt-1 truncate text-sm"
                                >
                                    <span class="font-medium">
                                        {{
                                            conversation.dernier_message.auteur
                                                ?.prenom ?? '—'
                                        }}
                                        :
                                    </span>
                                    {{ conversation.dernier_message.extrait }}
                                    <span class="text-xs text-muted-foreground">
                                        ·
                                        {{
                                            dateMessage(
                                                conversation.dernier_message
                                                    .envoye_le,
                                            )
                                        }}
                                    </span>
                                </p>
                                <p
                                    v-else
                                    class="mt-1 text-sm text-muted-foreground"
                                >
                                    {{ t('enseignant.echanges.no_message') }}
                                </p>
                            </div>
                            <Button size="sm" variant="outline" as-child>
                                <Link
                                    :href="
                                        echangesGroupe.url({
                                            cours: conversation.cours.id,
                                            classe: conversation.classe.id,
                                            groupe: conversation.groupe.id,
                                        })
                                    "
                                >
                                    {{ t('enseignant.echanges.open') }}
                                </Link>
                            </Button>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
