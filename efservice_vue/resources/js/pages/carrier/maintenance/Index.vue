<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import MaintenanceIndexPage from '@/pages/admin/maintenance/Index.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <MaintenanceIndexPage v-bind="attrs" />
</template>
