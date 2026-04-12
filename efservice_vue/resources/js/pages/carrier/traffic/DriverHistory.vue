<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import TrafficDriverHistoryPage from '@/pages/admin/traffic/DriverHistory.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <TrafficDriverHistoryPage v-bind="attrs" />
</template>
