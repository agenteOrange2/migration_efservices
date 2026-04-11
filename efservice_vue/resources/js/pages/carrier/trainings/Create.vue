<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import TrainingsCreatePage from '@/pages/admin/trainings/Create.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <TrainingsCreatePage v-bind="attrs" />
</template>
