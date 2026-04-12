<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import InspectionsDriverDocumentsPage from '@/pages/admin/inspections/DriverDocuments.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <InspectionsDriverDocumentsPage v-bind="attrs" />
</template>
