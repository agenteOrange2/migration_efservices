import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { useDebounce } from './useDebounce'

interface FilterOptions {
    routeName: string
    routeParams?: Record<string, unknown>
    debounceMs?: number
    preserveState?: boolean
    preserveScroll?: boolean
    only?: string[]
}

export function useFilters<T extends Record<string, unknown>>(
    initialFilters: T,
    options: FilterOptions,
) {
    const filters = ref<T>({ ...initialFilters }) as { value: T }
    const { debounced: debouncedSearch } = useDebounce(applyFilters, options.debounceMs ?? 300)

    function applyFilters() {
        const cleanFilters: Record<string, unknown> = {}

        for (const [key, value] of Object.entries(filters.value)) {
            if (value !== '' && value !== null && value !== undefined) {
                cleanFilters[key] = value
            }
        }

        router.get(
            route(options.routeName, options.routeParams),
            cleanFilters,
            {
                preserveState: options.preserveState ?? true,
                preserveScroll: options.preserveScroll ?? true,
                only: options.only,
                replace: true,
            },
        )
    }

    function updateFilter(key: keyof T, value: unknown) {
        filters.value[key] = value as T[keyof T]
        debouncedSearch()
    }

    function resetFilters() {
        filters.value = { ...initialFilters }
        applyFilters()
    }

    function setFilter(key: keyof T, value: unknown) {
        filters.value[key] = value as T[keyof T]
        applyFilters()
    }

    return {
        filters,
        updateFilter,
        resetFilters,
        setFilter,
        applyFilters,
    }
}
