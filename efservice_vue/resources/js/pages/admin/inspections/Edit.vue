<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import RazeLayout from '@/layouts/RazeLayout.vue'
import Form from './Form.vue'

defineOptions({ layout: RazeLayout })

defineProps<{
    inspection: any
    carriers: any[]
    drivers: any[]
    vehicles: any[]
    inspectionTypes: string[]
    inspectionLevels: string[]
    statuses: string[]
    carrier?: any | null
    isCarrierContext?: boolean
    routeNames?: Record<string, string>
}>()
</script>

<template>
    <Head title="Edit Inspection" />
    <Form
        mode="edit"
        :inspection="inspection"
        :carriers="carriers"
        :drivers="drivers"
        :vehicles="vehicles"
        :inspection-types="inspectionTypes"
        :inspection-levels="inspectionLevels"
        :statuses="statuses"
        :carrier="carrier"
        :is-carrier-context="isCarrierContext"
        :route-names="routeNames"
    />
</template>
