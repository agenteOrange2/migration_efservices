<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import VehiclesDocumentsIndexPage from '@/pages/admin/vehicles/documents/Index.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <VehiclesDocumentsIndexPage v-bind="attrs" />
</template>
