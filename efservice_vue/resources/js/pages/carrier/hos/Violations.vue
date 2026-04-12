<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import HosViolationsPage from '@/pages/admin/hos/Violations.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()

const routeNames = {
    index: 'carrier.violations.index',
    show: 'carrier.violations.show',
}
</script>

<template>
    <HosViolationsPage v-bind="attrs" :route-names="routeNames" />
</template>
