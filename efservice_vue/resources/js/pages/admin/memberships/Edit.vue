<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import RazeLayout from '@/layouts/RazeLayout.vue'
import Form from './Form.vue'

defineOptions({ layout: RazeLayout })

interface MembershipPayload {
    id: number
    name: string
    description: string
    pricing_type: string
    price: number | null
    carrier_price: number | null
    driver_price: number | null
    vehicle_price: number | null
    max_carrier: number
    max_drivers: number
    max_vehicles: number
    status: number
    show_in_register: number
    image_url?: string | null
}

defineProps<{
    membership: MembershipPayload
}>()
</script>

<template>
    <Head :title="`Edit: ${membership.name}`" />
    <Form mode="edit" :membership="membership" />
</template>
