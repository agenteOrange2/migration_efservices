<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import DriverTestingsHistoryPage from '@/pages/admin/driver-testings/DriverHistory.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <DriverTestingsHistoryPage v-bind="attrs" />
</template>
