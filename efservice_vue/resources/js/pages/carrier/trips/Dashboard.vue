<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import TripsStatisticsPage from '@/pages/admin/trips/Statistics.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()

const routeNames = {
    statistics: 'carrier.trips.dashboard',
    index: 'carrier.trips.index',
    show: 'carrier.trips.show',
}
</script>

<template>
    <TripsStatisticsPage
        v-bind="attrs"
        :route-names="routeNames"
        page-title="Trips Dashboard"
        page-description="Operational overview of your carrier trips, statuses, violations, and ghost logs."
        back-label="Back to Trips"
    />
</template>
