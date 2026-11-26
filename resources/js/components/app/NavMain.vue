<script setup lang="ts">
import NavCollapsible from '@/components/app/NavCollapsible.vue';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
    // Libellé du groupe ; « Plateforme » par défaut (barre latérale étudiante)
    label?: string;
}>();
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>{{ label ?? $t('nav.platform') }}</SidebarGroupLabel>
        <SidebarMenu>
            <NavCollapsible
                v-for="item in items"
                :key="item.title"
                :item="item"
                :depth="0"
            />
        </SidebarMenu>
    </SidebarGroup>
</template>
