import { ref, onUnmounted } from 'vue'

export function useDebounce<T extends (...args: unknown[]) => void>(
    fn: T,
    delay: number = 300,
) {
    const timeoutId = ref<ReturnType<typeof setTimeout> | null>(null)

    function debounced(...args: Parameters<T>) {
        if (timeoutId.value) {
            clearTimeout(timeoutId.value)
        }
        timeoutId.value = setTimeout(() => {
            fn(...args)
        }, delay)
    }

    function cancel() {
        if (timeoutId.value) {
            clearTimeout(timeoutId.value)
            timeoutId.value = null
        }
    }

    onUnmounted(cancel)

    return { debounced, cancel }
}

export function useDebouncedRef<T>(initialValue: T, delay: number = 300) {
    const value = ref<T>(initialValue) as { value: T }
    const debouncedValue = ref<T>(initialValue) as { value: T }
    const timeoutId = ref<ReturnType<typeof setTimeout> | null>(null)

    function update(newValue: T) {
        value.value = newValue
        if (timeoutId.value) clearTimeout(timeoutId.value)
        timeoutId.value = setTimeout(() => {
            debouncedValue.value = newValue
        }, delay)
    }

    onUnmounted(() => {
        if (timeoutId.value) clearTimeout(timeoutId.value)
    })

    return { value, debouncedValue, update }
}
