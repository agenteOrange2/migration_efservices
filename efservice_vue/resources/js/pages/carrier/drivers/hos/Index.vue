<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import DriverHosIndexPage from '@/pages/admin/drivers/hos/Index.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()

const routeNames = {
    index: 'carrier.drivers.hos.index',
    update: 'carrier.drivers.hos.update',
    approve: 'carrier.drivers.hos.approve',
    reject: 'carrier.drivers.hos.reject',
    driverLog: 'carrier.hos.driver.log',
}
</script>

<template>
    <DriverHosIndexPage v-bind="attrs" :route-names="routeNames" />
</template>
