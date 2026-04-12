<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import EmergencyRepairsIndexPage from '@/pages/admin/vehicles/emergency-repairs/Index.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <EmergencyRepairsIndexPage v-bind="attrs" />
</template>
