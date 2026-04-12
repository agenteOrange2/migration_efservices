<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import EmergencyRepairsCreatePage from '@/pages/admin/vehicles/emergency-repairs/Create.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <EmergencyRepairsCreatePage v-bind="attrs" />
</template>
