<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import HosViolationShowPage from '@/pages/admin/hos/ViolationShow.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()

const routeNames = {
    index: 'carrier.violations.index',
    acknowledge: 'carrier.violations.acknowledge',
    forgive: 'carrier.violations.forgive',
}
</script>

<template>
    <HosViolationShowPage v-bind="attrs" :route-names="routeNames" />
</template>
