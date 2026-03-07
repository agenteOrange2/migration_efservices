<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import * as LucideIcons from 'lucide-vue-next'
import { ChevronDown } from 'lucide-vue-next'
import AppLogo from '@/components/AppLogo.vue'
import NavUser from '@/components/NavUser.vue'
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarGroup,
    SidebarGroupLabel,
} from '@/components/ui/sidebar'
import { useCurrentUrl } from '@/composables/useCurrentUrl'
import type { SideMenuSection, SideMenuItem } from '@/types'

interface Props {
    menu: SideMenuSection[]
    homeRoute: string
}

const props = defineProps<Props>()
const { isCurrentUrl } = useCurrentUrl()
const openSubMenus = ref<Record<string, boolean>>({})

function resolveIcon(iconName: string) {
    const normalized = iconName.charAt(0).toUpperCase() + iconName.slice(1)
    const pascalCase = normalized.replace(/-([a-z])/g, (_, c: string) => c.toUpperCase())
    return (LucideIcons as Record<string, unknown>)[pascalCase] ?? LucideIcons.Circle
}

function isSection(item: SideMenuSection): item is string {
    return typeof item === 'string'
}

function getRouteHref(routeName: string): string {
    if (routeName === '#') return '#'
    try {
        return route(routeName)
    } catch {
        return '#'
    }
}

function isMenuActive(item: SideMenuItem): boolean {
    if (item.route_name !== '#') {
        return isCurrentUrl(getRouteHref(item.route_name))
    }
    if (item.sub_menu) {
        return item.sub_menu.some(sub => isCurrentUrl(getRouteHref(sub.route_name)))
    }
    return false
}

function toggleSubMenu(title: string) {
    openSubMenus.value[title] = !openSubMenus.value[title]
}

function isSubMenuOpen(item: SideMenuItem): boolean {
    if (openSubMenus.value[item.title] !== undefined) {
        return openSubMenus.value[item.title]
    }
    return isMenuActive(item)
}

const groupedMenu = computed(() => {
    const groups: { label: string; items: SideMenuItem[] }[] = []
    let currentLabel = ''

    for (const entry of props.menu) {
        if (isSection(entry)) {
            currentLabel = entry
        } else {
            const lastGroup = groups[groups.length - 1]
            if (lastGroup && lastGroup.label === currentLabel) {
                lastGroup.items.push(entry)
            } else {
                groups.push({ label: currentLabel, items: [entry] })
            }
        }
    }

    return groups
})
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="getRouteHref(homeRoute)">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="overflow-y-auto">
            <SidebarGroup v-for="group in groupedMenu" :key="group.label" class="px-2 py-0">
                <SidebarGroupLabel>{{ group.label }}</SidebarGroupLabel>
                <SidebarMenu>
                    <template v-for="item in group.items" :key="item.title">
                        <SidebarMenuItem v-if="!item.sub_menu">
                            <SidebarMenuButton
                                as-child
                                :is-active="isMenuActive(item)"
                                :tooltip="item.title"
                            >
                                <Link :href="getRouteHref(item.route_name)">
                                    <component :is="resolveIcon(item.icon)" class="size-4" />
                                    <span>{{ item.title }}</span>
                                    <span
                                        v-if="item.badge"
                                        class="ml-auto inline-flex size-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white"
                                    >
                                        {{ item.badge }}
                                    </span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>

                        <SidebarMenuItem v-else>
                            <SidebarMenuButton
                                :is-active="isMenuActive(item)"
                                :tooltip="item.title"
                                @click="toggleSubMenu(item.title)"
                                class="cursor-pointer"
                            >
                                <component :is="resolveIcon(item.icon)" class="size-4" />
                                <span class="flex-1">{{ item.title }}</span>
                                <ChevronDown
                                    class="ml-auto size-4 shrink-0 transition-transform duration-200"
                                    :class="{ 'rotate-180': isSubMenuOpen(item) }"
                                />
                            </SidebarMenuButton>

                            <Transition
                                enter-active-class="transition-all duration-200 ease-out"
                                enter-from-class="max-h-0 opacity-0"
                                enter-to-class="max-h-96 opacity-100"
                                leave-active-class="transition-all duration-150 ease-in"
                                leave-from-class="max-h-96 opacity-100"
                                leave-to-class="max-h-0 opacity-0"
                            >
                                <SidebarMenu v-show="isSubMenuOpen(item)" class="ml-4 mt-1 space-y-0.5 border-l border-gray-200 pl-2 dark:border-gray-700">
                                    <SidebarMenuItem v-for="sub in item.sub_menu" :key="sub.title">
                                        <SidebarMenuButton
                                            as-child
                                            :is-active="isCurrentUrl(getRouteHref(sub.route_name))"
                                            :tooltip="sub.title"
                                            size="sm"
                                        >
                                            <Link :href="getRouteHref(sub.route_name)">
                                                <component :is="resolveIcon(sub.icon)" class="size-3.5" />
                                                <span class="text-xs">{{ sub.title }}</span>
                                                <span
                                                    v-if="sub.badge"
                                                    class="ml-auto inline-flex size-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white"
                                                >
                                                    {{ sub.badge }}
                                                </span>
                                            </Link>
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>
                                </SidebarMenu>
                            </Transition>
                        </SidebarMenuItem>
                    </template>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
