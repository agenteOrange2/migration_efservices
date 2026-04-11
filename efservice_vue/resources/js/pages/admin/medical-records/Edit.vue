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

interface FilePayload {
    name: string
    url: string
    mime_type: string | null
    size: number | null
}

interface RecordPayload {
    id: number
    user_driver_detail_id: number
    carrier_id: number | null
    social_security_number: string | null
    hire_date: string | null
    location: string | null
    is_suspended: boolean
    suspension_date: string | null
    is_terminated: boolean
    termination_date: string | null
    medical_examiner_name: string | null
    medical_examiner_registry_number: string | null
    medical_card_expiration_date: string | null
    driver_name: string
    medical_card_file: FilePayload | null
    social_security_card_file: FilePayload | null
}

defineProps<{
    record: RecordPayload
    carriers: CarrierOption[]
    drivers: DriverOption[]
    carrier?: CarrierOption | null
    routeNames?: {
        index: string
        store: string
        update: string
        show?: string
        documentsShow: string
    }
    isCarrierContext?: boolean
}>()
</script>

<template>
    <Head title="Edit Medical Record" />
    <Form
        mode="edit"
        :record="record"
        :carriers="carriers"
        :drivers="drivers"
        :carrier="carrier"
        :route-names="routeNames"
        :is-carrier-context="isCarrierContext"
    />
</template>
