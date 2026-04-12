<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import MaintenanceCalendarPage from '@/pages/admin/maintenance/Calendar.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <MaintenanceCalendarPage v-bind="attrs" />
</template>
