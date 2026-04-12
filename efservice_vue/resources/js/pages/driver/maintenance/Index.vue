<script setup lang="ts">
import { useAttrs } from 'vue'
import RazeLayout from '@/layouts/RazeLayout.vue'
import MaintenanceIndexPage from '@/pages/admin/maintenance/Index.vue'

defineOptions({ layout: RazeLayout })

const attrs = useAttrs()
</script>

<template>
    <MaintenanceIndexPage v-bind="attrs" />
</template>
