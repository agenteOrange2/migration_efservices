<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import MaintenanceReportsPage from '@/pages/admin/maintenance/Reports.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <MaintenanceReportsPage v-bind="attrs" />
</template>
