<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import TrainingsShowPage from '@/pages/admin/trainings/Show.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <TrainingsShowPage v-bind="attrs" />
</template>
