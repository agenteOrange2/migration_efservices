<script setup lang="ts">
import { Search, X } from 'lucide-vue-next'

interface Props {
    modelValue: string
    placeholder?: string
}

withDefaults(defineProps<Props>(), {
    placeholder: 'Search...',
})

const emit = defineEmits<{
    'update:modelValue': [value: string]
}>()

function clear() {
    emit('update:modelValue', '')
}
</script>

<template>
    <div class="relative">
        <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
        <input
            type="text"
            :value="modelValue"
            @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
            :placeholder="placeholder"
            class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-9 text-sm transition focus:border-primary focus:ring-1 focus:ring-primary dark:border-gray-600 dark:bg-gray-900 dark:text-white"
        />
        <button
            v-if="modelValue"
            @click="clear"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
        >
            <X class="size-4" />
        </button>
    </div>
</template>
