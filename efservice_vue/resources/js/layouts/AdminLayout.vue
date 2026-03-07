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
    { icon: 'BookMarked', route_name: 'admin.dashboard', title: 'Dashboard' },
    'CARRIERS MANAGEMENT',
    {
        icon: 'Users', route_name: '#', title: 'Transporters',
        sub_menu: [
            { icon: 'UserPlus', route_name: 'admin.carrier.index', title: 'Carriers' },
            { icon: 'Vote', route_name: 'admin.admin_documents.list', title: 'All Documents' },
            { icon: 'UserSquare', route_name: 'admin.document-types.index', title: 'Document Type' },
            { icon: 'FileText', route_name: 'admin.document-types.default-policy', title: 'Default Policy' },
        ],
    },
    'DRIVERS MANAGEMENT',
    {
        icon: 'CarFront', route_name: '#', title: 'Drivers',
        sub_menu: [
            { icon: 'UserCheck', route_name: 'admin.driver-recruitment.index', title: 'Recruitment' },
            { icon: 'UserPlus', route_name: 'admin.drivers.index', title: 'Drivers' },
            { icon: 'CreditCard', route_name: 'admin.licenses.index', title: 'Drivers Licenses' },
            { icon: 'HeartPulse', route_name: 'admin.medical-records.index', title: 'Medical Records' },
            { icon: 'FileWarning', route_name: 'admin.drivers.employment-verification.index', title: 'Employment Verification' },
            { icon: 'Building', route_name: 'admin.companies.index', title: 'Companies' },
            { icon: 'AlertTriangle', route_name: 'admin.accidents.index', title: 'Accidents' },
            { icon: 'AlertTriangle', route_name: 'admin.traffic.index', title: 'Traffic Convictions' },
            { icon: 'BadgeInfo', route_name: 'admin.driver-testings.index', title: 'Testing' },
            { icon: 'Eye', route_name: 'admin.inspections.index', title: 'Inspections' },
            { icon: 'School', route_name: 'admin.training-schools.index', title: 'Driving Schools' },
            { icon: 'GraduationCap', route_name: 'admin.trainings.index', title: 'Trainings' },
            { icon: 'ClipboardList', route_name: 'admin.training-assignments.index', title: 'Training Assignments' },
            { icon: 'ShieldCheck', route_name: 'admin.courses.index', title: 'Courses' },
            { icon: 'Archive', route_name: 'admin.drivers.archived.index', title: 'Archived Drivers' },
        ],
    },
    'VEHICLES MANAGEMENT',
    {
        icon: 'Bus', route_name: '#', title: 'Vehicles',
        sub_menu: [
            { icon: 'LayoutDashboard', route_name: 'admin.vehicles.dashboard', title: 'Dashboard' },
            { icon: 'CarFront', route_name: 'admin.vehicles.index', title: 'Vehicle Profile' },
            { icon: 'CarFront', route_name: 'admin.vehicle-makes.index', title: 'Vehicle Make' },
            { icon: 'CarFront', route_name: 'admin.vehicle-types.index', title: 'Vehicle Type' },
            { icon: 'Users', route_name: 'admin.driver-types.index', title: 'Driver Types' },
            { icon: 'FileText', route_name: 'admin.vehicles-documents.index', title: 'Documents Overview' },
            { icon: 'Wrench', route_name: 'admin.maintenance.index', title: 'Maintenance' },
            { icon: 'AlertCircle', route_name: 'admin.vehicles.emergency-repairs.index', title: 'Repairs' },
        ],
    },
    'REPORT GENERATOR',
    {
        icon: 'FileText', route_name: '#', title: 'Reports',
        sub_menu: [
            { icon: 'LayoutDashboard', route_name: 'admin.reports.index', title: 'All Reports' },
            { icon: 'UserCheck', route_name: 'admin.reports.active-drivers', title: 'Active Drivers' },
            { icon: 'UserMinus', route_name: 'admin.reports.inactive-drivers', title: 'Inactive Drivers' },
            { icon: 'UserCheck', route_name: 'admin.reports.driver-prospects', title: 'Prospect Drivers' },
            { icon: 'Truck', route_name: 'admin.reports.equipment-list', title: 'Equipment List' },
            { icon: 'FileArchive', route_name: 'admin.reports.carrier-documents', title: 'Carrier Documents' },
            { icon: 'AlertTriangle', route_name: 'admin.reports.accidents', title: 'Accidents Manager' },
            { icon: 'Wrench', route_name: 'admin.reports.maintenances', title: 'Maintenances Report' },
            { icon: 'AlertCircle', route_name: 'admin.reports.emergency-repairs', title: 'Emergency Repairs' },
            { icon: 'GraduationCap', route_name: 'admin.reports.trainings', title: 'Trainings Report' },
            { icon: 'ArrowRightLeft', route_name: 'admin.reports.migrations', title: 'Migrations Report' },
            { icon: 'MapPin', route_name: 'admin.reports.trips', title: 'Trip Report' },
            { icon: 'Clock', route_name: 'admin.reports.hos', title: 'HOS Report' },
            { icon: 'AlertOctagon', route_name: 'admin.reports.violations', title: 'Violations Report' },
        ],
    },
    'TRIPS',
    {
        icon: 'MapPin', route_name: '#', title: 'Trip Management',
        sub_menu: [
            { icon: 'List', route_name: 'admin.trips.index', title: 'All Trips' },
            { icon: 'BarChart', route_name: 'admin.trips.statistics', title: 'Statistics' },
        ],
    },
    'HOURS OF SERVICE',
    {
        icon: 'Clock', route_name: '#', title: 'HOS Management',
        sub_menu: [
            { icon: 'LayoutDashboard', route_name: 'admin.hos.dashboard', title: 'HOS Overview' },
            { icon: 'Users', route_name: 'admin.drivers.hos.index', title: 'Driver HOS Settings' },
            { icon: 'AlertTriangle', route_name: 'admin.hos.violations', title: 'All Violations' },
            { icon: 'FileText', route_name: 'admin.hos.documents.index', title: 'HOS Documents' },
        ],
    },
    'DATA MANAGEMENT',
    { icon: 'Upload', route_name: 'admin.imports.index', title: 'Bulk Import' },
    'MEMBERSHIPS',
    { icon: 'PackageSearch', route_name: 'admin.membership.index', title: 'Memberships' },
    'MESSAGES MANAGEMENT',
    {
        icon: 'Mail', route_name: '#', title: 'Messages',
        sub_menu: [
            { icon: 'LayoutDashboard', route_name: 'admin.messages.dashboard', title: 'Dashboard' },
            { icon: 'List', route_name: 'admin.messages.index', title: 'All Messages' },
            { icon: 'Plus', route_name: 'admin.messages.create', title: 'New Message' },
        ],
    },
    'CONTACT MANAGEMENT',
    {
        icon: 'Mail', route_name: '#', title: 'Messages Contact',
        sub_menu: [
            { icon: 'MessageSquare', route_name: 'admin.contact-submissions.index', title: 'Contact Submissions' },
            { icon: 'CreditCard', route_name: 'admin.plan-requests.index', title: 'Plan Requests' },
        ],
    },
    'USER MANAGEMENT',
    {
        icon: 'UserSquare', route_name: '#', title: 'Users',
        sub_menu: [
            { icon: 'UserSquare', route_name: 'admin.users.index', title: 'Users' },
            { icon: 'ShieldBan', route_name: 'admin.permissions.index', title: 'Permissions' },
            { icon: 'Users', route_name: 'admin.roles.index', title: 'Roles' },
        ],
    },
]
</script>

<template>
    <AppShell variant="sidebar">
        <RoleSidebar :menu="menu" home-route="admin.dashboard" />
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
