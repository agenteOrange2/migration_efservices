<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import TripsIndexPage from '@/pages/admin/trips/Index.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()

const routeNames = {
    index: 'carrier.trips.index',
    statistics: 'carrier.trips.dashboard',
    create: 'carrier.trips.create',
    show: 'carrier.trips.show',
    edit: 'carrier.trips.edit',
    destroy: 'carrier.trips.destroy',
    forceStart: 'carrier.trips.force-start',
    forcePause: 'carrier.trips.force-pause',
    forceResume: 'carrier.trips.force-resume',
    forceEnd: 'carrier.trips.force-end',
}
</script>

<template>
    <TripsIndexPage v-bind="attrs" :route-names="routeNames" overview-label="Dashboard" />
</template>
