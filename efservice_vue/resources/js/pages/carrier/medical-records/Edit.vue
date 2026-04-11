<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import MedicalEditPage from '@/pages/admin/medical-records/Edit.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <MedicalEditPage v-bind="attrs" />
</template>
