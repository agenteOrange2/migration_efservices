<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import MedicalIndexPage from '@/pages/admin/medical-records/Index.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <MedicalIndexPage v-bind="attrs" />
</template>
