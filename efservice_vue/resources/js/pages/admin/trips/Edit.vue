<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import RazeLayout from '@/layouts/RazeLayout.vue'
import Form from './Form.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    trip: {
        id: number
        carrier_id: string
        driver_id: string
        vehicle_id: string
        origin_address: string
        destination_address: string
        scheduled_start_date: string | null
        scheduled_start_time: string | null
        scheduled_end_date: string | null
        scheduled_end_time: string | null
        estimated_duration_minutes: number | null
        description: string | null
        notes: string | null
        load_type: string | null
        load_weight: string
        status_label: string
    }
    carriers: { id: number; name: string }[]
    drivers: { id: number; name: string; email?: string | null; hours_remaining: number; status_color?: string; can_drive: boolean }[]
    vehicles: { id: number; label: string }[]
    isSuperadmin: boolean
    routeNames?: Partial<{
        update: string
        show: string
        carrierData: string
    }>
}>()

const form = useForm({
    carrier_id: props.trip.carrier_id || '',
    driver_id: props.trip.driver_id || '',
    vehicle_id: props.trip.vehicle_id || '',
    origin_address: props.trip.origin_address || '',
    destination_address: props.trip.destination_address || '',
    scheduled_start_date: props.trip.scheduled_start_date || '',
    scheduled_start_time: props.trip.scheduled_start_time || '08:00',
    scheduled_end_date: props.trip.scheduled_end_date || '',
    scheduled_end_time: props.trip.scheduled_end_time || '',
    estimated_duration_minutes: props.trip.estimated_duration_minutes ?? '',
    description: props.trip.description || '',
    notes: props.trip.notes || '',
    load_type: props.trip.load_type || '',
    load_weight: props.trip.load_weight || '',
})

function submit() {
    form.put(route(props.routeNames?.update ?? 'admin.trips.update', props.trip.id), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head :title="`Edit ${trip.id}`" />

    <Form
        :form="form"
        :carriers="carriers"
        :drivers="drivers"
        :vehicles="vehicles"
        :is-superadmin="isSuperadmin"
        page-title="Edit Trip"
        page-description="Update the trip assignment, route, and schedule before it starts."
        submit-label="Update Trip"
        :cancel-href="route(props.routeNames?.show ?? 'admin.trips.show', trip.id)"
        :status-label="trip.status_label"
        :route-names="{ carrierData: props.routeNames?.carrierData ?? 'admin.trips.carrier.data' }"
        @submit="submit"
    />
</template>
