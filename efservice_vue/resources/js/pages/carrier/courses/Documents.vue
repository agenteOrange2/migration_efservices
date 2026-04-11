<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import CoursesDocumentsPage from '@/pages/admin/courses/Documents.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <CoursesDocumentsPage v-bind="attrs" />
</template>
