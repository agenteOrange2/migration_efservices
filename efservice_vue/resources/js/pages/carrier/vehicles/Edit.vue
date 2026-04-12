<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import VehiclesEditPage from '@/pages/admin/vehicles/Edit.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <VehiclesEditPage v-bind="attrs" />
</template>
