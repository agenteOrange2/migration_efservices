<script setup lang="ts" generic="T extends Record<string, unknown>">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { ChevronUp, ChevronDown, ChevronsUpDown } from 'lucide-vue-next'
import type { PaginatedResponse } from '@/types/models'

export interface Column<R = T> {
    key: string
    label: string
    sortable?: boolean
    class?: string
    render?: (row: R) => string
}

interface Props {
    data: PaginatedResponse<T>
    columns: Column<T>[]
    sortBy?: string
    sortDir?: 'asc' | 'desc'
    loading?: boolean
    emptyMessage?: string
    rowKey?: string
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
    emptyMessage: 'No records found.',
    rowKey: 'id',
})

const emit = defineEmits<{
    sort: [key: string]
    rowClick: [row: T]
}>()

function getSortIcon(key: string) {
    if (props.sortBy !== key) return ChevronsUpDown
    return props.sortDir === 'asc' ? ChevronUp : ChevronDown
}

function getCellValue(row: T, column: Column<T>): unknown {
    if (column.render) return column.render(row)

    const keys = column.key.split('.')
    let value: unknown = row
    for (const k of keys) {
        if (value == null) return ''
        value = (value as Record<string, unknown>)[k]
    }
    return value ?? ''
}

const showPagination = computed(() => props.data.last_page > 1)
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <slot name="header-prepend" />
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400"
                            :class="col.class"
                        >
                            <button
                                v-if="col.sortable"
                                @click="emit('sort', col.key)"
                                class="inline-flex items-center gap-1 hover:text-gray-900 dark:hover:text-white"
                            >
                                {{ col.label }}
                                <component :is="getSortIcon(col.key)" class="size-3.5" />
                            </button>
                            <span v-else>{{ col.label }}</span>
                        </th>
                        <slot name="header-append" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                    <template v-if="loading">
                        <tr v-for="n in 5" :key="n">
                            <td
                                v-for="col in columns"
                                :key="col.key"
                                class="px-4 py-3"
                            >
                                <div class="h-4 animate-pulse rounded bg-gray-200 dark:bg-gray-700" />
                            </td>
                        </tr>
                    </template>
                    <template v-else-if="data.data.length">
                        <tr
                            v-for="row in data.data"
                            :key="(row as Record<string, unknown>)[rowKey] as string"
                            @click="emit('rowClick', row)"
                            class="transition hover:bg-gray-50 dark:hover:bg-gray-800/50"
                            :class="{ 'cursor-pointer': $attrs.onRowClick }"
                        >
                            <slot name="row-prepend" :row="row" />
                            <td
                                v-for="col in columns"
                                :key="col.key"
                                class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300"
                                :class="col.class"
                            >
                                <slot :name="`cell-${col.key}`" :row="row" :value="getCellValue(row, col)">
                                    {{ getCellValue(row, col) }}
                                </slot>
                            </td>
                            <slot name="row-append" :row="row" />
                        </tr>
                    </template>
                    <tr v-else>
                        <td
                            :colspan="columns.length"
                            class="px-4 py-12 text-center text-sm text-gray-500"
                        >
                            {{ emptyMessage }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="showPagination"
            class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800"
        >
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Showing {{ data.from }} to {{ data.to }} of {{ data.total }} results
            </p>
            <nav class="flex items-center gap-1">
                <template v-for="link in data.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        preserve-state
                        preserve-scroll
                        class="rounded-md border px-3 py-1.5 text-xs font-medium transition"
                        :class="link.active
                            ? 'border-primary bg-primary text-white'
                            : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="rounded-md border border-gray-200 px-3 py-1.5 text-xs text-gray-400 dark:border-gray-700"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </div>
    </div>
</template>
