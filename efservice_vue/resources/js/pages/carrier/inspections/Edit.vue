<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import InspectionsEditPage from '@/pages/admin/inspections/Edit.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <InspectionsEditPage v-bind="attrs" />
</template>
