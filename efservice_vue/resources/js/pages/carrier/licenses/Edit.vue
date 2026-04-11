<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import LicenseEditPage from '@/pages/admin/licenses/Edit.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <LicenseEditPage v-bind="attrs" />
</template>
