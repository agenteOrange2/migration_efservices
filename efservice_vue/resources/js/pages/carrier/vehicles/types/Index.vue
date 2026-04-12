<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import VehicleTypesIndexPage from '@/pages/admin/vehicles/types/Index.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <VehicleTypesIndexPage v-bind="attrs" />
</template>
