<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import LicenseShowPage from '@/pages/admin/licenses/Show.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <LicenseShowPage v-bind="attrs" />
</template>
