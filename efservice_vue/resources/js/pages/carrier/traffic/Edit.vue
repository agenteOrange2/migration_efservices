<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import TrafficEditPage from '@/pages/admin/traffic/Edit.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <TrafficEditPage v-bind="attrs" />
</template>
