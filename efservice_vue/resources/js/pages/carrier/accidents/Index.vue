<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import AccidentsIndexPage from '@/pages/admin/accidents/Index.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <AccidentsIndexPage v-bind="attrs" />
</template>
