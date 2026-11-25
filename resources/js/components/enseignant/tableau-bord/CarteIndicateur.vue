<script setup lang="ts">
import type { Component } from 'vue';
import { Card } from '@/components/ui/card';

defineProps<{
    icone: Component;
    libelle: string;
    valeur: number | string;
    detail?: string;
    // Met le détail en évidence quand il signale quelque chose à traiter
    alerte?: boolean;
}>();
</script>

<template>
    <Card class="gap-1.5 px-5 py-4">
        <div
            class="flex items-center justify-between text-sm font-medium text-muted-foreground"
        >
            <span>{{ libelle }}</span>
            <component :is="icone" class="size-4 opacity-70" />
        </div>
        <div class="text-3xl leading-tight font-bold tracking-tight">
            {{ valeur }}
        </div>
        <div
            v-if="detail"
            class="text-xs"
            :class="
                alerte
                    ? 'font-semibold text-amber-600 dark:text-amber-400'
                    : 'text-muted-foreground'
            "
        >
            {{ detail }}
        </div>
    </Card>
</template>
