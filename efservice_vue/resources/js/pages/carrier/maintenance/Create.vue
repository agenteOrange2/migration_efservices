<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import MaintenanceCreatePage from '@/pages/admin/maintenance/Create.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <MaintenanceCreatePage v-bind="attrs" />
</template>
