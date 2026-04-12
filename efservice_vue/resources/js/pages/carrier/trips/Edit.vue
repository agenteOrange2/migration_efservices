<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import TripsEditPage from '@/pages/admin/trips/Edit.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()

const routeNames = {
    update: 'carrier.trips.update',
    show: 'carrier.trips.show',
    carrierData: 'carrier.trips.carrier.data',
}
</script>

<template>
    <TripsEditPage v-bind="attrs" :route-names="routeNames" />
</template>
