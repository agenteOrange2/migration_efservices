<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import HosDriverLogPage from '@/pages/admin/hos/DriverLog.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()

const routeNames = {
    driverLog: 'carrier.hos.driver.log',
    documentsIndex: 'carrier.hos.documents.index',
    violationsIndex: 'carrier.violations.index',
    violationsShow: 'carrier.violations.show',
    entryUpdate: 'carrier.hos.entries.update',
    entryDestroy: 'carrier.hos.entries.destroy',
    entryBulkDestroy: 'carrier.hos.entries.bulk-destroy',
}
</script>

<template>
    <HosDriverLogPage v-bind="attrs" :route-names="routeNames" />
</template>
