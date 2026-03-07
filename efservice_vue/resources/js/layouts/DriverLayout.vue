<script setup lang="ts">
import AppContent from '@/components/AppContent.vue'
import AppShell from '@/components/AppShell.vue'
import AppSidebarHeader from '@/components/AppSidebarHeader.vue'
import RoleSidebar from '@/components/RoleSidebar.vue'
import FlashMessages from '@/components/shared/FlashMessages.vue'
import ConfirmDialog from '@/components/shared/ConfirmDialog.vue'
import type { BreadcrumbItem, SideMenuSection } from '@/types'

interface Props {
    breadcrumbs?: BreadcrumbItem[]
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
})

const menu: SideMenuSection[] = [
    'DASHBOARD',
    { icon: 'LayoutDashboard', route_name: 'driver.dashboard', title: 'Dashboard' },
    'INFORMATION',
    { icon: 'User', route_name: 'driver.profile', title: 'My Profile' },
    { icon: 'CreditCard', route_name: 'driver.licenses.index', title: 'My Licenses' },
    { icon: 'Heart', route_name: 'driver.medical.index', title: 'Medical Record' },
    { icon: 'Truck', route_name: 'driver.vehicles.index', title: 'Vehicles' },
    { icon: 'Wrench', route_name: 'driver.maintenance.index', title: 'Vehicle Maintenance' },
    { icon: 'AlertTriangle', route_name: 'driver.emergency-repairs.index', title: 'Repairs' },
    'TRAININGS',
    { icon: 'GraduationCap', route_name: 'driver.trainings.index', title: 'My Trainings' },
    'TRIPS',
    { icon: 'MapPin', route_name: 'driver.trips.index', title: 'My Trips' },
    'HOURS OF SERVICE',
    { icon: 'Clock', route_name: 'driver.hos.dashboard', title: 'HOS Dashboard' },
    { icon: 'History', route_name: 'driver.hos.history', title: 'HOS History' },
    { icon: 'Settings', route_name: 'driver.hos.cycle.index', title: 'Cycle Settings' },
    'HISTORY',
    { icon: 'TestTube', route_name: 'driver.testing.index', title: 'Tests' },
    { icon: 'Search', route_name: 'driver.inspections.index', title: 'Inspections' },
    'MESSAGES',
    { icon: 'Mail', route_name: 'driver.messages.index', title: 'My Messages' },
    'NOTIFICATIONS',
    { icon: 'Bell', route_name: 'driver.notifications.index', title: 'Notifications' },
    'DOCUMENTS',
    { icon: 'FileText', route_name: 'driver.documents.index', title: 'My Documents' },
]
</script>

<template>
    <AppShell variant="sidebar">
        <RoleSidebar :menu="menu" home-route="driver.dashboard" />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
                <slot />
            </div>
        </AppContent>
    </AppShell>
    <FlashMessages />
    <ConfirmDialog />
</template>
