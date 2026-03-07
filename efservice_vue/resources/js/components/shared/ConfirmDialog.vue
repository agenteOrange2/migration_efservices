<script setup lang="ts">
import { useConfirmation } from '@/composables/useConfirmation'
import { AlertTriangle, Info, ShieldAlert } from 'lucide-vue-next'
import { computed } from 'vue'

const { state } = useConfirmation()

const icon = computed(() => {
    switch (state.value.variant) {
        case 'danger': return ShieldAlert
        case 'warning': return AlertTriangle
        case 'info': return Info
        default: return ShieldAlert
    }
})

const iconColorClass = computed(() => {
    switch (state.value.variant) {
        case 'danger': return 'text-red-500'
        case 'warning': return 'text-yellow-500'
        case 'info': return 'text-blue-500'
        default: return 'text-red-500'
    }
})

const confirmButtonClass = computed(() => {
    switch (state.value.variant) {
        case 'danger': return 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500'
        case 'warning': return 'bg-yellow-600 text-white hover:bg-yellow-700 focus:ring-yellow-500'
        case 'info': return 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500'
        default: return 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500'
    }
})
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="state.isOpen" class="fixed inset-0 z-[100] flex items-center justify-center">
                <div class="fixed inset-0 bg-black/50" @click="state.onCancel?.()" />
                <div class="relative z-10 w-full max-w-md rounded-xl bg-white p-6 shadow-2xl dark:bg-gray-900">
                    <div class="flex items-start gap-4">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                            <component :is="icon" class="size-5" :class="iconColorClass" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ state.title }}
                            </h3>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ state.message }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            @click="state.onCancel?.()"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                        >
                            {{ state.cancelText }}
                        </button>
                        <button
                            @click="state.onConfirm?.()"
                            class="rounded-lg px-4 py-2 text-sm font-medium focus:ring-2 focus:ring-offset-2"
                            :class="confirmButtonClass"
                        >
                            {{ state.confirmText }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
