<script setup lang="ts">
import { useAttrs } from 'vue'
import RazeLayout from '@/layouts/RazeLayout.vue'
import EmergencyRepairsShowPage from '@/pages/admin/vehicles/emergency-repairs/Show.vue'

defineOptions({ layout: RazeLayout })

const attrs = useAttrs()
</script>

<template>
    <EmergencyRepairsShowPage v-bind="attrs" />
</template>
