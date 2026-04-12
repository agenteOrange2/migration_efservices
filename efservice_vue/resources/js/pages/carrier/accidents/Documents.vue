<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import AccidentsDocumentsPage from '@/pages/admin/accidents/Documents.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <AccidentsDocumentsPage v-bind="attrs" />
</template>
