<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import RazeLayout from '@/layouts/RazeLayout.vue'
import Form from './Form.vue'

defineOptions({ layout: RazeLayout })

defineProps<{
    maintenance: {
        id: number
        vehicle_id: string
        carrier_id: string
        unit: string
        service_tasks: string
        service_date: string
        next_service_date: string
        vendor_mechanic: string
        cost: string
        odometer: string
        description: string | null
        notes: string | null
        status: boolean
        is_historical: boolean
        attachments: { id: number; name: string; url: string; size: string; mime_type: string | null }[]
    }
    carriers: { id: number; name: string }[]
    vehicles: { id: number; carrier_id: number | null; carrier_name: string | null; label: string }[]
    maintenanceTypes: string[]
    selectedCarrierId?: number | null
    contextVehicle?: {
        id: number
        carrier_id: number | null
        carrier_name: string | null
        label: string
        company_unit_number: string | null
    } | null
    isSuperadmin: boolean
    routeNames?: Partial<{
        index: string
        store: string
        update: string
        attachmentsDestroy: string
        vehicleIndex: string
    }>
}>()
</script>

<template>
    <Head title="Edit Maintenance Record" />
    <Form v-bind="$props" mode="edit" />
</template>
