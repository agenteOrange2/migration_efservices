<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import LicenseIndexPage from '@/pages/admin/licenses/Index.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <LicenseIndexPage v-bind="attrs" />
</template>
