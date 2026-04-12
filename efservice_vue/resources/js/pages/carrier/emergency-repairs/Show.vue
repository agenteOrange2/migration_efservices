<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import EmergencyRepairsShowPage from '@/pages/admin/vehicles/emergency-repairs/Show.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <EmergencyRepairsShowPage v-bind="attrs" />
</template>
