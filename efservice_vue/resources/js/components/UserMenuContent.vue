<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { LogOut, Mail, Shield, ShieldCheck, User } from 'lucide-vue-next';
import { computed } from 'vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { usePermissions } from '@/composables/usePermissions';
import type { User as AppUser } from '@/types';

type Props = {
    user: AppUser;
};

const props = defineProps<Props>();
const { hasAnyRole } = usePermissions();

const handleLogout = () => {
    router.flushAll();
};

function safeRoute(routeName?: string | null): string | null {
    if (!routeName) {
        return null;
    }

    try {
        return route(routeName);
    } catch {
        return null;
    }
}

const profileHref = computed(() => {
    if (hasAnyRole('user_driver')) {
        return safeRoute('driver.profile');
    }

    if (hasAnyRole('user_carrier')) {
        return safeRoute('carrier.profile');
    }

    return safeRoute('profile.edit');
});

const profileEditHref = computed(() => {
    if (hasAnyRole('user_driver')) {
        return safeRoute('driver.profile.edit');
    }

    if (hasAnyRole('user_carrier')) {
        return safeRoute('carrier.profile.edit');
    }

    return safeRoute('profile.edit');
});

const securityHref = computed(() => {
    if (hasAnyRole('user_driver')) {
        return safeRoute('driver.profile.edit');
    }

    if (hasAnyRole('user_carrier')) {
        return safeRoute('carrier.profile.edit');
    }

    return safeRoute('user-password.edit');
});

const rolesHref = computed(() => {
    if (!hasAnyRole('superadmin')) {
        return null;
    }

    return safeRoute('admin.roles.index') ?? safeRoute('admin.permissions.index');
});

const roleLabel = computed(() => {
    const roles = Array.isArray((props.user as any).roles) ? ((props.user as any).roles as string[]) : [];

    if (roles.includes('superadmin')) {
        return 'Administrator';
    }

    if (roles.includes('user_carrier')) {
        return 'Carrier';
    }

    if (roles.includes('user_driver')) {
        return 'Driver';
    }

    return roles[0]
        ? roles[0]
              .replace(/_/g, ' ')
              .replace(/\b\w/g, (letter) => letter.toUpperCase())
        : 'User';
});
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="px-4 py-4 text-left">
            <div class="text-base font-semibold text-slate-700">{{ user.name }}</div>
            <div class="mt-0.5 text-sm text-slate-500">{{ user.email }}</div>
            <div class="mt-3 inline-flex rounded-full bg-primary/8 px-2.5 py-1 text-xs font-medium text-primary">
                {{ roleLabel }}
            </div>
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem v-if="profileHref" :as-child="true">
            <Link class="block w-full cursor-pointer" :href="profileHref">
                <User class="mr-2 h-4 w-4" />
                Profile Info
            </Link>
        </DropdownMenuItem>
        <DropdownMenuItem v-if="profileEditHref" :as-child="true">
            <Link class="block w-full cursor-pointer" :href="profileEditHref">
                <Mail class="mr-2 h-4 w-4" />
                Email Settings
            </Link>
        </DropdownMenuItem>
        <DropdownMenuItem v-if="securityHref" :as-child="true">
            <Link class="block w-full cursor-pointer" :href="securityHref">
                <Shield class="mr-2 h-4 w-4" />
                Security
            </Link>
        </DropdownMenuItem>
        <DropdownMenuItem v-if="rolesHref" :as-child="true">
            <Link class="block w-full cursor-pointer" :href="rolesHref">
                <ShieldCheck class="mr-2 h-4 w-4" />
                Roles &amp; Permissions
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link
            class="block w-full cursor-pointer"
            :href="route('logout')"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            Log out
        </Link>
    </DropdownMenuItem>
</template>
