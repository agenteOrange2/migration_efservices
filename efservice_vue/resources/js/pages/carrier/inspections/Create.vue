<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import InspectionsCreatePage from '@/pages/admin/inspections/Create.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <InspectionsCreatePage v-bind="attrs" />
</template>
