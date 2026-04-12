<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import EmergencyRepairsEditPage from '@/pages/admin/vehicles/emergency-repairs/Edit.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <EmergencyRepairsEditPage v-bind="attrs" />
</template>
