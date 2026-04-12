<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import HosDocumentsPage from '@/pages/admin/hos/documents/Index.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()

const routeNames = {
    index: 'carrier.hos.documents.index',
    generateDailyLog: 'carrier.hos.documents.generate-daily-log',
    generateMonthlySummary: 'carrier.hos.documents.generate-monthly-summary',
    generateFmcsaMonthly: 'carrier.hos.documents.generate-fmcsa-monthly',
    destroy: 'carrier.hos.documents.destroy',
    bulkDestroy: 'carrier.hos.documents.bulk-destroy',
    bulkDownload: 'carrier.hos.documents.bulk-download',
}
</script>

<template>
    <HosDocumentsPage v-bind="attrs" :route-names="routeNames" />
</template>
