<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import TrafficCreatePage from '@/pages/admin/traffic/Create.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <TrafficCreatePage v-bind="attrs" />
</template>
