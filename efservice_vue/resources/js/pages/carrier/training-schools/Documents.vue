<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import TrainingSchoolsDocumentsPage from '@/pages/admin/training-schools/Documents.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <TrainingSchoolsDocumentsPage v-bind="attrs" />
</template>
