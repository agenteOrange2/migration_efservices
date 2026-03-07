import { ref } from 'vue'

interface ConfirmationState {
    isOpen: boolean
    title: string
    message: string
    confirmText: string
    cancelText: string
    variant: 'danger' | 'warning' | 'info'
    onConfirm: (() => void) | null
    onCancel: (() => void) | null
}

const state = ref<ConfirmationState>({
    isOpen: false,
    title: '',
    message: '',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    variant: 'danger',
    onConfirm: null,
    onCancel: null,
})

export function useConfirmation() {
    function confirm(options: {
        title: string
        message: string
        confirmText?: string
        cancelText?: string
        variant?: 'danger' | 'warning' | 'info'
    }): Promise<boolean> {
        return new Promise((resolve) => {
            state.value = {
                isOpen: true,
                title: options.title,
                message: options.message,
                confirmText: options.confirmText ?? 'Confirm',
                cancelText: options.cancelText ?? 'Cancel',
                variant: options.variant ?? 'danger',
                onConfirm: () => {
                    state.value.isOpen = false
                    resolve(true)
                },
                onCancel: () => {
                    state.value.isOpen = false
                    resolve(false)
                },
            }
        })
    }

    function confirmDelete(itemName: string = 'this item'): Promise<boolean> {
        return confirm({
            title: 'Delete Confirmation',
            message: `Are you sure you want to delete ${itemName}? This action cannot be undone.`,
            confirmText: 'Delete',
            variant: 'danger',
        })
    }

    return {
        state,
        confirm,
        confirmDelete,
    }
}
