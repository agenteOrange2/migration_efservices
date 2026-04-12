<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import TripsShowPage from '@/pages/admin/trips/Show.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()

const routeNames = {
    destroy: 'carrier.trips.destroy',
    edit: 'carrier.trips.edit',
    index: 'carrier.trips.index',
    forceStart: 'carrier.trips.force-start',
    forcePause: 'carrier.trips.force-pause',
    forceResume: 'carrier.trips.force-resume',
    forceEnd: 'carrier.trips.force-end',
}
</script>

<template>
    <TripsShowPage v-bind="attrs" :route-names="routeNames" />
</template>
