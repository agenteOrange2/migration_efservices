<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import InspectionsIndexPage from '@/pages/admin/inspections/Index.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <InspectionsIndexPage v-bind="attrs" />
</template>
