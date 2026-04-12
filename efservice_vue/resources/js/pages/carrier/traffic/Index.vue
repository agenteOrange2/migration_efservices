<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import TrafficIndexPage from '@/pages/admin/traffic/Index.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <TrafficIndexPage v-bind="attrs" />
</template>
