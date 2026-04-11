<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import DriverIndexPage from '@/pages/admin/drivers/Index.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <DriverIndexPage v-bind="attrs" />
</template>
