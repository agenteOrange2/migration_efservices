<script setup lang="ts">
import { useAttrs } from 'vue'
import RazeLayout from '@/layouts/RazeLayout.vue'
import EmergencyRepairsCreatePage from '@/pages/admin/vehicles/emergency-repairs/Create.vue'

defineOptions({ layout: RazeLayout })

const attrs = useAttrs()
</script>

<template>
    <EmergencyRepairsCreatePage v-bind="attrs" />
</template>
