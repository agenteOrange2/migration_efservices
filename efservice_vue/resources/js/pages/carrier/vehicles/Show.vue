<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import VehiclesShowPage from '@/pages/admin/vehicles/Show.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <VehiclesShowPage v-bind="attrs" />
</template>
