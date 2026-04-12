<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import DriverTestingsEditPage from '@/pages/admin/drivers/testings/Edit.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <DriverTestingsEditPage v-bind="attrs" />
</template>
