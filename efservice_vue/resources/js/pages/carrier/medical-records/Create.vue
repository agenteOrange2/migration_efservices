<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import MedicalCreatePage from '@/pages/admin/medical-records/Create.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <MedicalCreatePage v-bind="attrs" />
</template>
