<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import VehicleMakesIndexPage from '@/pages/admin/vehicles/makes/Index.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <VehicleMakesIndexPage v-bind="attrs" />
</template>
