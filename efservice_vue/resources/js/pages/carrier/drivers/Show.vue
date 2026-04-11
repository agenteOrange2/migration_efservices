<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import DriverShowPage from '@/pages/admin/drivers/Show.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <DriverShowPage v-bind="attrs" />
</template>
