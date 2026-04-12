<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import RazeLayout from '@/layouts/RazeLayout.vue'
import Form from './Form.vue'

defineOptions({ layout: RazeLayout })

defineProps<{
    repair: null
    carriers: { id: number; name: string }[]
    vehicles: { id: number; carrier_id: number | null; carrier_name: string | null; label: string }[]
    drivers: { id: number; carrier_id: number | null; name: string; email: string | null }[]
    statusOptions: Record<string, string>
    selectedCarrierId?: number | null
    contextVehicle?: {
        id: number
        carrier_id: number | null
        carrier_name: string | null
        label: string
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
    <Head title="New Emergency Repair" />
    <Form v-bind="$props" mode="create" />
</template>
