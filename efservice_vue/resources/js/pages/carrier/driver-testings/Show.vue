<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import DriverTestingsShowPage from '@/pages/admin/driver-testings/Show.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <DriverTestingsShowPage v-bind="attrs" />
</template>
