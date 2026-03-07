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
    { icon: 'BookMarked', route_name: 'carrier.dashboard', title: 'Dashboard' },
    'DRIVERS',
    {
        icon: 'Users', route_name: '#', title: 'Drivers Management',
        sub_menu: [
            { icon: 'User', route_name: 'carrier.drivers.index', title: 'List Drivers' },
            { icon: 'CreditCard', route_name: 'carrier.licenses.index', title: 'Licenses' },
            { icon: 'FileHeart', route_name: 'carrier.medical-records.index', title: 'Medical Records' },
            { icon: 'GraduationCap', route_name: 'carrier.training-schools.index', title: 'Training Schools' },
            { icon: 'BookOpen', route_name: 'carrier.courses.index', title: 'Courses' },
            { icon: 'GraduationCap', route_name: 'carrier.trainings.index', title: 'Trainings' },
            { icon: 'AlertTriangle', route_name: 'carrier.drivers.accidents.index', title: 'Accidents' },
            { icon: 'AlertTriangle', route_name: 'carrier.traffic.index', title: 'Traffic Convictions' },
            { icon: 'ClipboardCheck', route_name: 'carrier.drivers.testings.index', title: 'Drug Tests' },
            { icon: 'ClipboardList', route_name: 'carrier.drivers.inspections.index', title: 'Inspections' },
            { icon: 'Archive', route_name: 'carrier.drivers.inactive.index', title: 'Inactive Drivers' },
        ],
    },
    'VEHICLES',
    {
        icon: 'Truck', route_name: '#', title: 'Vehicles Management',
        sub_menu: [
            { icon: 'List', route_name: 'carrier.vehicles.index', title: 'List Vehicles' },
            { icon: 'FileText', route_name: 'carrier.vehicles-documents.index', title: 'Documents Overview' },
            { icon: 'Truck', route_name: 'carrier.driver-vehicle-management.index', title: 'Driver & Vehicle Management' },
            { icon: 'Settings', route_name: 'carrier.maintenance.index', title: 'Maintenance' },
            { icon: 'Wrench', route_name: 'carrier.emergency-repairs.index', title: 'Repairs Management' },
            { icon: 'Tag', route_name: 'carrier.vehicle-makes.index', title: 'Vehicle Makes' },
            { icon: 'Layers', route_name: 'carrier.vehicle-types.index', title: 'Vehicle Types' },
        ],
    },
    'MESSAGES',
    {
        icon: 'Mail', route_name: '#', title: 'Messages',
        sub_menu: [
            { icon: 'Inbox', route_name: 'carrier.messages.index', title: 'All Messages' },
            { icon: 'BarChart3', route_name: 'carrier.messages.dashboard', title: 'Dashboard' },
            { icon: 'PenSquare', route_name: 'carrier.messages.create', title: 'New Message' },
        ],
    },
    'TRIPS',
    {
        icon: 'MapPin', route_name: '#', title: 'Trip Management',
        sub_menu: [
            { icon: 'LayoutDashboard', route_name: 'carrier.trips.dashboard', title: 'Dashboard' },
            { icon: 'List', route_name: 'carrier.trips.index', title: 'All Trips' },
            { icon: 'Plus', route_name: 'carrier.trips.create', title: 'Create Trip' },
        ],
    },
    'HOURS OF SERVICE',
    {
        icon: 'Clock', route_name: '#', title: 'HOS Management',
        sub_menu: [
            { icon: 'LayoutDashboard', route_name: 'carrier.hos.dashboard', title: 'HOS Dashboard' },
            { icon: 'Settings', route_name: 'carrier.hos.fmcsa.configuration', title: 'FMCSA Configuration' },
            { icon: 'Users', route_name: 'carrier.drivers.hos.index', title: 'Driver HOS Settings' },
            { icon: 'AlertTriangle', route_name: 'carrier.violations.index', title: 'Violations' },
        ],
    },
    'REPORTS',
    { icon: 'FileText', route_name: 'carrier.reports.index', title: 'Dashboard Reports' },
    { icon: 'User', route_name: 'carrier.reports.drivers', title: 'Driver Reports' },
    { icon: 'Truck', route_name: 'carrier.reports.vehicles', title: 'Vehicles Reports' },
    { icon: 'FileText', route_name: 'carrier.reports.accidents', title: 'Accident Reports' },
    { icon: 'FileHeart', route_name: 'carrier.reports.medical-records', title: 'Medical Reports' },
    { icon: 'CreditCard', route_name: 'carrier.reports.licenses', title: 'License Reports' },
    { icon: 'Settings', route_name: 'carrier.reports.maintenance', title: 'Maintenance Reports' },
    { icon: 'Wrench', route_name: 'carrier.reports.repairs', title: 'Repairs Reports' },
    { icon: 'MapPin', route_name: 'carrier.reports.trips', title: 'Trip Reports' },
    { icon: 'Clock', route_name: 'carrier.reports.hos', title: 'HOS Reports' },
    { icon: 'AlertOctagon', route_name: 'carrier.reports.violations', title: 'Violations Reports' },
    'CARRIER PROFILE',
    { icon: 'Bell', route_name: 'carrier.notifications.index', title: 'Notifications' },
    { icon: 'User', route_name: 'carrier.profile', title: 'My Profile' },
]
</script>

<template>
    <AppShell variant="sidebar">
        <RoleSidebar :menu="menu" home-route="carrier.dashboard" />
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
