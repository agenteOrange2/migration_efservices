<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import TrafficDocumentsPage from '@/pages/admin/traffic/Documents.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <TrafficDocumentsPage v-bind="attrs" />
</template>
