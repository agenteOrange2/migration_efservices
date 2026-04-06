<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import RazeLayout from '@/layouts/RazeLayout.vue'
import Form from './Form.vue'

defineOptions({ layout: RazeLayout })
</script>

<template>
    <Head title="Create Membership" />
    <Form mode="create" />
</template>
