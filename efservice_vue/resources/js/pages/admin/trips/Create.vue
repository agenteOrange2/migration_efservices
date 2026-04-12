<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import RazeLayout from '@/layouts/RazeLayout.vue'
import Form from './Form.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    carriers: { id: number; name: string }[]
    drivers: { id: number; name: string; email?: string | null; hours_remaining: number; status_color?: string; can_drive: boolean }[]
    vehicles: { id: number; label: string }[]
    isSuperadmin: boolean
    selectedCarrierId: string
    routeNames?: Partial<{
        store: string
        index: string
        carrierData: string
    }>
}>()

const form = useForm({
    carrier_id: props.selectedCarrierId || '',
    driver_id: '',
    vehicle_id: '',
    origin_address: '',
    destination_address: '',
    scheduled_start_date: '',
    scheduled_start_time: '08:00',
    scheduled_end_date: '',
    scheduled_end_time: '',
    estimated_duration_minutes: '',
    description: '',
    notes: '',
    load_type: '',
    load_weight: '',
})

function submit() {
    form.post(route(props.routeNames?.store ?? 'admin.trips.store'), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Create Trip" />

    <Form
        :form="form"
        :carriers="carriers"
        :drivers="drivers"
        :vehicles="vehicles"
        :is-superadmin="isSuperadmin"
        page-title="Create New Trip"
        page-description="Schedule a trip across carriers with the current admin workflow."
        submit-label="Create Trip"
        :cancel-href="route(props.routeNames?.index ?? 'admin.trips.index')"
        :route-names="{ carrierData: props.routeNames?.carrierData ?? 'admin.trips.carrier.data' }"
        @submit="submit"
    />
</template>
