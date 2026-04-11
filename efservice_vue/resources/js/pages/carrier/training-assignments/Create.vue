<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import TrainingAssignmentsCreatePage from '@/pages/admin/training-assignments/Create.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <TrainingAssignmentsCreatePage v-bind="attrs" />
</template>
