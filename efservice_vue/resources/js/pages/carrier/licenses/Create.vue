<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import LicenseCreatePage from '@/pages/admin/licenses/Create.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <LicenseCreatePage v-bind="attrs" />
</template>
