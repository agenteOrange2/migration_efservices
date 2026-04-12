<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import RazeLayout from '@/layouts/RazeLayout.vue'
import Form from './Form.vue'

defineOptions({ layout: RazeLayout })

interface AccidentRouteNames {
    index: string
    create: string
    store: string
    edit: string
    update: string
    destroy: string
    driverHistory: string
    documentsIndex: string
    documentsShow: string
    documentsDestroy: string
    mediaDestroy: string
    driverShow: string
}

withDefaults(defineProps<{
    carriers: { id: number; name: string }[]
    drivers: { id: number; carrier_id: number | null; carrier_name: string | null; name: string; email: string | null }[]
    carrier?: { id: number; name: string } | null
    isCarrierContext?: boolean
    routeNames?: AccidentRouteNames
}>(), {
    carrier: null,
    isCarrierContext: false,
    routeNames: () => ({
        index: 'admin.accidents.index',
        create: 'admin.accidents.create',
        store: 'admin.accidents.store',
        edit: 'admin.accidents.edit',
        update: 'admin.accidents.update',
        destroy: 'admin.accidents.destroy',
        driverHistory: 'admin.accidents.driver-history',
        documentsIndex: 'admin.accidents.documents.index',
        documentsShow: 'admin.accidents.documents.show',
        documentsDestroy: 'admin.accidents.documents.destroy',
        mediaDestroy: 'admin.accidents.media.destroy',
        driverShow: 'admin.drivers.show',
    }),
})
</script>

<template>
    <Head title="Add Accident Record" />
    <Form
        mode="create"
        :carriers="carriers"
        :drivers="drivers"
        :carrier="carrier"
        :is-carrier-context="isCarrierContext"
        :route-names="routeNames"
    />
</template>
