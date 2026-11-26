<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { LogOut } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/composables/useInitials';
import { logout } from '@/routes';

const { t } = useI18n();
const page = usePage();
const user = computed(() => page.props.auth.user);
const { getInitials } = useInitials();

/**
 * Vide le cache des pages Inertia avant la déconnexion, comme le menu utilisateur.
 */
function viderCache(): void {
    router.flushAll();
}
</script>

<template>
    <div v-if="user" class="flex items-center gap-3">
        <div class="h-6 w-px bg-border" />
        <Avatar class="size-8 shrink-0">
            <AvatarImage
                v-if="user.avatar"
                :src="user.avatar"
                :alt="user.name"
            />
            <AvatarFallback
                class="bg-primary text-xs font-semibold text-primary-foreground"
            >
                {{ getInitials(user.name) }}
            </AvatarFallback>
        </Avatar>
        <div class="hidden leading-tight md:block">
            <p class="text-sm font-semibold">{{ user.name }}</p>
            <p class="text-xs text-muted-foreground">
                {{ t(`nav.roles.${user.role}`) }}
            </p>
        </div>
        <Button
            variant="outline"
            size="sm"
            class="border-destructive/30 text-destructive hover:bg-destructive/10 hover:text-destructive"
            as-child
        >
            <Link
                :href="logout()"
                as="button"
                data-test="header-logout-button"
                @click="viderCache"
            >
                <LogOut class="size-4" />
                <span class="hidden sm:inline">{{ t('nav.logout') }}</span>
            </Link>
        </Button>
    </div>
</template>
