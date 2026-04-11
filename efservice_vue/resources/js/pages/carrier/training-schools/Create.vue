<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import TrainingSchoolsCreatePage from '@/pages/admin/training-schools/Create.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <TrainingSchoolsCreatePage v-bind="attrs" />
</template>
