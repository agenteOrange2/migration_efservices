<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import RazeLayout from '@/layouts/RazeLayout.vue'
import Form from './Form.vue'

defineOptions({ layout: RazeLayout })

defineProps<{
    carriers: { id: number; name: string }[]
    drivers: { id: number; carrier_id: number | null; carrier_name: string | null; name: string; email: string | null }[]
}>()
</script>

<template>
    <Head title="Add Traffic Conviction" />
    <Form mode="create" :carriers="carriers" :drivers="drivers" />
</template>
