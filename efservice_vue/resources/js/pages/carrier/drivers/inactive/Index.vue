<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import ArchivedDriversIndexPage from '@/pages/admin/drivers/archived/Index.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <ArchivedDriversIndexPage v-bind="attrs" />
</template>
