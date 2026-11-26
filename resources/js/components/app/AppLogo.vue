<script setup lang="ts">
import AppLogoIcon from '@/components/app/AppLogoIcon.vue';
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
    >
        <AppLogoIcon class="size-5 fill-current text-white dark:text-black" />
    </div>
    <div class="ml-1 grid flex-1 text-left leading-tight">
        <span class="truncate text-sm font-semibold">{{
            $t('sidebar.brand')
        }}</span>
        <span class="truncate text-xs text-muted-foreground">{{
            $t('sidebar.brand_tagline')
        }}</span>
    </div>
</template>
