<script setup lang="ts">
import { useAttrs } from 'vue'
import RazeLayout from '@/layouts/RazeLayout.vue'
import MaintenanceEditPage from '@/pages/admin/maintenance/Edit.vue'

defineOptions({ layout: RazeLayout })

const attrs = useAttrs()
</script>

<template>
    <MaintenanceEditPage v-bind="attrs" />
</template>
