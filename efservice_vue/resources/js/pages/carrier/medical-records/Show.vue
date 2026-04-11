<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import MedicalShowPage from '@/pages/admin/medical-records/Show.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <MedicalShowPage v-bind="attrs" />
</template>
