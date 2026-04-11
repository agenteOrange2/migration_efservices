<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import TrainingSchoolsShowPage from '@/pages/admin/training-schools/Show.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <TrainingSchoolsShowPage v-bind="attrs" />
</template>
