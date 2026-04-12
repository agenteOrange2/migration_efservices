<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import RazeLayout from '@/layouts/RazeLayout.vue'
import Form from './Form.vue'

defineOptions({ layout: RazeLayout })

defineProps<{
    maintenance: null
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
    <Head title="New Maintenance Record" />
    <Form v-bind="$props" mode="create" />
</template>
