<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import InspectionsDriverHistoryPage from '@/pages/admin/inspections/DriverHistory.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <InspectionsDriverHistoryPage v-bind="attrs" />
</template>
