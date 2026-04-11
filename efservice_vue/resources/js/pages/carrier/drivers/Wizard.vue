<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import DriverWizardPage from '@/pages/admin/drivers/wizard/Wizard.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <DriverWizardPage v-bind="attrs" />
</template>
