<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const page = usePage();
const branding = computed(() => ((page.props as any).branding ?? {}) as Record<string, any>);
const auth = computed(() => ((page.props as any).auth ?? {}) as Record<string, any>);
const user = computed(() => auth.value.user ?? null);
const userRoles = computed<string[]>(() => Array.isArray(user.value?.roles) ? user.value.roles : []);
const isCarrier = computed(() => userRoles.value.includes('user_carrier'));

const logoUrl = computed(() => {
  if (isCarrier.value) {
    return user.value?.carrier_logo_url || branding.value?.logoUrl || null;
  }

  return branding.value?.logoUrl || null;
});

const appName = computed(() => {
  if (isCarrier.value && user.value?.carrier_name) {
    return user.value.carrier_name;
  }

  return branding.value?.appName || (page.props as any).name || 'EF Services';
});
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
    >
        <img
            v-if="logoUrl"
            :src="logoUrl"
            :alt="appName"
            class="h-full w-full object-contain bg-sidebar-primary p-1"
        />
        <AppLogoIcon v-else class="size-5 fill-current text-white dark:text-black" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">{{ appName }}</span>
    </div>
</template>
