<script setup lang="ts">
import { useAttrs } from 'vue'
import RazeLayout from '@/layouts/RazeLayout.vue'
import EmergencyRepairsIndexPage from '@/pages/admin/vehicles/emergency-repairs/Index.vue'

defineOptions({ layout: RazeLayout })

const attrs = useAttrs()
</script>

<template>
    <EmergencyRepairsIndexPage v-bind="attrs" />
</template>
