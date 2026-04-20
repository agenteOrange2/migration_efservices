<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import AppLogoIcon from '@/components/AppLogoIcon.vue'
import Lucide from '@/components/Base/Lucide'

const page = usePage()
const flash = computed(() => (page.props as any).flash ?? {})
const branding = computed(() => ((page.props as any).branding ?? {}) as Record<string, any>)

const portalName = computed(() => branding.value?.appName || (page.props as any).name || 'EF Services')
const portalLogoUrl = computed(() => branding.value?.logoUrl || null)

const toastVisible = ref(false)
const toastMessage = ref('')
const toastType = ref<'success' | 'error' | 'warning'>('success')
let toastTimer: ReturnType<typeof setTimeout> | null = null

function showToast(type: 'success' | 'error' | 'warning', message: string) {
    toastType.value = type
    toastMessage.value = message
    toastVisible.value = true
    if (toastTimer) clearTimeout(toastTimer)
    toastTimer = setTimeout(() => { toastVisible.value = false }, 6000)
}

watch(flash, (f) => {
    if (f.success) showToast('success', f.success)
    else if (f.error) showToast('error', f.error)
    else if (f.warning) showToast('warning', f.warning)
}, { immediate: true })

const toastIcon = computed(() => {
    if (toastType.value === 'success') return 'CheckCircle'
    if (toastType.value === 'error') return 'XCircle'
    return 'AlertTriangle'
})

const toastClasses = computed(() => {
    if (toastType.value === 'success') return 'border-primary/30 bg-primary/10 text-primary'
    if (toastType.value === 'error') return 'border-red-200 bg-red-50 text-red-700'
    return 'border-warning/30 bg-warning/10 text-warning'
})
</script>

<template>
    <div class="min-h-screen bg-slate-50 dark:bg-darkmode-700">
        <header class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/95 backdrop-blur-sm dark:border-darkmode-400 dark:bg-darkmode-600/95">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
                <Link href="/" class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-lg bg-primary/10">
                        <img
                            v-if="portalLogoUrl"
                            :src="portalLogoUrl"
                            :alt="portalName"
                            class="h-7 w-7 object-contain"
                        />
                        <AppLogoIcon v-else class="h-5 w-5 fill-current text-primary" />
                    </div>
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                        {{ portalName }}
                        <span class="font-normal text-slate-400 dark:text-slate-500"> · Driver Portal</span>
                    </span>
                </Link>
                <Link
                    :href="route('login')"
                    class="flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-primary dark:text-slate-400"
                >
                    <Lucide icon="LogIn" class="h-4 w-4" />
                    Sign In
                </Link>
            </div>
        </header>

        <main class="w-full px-4 py-8 sm:px-6">
            <slot />
        </main>

        <footer class="mt-auto border-t border-slate-200 bg-white py-5 dark:border-darkmode-400 dark:bg-darkmode-600">
            <div class="px-4 sm:px-6">
                <div class="flex flex-col items-center justify-between gap-3 sm:flex-row">
                    <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                        <Lucide icon="ShieldCheck" class="h-3.5 w-3.5 text-primary" />
                        SSL Encrypted · Your data is protected
                    </div>
                    <div class="flex items-center gap-4 text-xs text-slate-400 dark:text-slate-500">
                        <a href="#" class="hover:text-primary">Terms of Service</a>
                        <span>·</span>
                        <a href="#" class="hover:text-primary">Privacy Policy</a>
                        <span>·</span>
                        <span>© {{ new Date().getFullYear() }} {{ portalName }}</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-2 opacity-0"
    >
        <div
            v-if="toastVisible"
            class="fixed bottom-6 right-6 z-[9999] flex max-w-sm items-start gap-3 rounded-2xl border px-4 py-3 shadow-lg"
            :class="toastClasses"
        >
            <Lucide :icon="toastIcon as any" class="mt-0.5 h-4 w-4 shrink-0" />
            <p class="text-sm font-medium leading-snug">{{ toastMessage }}</p>
            <button @click="toastVisible = false" class="ml-auto shrink-0 opacity-60 hover:opacity-100">
                <Lucide icon="X" class="h-4 w-4" />
            </button>
        </div>
    </Transition>
</template>
