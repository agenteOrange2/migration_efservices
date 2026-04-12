<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import RazeLayout from '@/layouts/RazeLayout.vue'
import Form from './Form.vue'

defineOptions({ layout: RazeLayout })

interface TrafficRouteNames {
    index: string
    create: string
    store: string
    edit: string
    update: string
    destroy: string
    driverHistory: string
    documentsShow: string
    mediaDestroy: string
    driverShow: string
}

withDefaults(defineProps<{
    conviction: any
    carriers: { id: number; name: string }[]
    drivers: { id: number; carrier_id: number | null; carrier_name: string | null; name: string; email: string | null }[]
    carrier?: { id: number; name: string } | null
    isCarrierContext?: boolean
    routeNames?: TrafficRouteNames
}>(), {
    carrier: null,
    isCarrierContext: false,
    routeNames: () => ({
        index: 'admin.traffic.index',
        create: 'admin.traffic.create',
        store: 'admin.traffic.store',
        edit: 'admin.traffic.edit',
        update: 'admin.traffic.update',
        destroy: 'admin.traffic.destroy',
        driverHistory: 'admin.traffic.driver-history',
        documentsShow: 'admin.traffic.documents.show',
        mediaDestroy: 'admin.traffic.media.destroy',
        driverShow: 'admin.drivers.show',
    }),
})
</script>

<template>
    <Head title="Edit Traffic Conviction" />
    <Form mode="edit" :conviction="conviction" :carriers="carriers" :drivers="drivers" :carrier="carrier" :is-carrier-context="isCarrierContext" :route-names="routeNames" />
</template>
