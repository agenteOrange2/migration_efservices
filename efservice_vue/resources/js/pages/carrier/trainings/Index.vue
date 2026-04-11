<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import TrainingsIndexPage from '@/pages/admin/trainings/Index.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <TrainingsIndexPage v-bind="attrs" />
</template>
