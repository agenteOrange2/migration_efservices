<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import RazeLayout from '@/layouts/RazeLayout.vue'
import Form from './Form.vue'

defineOptions({ layout: RazeLayout })

defineProps<{
    repair: {
        id: number
        vehicle_id: string
        carrier_id: string
        repair_name: string
        repair_date: string
        cost: string
        odometer: string
        status: string
        description: string | null
        notes: string | null
        attachments: { id: number; name: string; url: string; size: string; mime_type: string | null }[]
    }
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
}>()
</script>

<template>
    <Head title="Edit Emergency Repair" />
    <Form v-bind="$props" mode="edit" />
</template>
