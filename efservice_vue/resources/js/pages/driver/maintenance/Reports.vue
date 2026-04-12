<script setup lang="ts">
import { useAttrs } from 'vue'
import RazeLayout from '@/layouts/RazeLayout.vue'
import MaintenanceReportsPage from '@/pages/admin/maintenance/Reports.vue'

defineOptions({ layout: RazeLayout })

const attrs = useAttrs()
</script>

<template>
    <MaintenanceReportsPage v-bind="attrs" />
</template>
