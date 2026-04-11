<script setup lang="ts">
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import CoursesCreatePage from '@/pages/admin/courses/Create.vue'
import { useAttrs } from 'vue'

defineOptions({ layout: CarrierLayout })

const attrs = useAttrs()
</script>

<template>
    <CoursesCreatePage v-bind="attrs" />
</template>
