<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import CoursesEditPage from '@/pages/admin/courses/Edit.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <CoursesEditPage v-bind="attrs" />
</template>
