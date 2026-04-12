<script setup lang="ts">
import { useAttrs } from 'vue'
import RazeLayout from '@/layouts/RazeLayout.vue'
import MaintenanceCreatePage from '@/pages/admin/maintenance/Create.vue'

defineOptions({ layout: RazeLayout })

const attrs = useAttrs()
</script>

<template>
    <MaintenanceCreatePage v-bind="attrs" />
</template>
