<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import AccidentsDriverHistoryPage from '@/pages/admin/accidents/DriverHistory.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <AccidentsDriverHistoryPage v-bind="attrs" />
</template>
