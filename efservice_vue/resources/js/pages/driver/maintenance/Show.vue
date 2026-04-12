<script setup lang="ts">
import { useAttrs } from 'vue'
import RazeLayout from '@/layouts/RazeLayout.vue'
import MaintenanceShowPage from '@/pages/admin/maintenance/Show.vue'

defineOptions({ layout: RazeLayout })

const attrs = useAttrs()
</script>

<template>
    <MaintenanceShowPage v-bind="attrs" />
</template>
