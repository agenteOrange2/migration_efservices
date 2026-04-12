<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import VehiclesIndexPage from '@/pages/admin/vehicles/Index.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <VehiclesIndexPage v-bind="attrs" />
</template>
