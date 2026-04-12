<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import AccidentsEditPage from '@/pages/admin/accidents/Edit.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <AccidentsEditPage v-bind="attrs" />
</template>
