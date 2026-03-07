import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import type { FlashMessages } from '@/types/models'

export function useFlash() {
    const page = usePage()

    const flash = computed(() => (page.props.flash ?? {}) as FlashMessages)

    const hasSuccess = computed(() => !!flash.value.success)
    const hasError = computed(() => !!flash.value.error)
    const hasWarning = computed(() => !!flash.value.warning)
    const hasInfo = computed(() => !!flash.value.info)
    const hasAny = computed(() => hasSuccess.value || hasError.value || hasWarning.value || hasInfo.value)

    return {
        flash,
        hasSuccess,
        hasError,
        hasWarning,
        hasInfo,
        hasAny,
    }
}
