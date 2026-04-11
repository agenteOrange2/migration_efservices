<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import CoursesIndexPage from '@/pages/admin/courses/Index.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <CoursesIndexPage v-bind="attrs" />
</template>
