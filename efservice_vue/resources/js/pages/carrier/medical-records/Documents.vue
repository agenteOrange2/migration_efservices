<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import MedicalDocumentsPage from '@/pages/admin/medical-records/Documents.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <MedicalDocumentsPage v-bind="attrs" />
</template>
