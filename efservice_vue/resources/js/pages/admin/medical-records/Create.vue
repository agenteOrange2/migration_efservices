<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import RazeLayout from '@/layouts/RazeLayout.vue'
import Form from './Form.vue'

defineOptions({ layout: RazeLayout })

interface CarrierOption {
    id: number
    name: string
}

interface DriverOption {
    id: number
    carrier_id: number | null
    carrier_name: string | null
    name: string
    email: string | null
}

defineProps<{
    carriers: CarrierOption[]
    drivers: DriverOption[]
}>()
</script>

<template>
    <Head title="Add Medical Record" />
    <Form mode="create" :carriers="carriers" :drivers="drivers" />
</template>
