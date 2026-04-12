<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import MaintenanceShowPage from '@/pages/admin/maintenance/Show.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <MaintenanceShowPage v-bind="attrs" />
</template>
