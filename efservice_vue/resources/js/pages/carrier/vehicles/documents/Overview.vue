<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import VehiclesDocumentsOverviewPage from '@/pages/admin/vehicles/documents/Overview.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <VehiclesDocumentsOverviewPage v-bind="attrs" />
</template>
