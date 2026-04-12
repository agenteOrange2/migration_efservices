<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import DriverTestingsCreatePage from '@/pages/admin/drivers/testings/Create.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <DriverTestingsCreatePage v-bind="attrs" />
</template>
