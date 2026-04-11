<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import TrainingSchoolsIndexPage from '@/pages/admin/training-schools/Index.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <TrainingSchoolsIndexPage v-bind="attrs" />
</template>
