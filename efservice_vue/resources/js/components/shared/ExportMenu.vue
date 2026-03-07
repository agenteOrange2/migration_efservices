<script setup lang="ts">
import { ref } from 'vue'
import { Download, FileSpreadsheet, FileText, ChevronDown } from 'lucide-vue-next'
import { useExport } from '@/composables/useExport'

interface Props {
    routeName: string
    routeParams?: Record<string, unknown>
    filters?: Record<string, unknown>
    filename?: string
    formats?: ('pdf' | 'excel' | 'csv')[]
}

const props = withDefaults(defineProps<Props>(), {
    formats: () => ['pdf', 'excel'],
})

const { exporting, exportPdf, exportExcel, exportCsv } = useExport()
const isOpen = ref(false)

function handleExport(format: 'pdf' | 'excel' | 'csv') {
    isOpen.value = false
    const options = {
        routeName: props.routeName,
        routeParams: props.routeParams,
        filters: props.filters,
        filename: props.filename,
    }

    switch (format) {
        case 'pdf': return exportPdf(options)
        case 'excel': return exportExcel(options)
        case 'csv': return exportCsv(options)
    }
}

const formatConfig = {
    pdf: { label: 'Export PDF', icon: FileText },
    excel: { label: 'Export Excel', icon: FileSpreadsheet },
    csv: { label: 'Export CSV', icon: FileText },
}
</script>

<template>
    <div class="relative">
        <button
            @click="isOpen = !isOpen"
            :disabled="exporting"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
        >
            <Download class="size-4" :class="{ 'animate-bounce': exporting }" />
            Export
            <ChevronDown class="size-3" />
        </button>

        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-if="isOpen"
                class="absolute right-0 z-20 mt-1 w-44 rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800"
            >
                <button
                    v-for="format in formats"
                    :key="format"
                    @click="handleExport(format)"
                    class="flex w-full items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    <component :is="formatConfig[format].icon" class="size-4" />
                    {{ formatConfig[format].label }}
                </button>
            </div>
        </Transition>

        <div v-if="isOpen" class="fixed inset-0 z-10" @click="isOpen = false" />
    </div>
</template>
