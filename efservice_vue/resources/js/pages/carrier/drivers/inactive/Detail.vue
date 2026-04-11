<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import ArchivedDriverDetailPage from '@/pages/admin/drivers/archived/Detail.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <ArchivedDriverDetailPage v-bind="attrs" />
</template>
