<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import LicenseDocumentsPage from '@/pages/admin/licenses/Documents.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <LicenseDocumentsPage v-bind="attrs" />
</template>
