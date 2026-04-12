<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import VehiclesAssignmentHistoryPage from '@/pages/admin/vehicles/AssignmentHistory.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <VehiclesAssignmentHistoryPage v-bind="attrs" />
</template>
