<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import TripsCreatePage from '@/pages/admin/trips/Create.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()

const routeNames = {
    store: 'carrier.trips.store',
    index: 'carrier.trips.index',
    carrierData: 'carrier.trips.carrier.data',
}
</script>

<template>
    <TripsCreatePage v-bind="attrs" :route-names="routeNames" />
</template>
