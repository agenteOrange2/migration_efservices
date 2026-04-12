<script setup lang="ts">
import { useAttrs } from 'vue'
import RazeLayout from '@/layouts/RazeLayout.vue'
import EmergencyRepairsEditPage from '@/pages/admin/vehicles/emergency-repairs/Edit.vue'

defineOptions({ layout: RazeLayout })

const attrs = useAttrs()
</script>

<template>
    <EmergencyRepairsEditPage v-bind="attrs" />
</template>
