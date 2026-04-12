<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import DriverTestingsIndexPage from '@/pages/admin/driver-testings/Index.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <DriverTestingsIndexPage v-bind="attrs" />
</template>
