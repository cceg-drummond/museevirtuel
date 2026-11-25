<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

defineProps<{
    titre: string;
    sousTitre?: string;
    vide: boolean;
    messageVide: string;
}>();
</script>

<template>
    <Card class="gap-2">
        <CardHeader>
            <CardTitle class="text-base">{{ titre }}</CardTitle>
            <p v-if="sousTitre" class="text-xs text-muted-foreground">
                {{ sousTitre }}
            </p>
        </CardHeader>
        <CardContent>
            <p
                v-if="vide"
                class="py-6 text-center text-sm text-muted-foreground"
            >
                {{ messageVide }}
            </p>
            <ul v-else class="divide-y">
                <slot />
            </ul>
        </CardContent>
    </Card>
</template>
