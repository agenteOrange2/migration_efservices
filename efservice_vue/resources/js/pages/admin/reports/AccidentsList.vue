<script setup lang="ts">
import Accidents from './Accidents.vue'

const props = defineProps([
    'filters',
    'accidents',
    'stats',
    'carriers',
    'drivers',
    'canFilterCarriers',
    'registerRoute',
    'listRoute',
])
</script>

<template>
    <Accidents v-bind="props" />
</template>
