<script setup lang="ts">
import { ref, watch } from 'vue'
import { useFlash } from '@/composables/useFlash'
import { CheckCircle, XCircle, AlertTriangle, Info, X } from 'lucide-vue-next'

const { flash, hasAny } = useFlash()

const visible = ref(false)
const autoHideTimeout = ref<ReturnType<typeof setTimeout>>()

watch(hasAny, (val) => {
    if (val) {
        visible.value = true
        clearTimeout(autoHideTimeout.value)
        autoHideTimeout.value = setTimeout(() => {
            visible.value = false
        }, 5000)
    }
}, { immediate: true })

function dismiss() {
    visible.value = false
}
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="translate-y-[-100%] opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-[-100%] opacity-0"
    >
        <div v-if="visible && hasAny" class="fixed top-4 right-4 z-50 w-96 space-y-2">
            <div
                v-if="flash.success"
                class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-4 text-green-800 shadow-lg dark:border-green-800 dark:bg-green-950 dark:text-green-200"
            >
                <CheckCircle class="size-5 shrink-0" />
                <p class="flex-1 text-sm">{{ flash.success }}</p>
                <button @click="dismiss" class="shrink-0"><X class="size-4" /></button>
            </div>

            <div
                v-if="flash.error"
                class="flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800 shadow-lg dark:border-red-800 dark:bg-red-950 dark:text-red-200"
            >
                <XCircle class="size-5 shrink-0" />
                <p class="flex-1 text-sm">{{ flash.error }}</p>
                <button @click="dismiss" class="shrink-0"><X class="size-4" /></button>
            </div>

            <div
                v-if="flash.warning"
                class="flex items-center gap-3 rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-yellow-800 shadow-lg dark:border-yellow-800 dark:bg-yellow-950 dark:text-yellow-200"
            >
                <AlertTriangle class="size-5 shrink-0" />
                <p class="flex-1 text-sm">{{ flash.warning }}</p>
                <button @click="dismiss" class="shrink-0"><X class="size-4" /></button>
            </div>

            <div
                v-if="flash.info"
                class="flex items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4 text-blue-800 shadow-lg dark:border-blue-800 dark:bg-blue-950 dark:text-blue-200"
            >
                <Info class="size-5 shrink-0" />
                <p class="flex-1 text-sm">{{ flash.info }}</p>
                <button @click="dismiss" class="shrink-0"><X class="size-4" /></button>
            </div>
        </div>
    </Transition>
</template>
