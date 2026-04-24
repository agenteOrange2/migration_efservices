<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import Lucide from '@/components/Base/Lucide'

declare function route(name: string, params?: any): string

type StatusKey =
    | 'success'
    | 'info'
    | 'warning'
    | 'error'
    | 'carrier_inactive'
    | 'carrier_rejected'
    | 'carrier_pending'
    | 'carrier_banking_rejected'
    | 'carrier_banking_pending'
    | 'driver_inactive'
    | 'driver_pending'
    | 'application_review'
    | 'application_rejected'

interface FlashShape {
    success?: string | null
    info?: string | null
    warning?: string | null
    error?: string | null
    status_code?: string | null
}

interface Props {
    statusCode?: string | null
    statusMessage?: string | null
}

const props = defineProps<Props>()

const page = usePage()
const flash = computed<FlashShape>(() => ((page.props as any).flash ?? {}) as FlashShape)

// Server-computed props win. Flash is kept as a fallback for redirects from
// elsewhere that may flash a one-shot message.
const statusKey = computed<StatusKey>(() => {
    const serverCode = (props.statusCode as StatusKey | null | undefined) ?? null
    if (serverCode) return serverCode
    const flashCode = flash.value.status_code as StatusKey | null | undefined
    if (flashCode) return flashCode
    if (flash.value.error) return 'error'
    if (flash.value.warning) return 'warning'
    if (flash.value.success) return 'success'
    return 'info'
})

const flashMessage = computed(() => {
    return props.statusMessage
        ?? flash.value.error
        ?? flash.value.warning
        ?? flash.value.info
        ?? flash.value.success
        ?? null
})

interface StatusConfig {
    headTitle: string
    panelTitle: string
    panelMessage: string
    heroTitle: string
    heroSubtitle: string
    noticeTitle: string
    noticeHint: string
    icon: string
    steps: { icon: string; label: string; done: boolean; tone: 'success' | 'pending' | 'danger' }[]
}

const statusMeta = computed<StatusConfig>(() => {
    switch (statusKey.value) {
        case 'carrier_inactive':
            return {
                headTitle: 'Carrier Inactive',
                panelTitle: 'Carrier Inactive',
                panelMessage: 'Your carrier account is currently inactive. You cannot access the driver portal until your carrier is reactivated by the administrator.',
                heroTitle: 'Carrier\nInactive',
                heroSubtitle: 'Access has been paused while your carrier account is inactive. Please reach out to your carrier administrator for next steps.',
                noticeTitle: 'What to do next',
                noticeHint: 'Contact your carrier administrator or our support team to request reactivation.',
                icon: 'PowerOff',
                steps: [
                    { icon: 'CheckCircle', label: 'Driver Account Created', done: true, tone: 'success' },
                    { icon: 'XCircle', label: 'Carrier Currently Inactive', done: true, tone: 'danger' },
                    { icon: 'Lock', label: 'Portal Access Suspended', done: true, tone: 'danger' },
                ],
            }
        case 'carrier_rejected':
            return {
                headTitle: 'Carrier Rejected',
                panelTitle: 'Carrier Registration Rejected',
                panelMessage: "Your carrier's registration was rejected, so driver access is not available. Please contact support for more information.",
                heroTitle: 'Carrier\nRejected',
                heroSubtitle: "The carrier's registration did not pass review. Contact support if you believe this was a mistake.",
                noticeTitle: 'Next steps',
                noticeHint: 'Contact support to review the decision or wait for your carrier to resubmit.',
                icon: 'ShieldX',
                steps: [
                    { icon: 'CheckCircle', label: 'Driver Account Created', done: true, tone: 'success' },
                    { icon: 'XCircle', label: 'Carrier Registration Rejected', done: true, tone: 'danger' },
                    { icon: 'Lock', label: 'Portal Access Blocked', done: true, tone: 'danger' },
                ],
            }
        case 'carrier_pending':
            return {
                headTitle: 'Carrier Pending Approval',
                panelTitle: 'Carrier Pending Approval',
                panelMessage: 'Your carrier is pending approval. Driver access will be enabled as soon as your carrier is activated by our administrators.',
                heroTitle: 'Carrier\nPending',
                heroSubtitle: "We're still reviewing your carrier's registration. Driver access opens as soon as the carrier is activated.",
                noticeTitle: 'While you wait',
                noticeHint: "You'll receive an email the moment your carrier is activated — no action required from you.",
                icon: 'Clock',
                steps: [
                    { icon: 'CheckCircle', label: 'Driver Account Created', done: true, tone: 'success' },
                    { icon: 'Clock', label: 'Carrier Approval Pending', done: false, tone: 'pending' },
                    { icon: 'Lock', label: 'Portal Access Locked', done: false, tone: 'pending' },
                ],
            }
        case 'carrier_banking_rejected':
            return {
                headTitle: 'Carrier Payment Rejected',
                panelTitle: 'Carrier Payment Information Rejected',
                panelMessage: "Your carrier's payment information was rejected. Driver access will be restored once the carrier updates their payment method and it is approved.",
                heroTitle: 'Payment\nRejected',
                heroSubtitle: "Your carrier's payment method needs attention. Please contact your carrier administrator.",
                noticeTitle: 'Action needed',
                noticeHint: 'Contact your carrier administrator to update the payment method.',
                icon: 'CreditCard',
                steps: [
                    { icon: 'CheckCircle', label: 'Driver Account Created', done: true, tone: 'success' },
                    { icon: 'CheckCircle', label: 'Carrier Active', done: true, tone: 'success' },
                    { icon: 'XCircle', label: 'Carrier Payment Rejected', done: true, tone: 'danger' },
                ],
            }
        case 'carrier_banking_pending':
            return {
                headTitle: 'Carrier Payment Validating',
                panelTitle: 'Carrier Payment Validation',
                panelMessage: "Your carrier's payment information is being validated. Driver access opens as soon as validation completes.",
                heroTitle: 'Payment\nValidating',
                heroSubtitle: "We're validating your carrier's payment information. This usually takes a short time.",
                noticeTitle: 'Almost there',
                noticeHint: "You'll regain access automatically once validation is approved.",
                icon: 'Hourglass',
                steps: [
                    { icon: 'CheckCircle', label: 'Driver Account Created', done: true, tone: 'success' },
                    { icon: 'CheckCircle', label: 'Carrier Active', done: true, tone: 'success' },
                    { icon: 'Hourglass', label: 'Carrier Payment Validating', done: false, tone: 'pending' },
                ],
            }
        case 'driver_inactive':
            return {
                headTitle: 'Driver Account Deactivated',
                panelTitle: 'Driver Account Deactivated',
                panelMessage: 'Your driver account has been deactivated. Please contact your carrier administrator or our support team to request reactivation.',
                heroTitle: 'Account\nDeactivated',
                heroSubtitle: 'Your driver account is currently deactivated. Reach out to your carrier administrator to request reactivation.',
                noticeTitle: 'How to regain access',
                noticeHint: 'Contact your carrier administrator or support to request the reactivation of your driver account.',
                icon: 'UserX',
                steps: [
                    { icon: 'CheckCircle', label: 'Driver Account Created', done: true, tone: 'success' },
                    { icon: 'XCircle', label: 'Driver Account Deactivated', done: true, tone: 'danger' },
                    { icon: 'Lock', label: 'Portal Access Suspended', done: true, tone: 'danger' },
                ],
            }
        case 'driver_pending':
            return {
                headTitle: 'Driver Account Pending',
                panelTitle: 'Driver Account Pending Approval',
                panelMessage: 'Your driver account is awaiting approval from the carrier administrator. You will be notified by email as soon as it is activated.',
                heroTitle: 'Almost\nThere!',
                heroSubtitle: 'Your driver account has been created and is pending approval from your carrier administrator.',
                noticeTitle: 'While you wait',
                noticeHint: "Keep an eye on your inbox — we'll notify you the moment your account is activated.",
                icon: 'Clock',
                steps: [
                    { icon: 'CheckCircle', label: 'Driver Account Created', done: true, tone: 'success' },
                    { icon: 'Clock', label: 'Carrier Administrator Approval Pending', done: false, tone: 'pending' },
                    { icon: 'Lock', label: 'Portal Access Locked', done: false, tone: 'pending' },
                ],
            }
        case 'application_rejected':
            return {
                headTitle: 'Application Rejected',
                panelTitle: 'Application Rejected',
                panelMessage: 'Your application has been rejected. Please contact support for more information or to request a new review.',
                heroTitle: 'Application\nRejected',
                heroSubtitle: "We're sorry — your application wasn't approved. Contact support if you believe this was a mistake.",
                noticeTitle: 'Next steps',
                noticeHint: 'Reach out to support to review the decision or submit a new application.',
                icon: 'XCircle',
                steps: [
                    { icon: 'CheckCircle', label: 'Driver Account Created', done: true, tone: 'success' },
                    { icon: 'CheckCircle', label: 'Application Submitted', done: true, tone: 'success' },
                    { icon: 'XCircle', label: 'Application Rejected', done: true, tone: 'danger' },
                ],
            }
        case 'application_review':
            return {
                headTitle: 'Application Under Review',
                panelTitle: 'Application Under Review',
                panelMessage: 'Your application has been submitted and is being reviewed by our team. We will notify you by email as soon as it has been processed.',
                heroTitle: 'Under\nReview',
                heroSubtitle: "Thanks for submitting your application. We'll notify you by email as soon as it has been processed.",
                noticeTitle: 'What happens next',
                noticeHint: 'Keep an eye on your inbox — your status will update as soon as the review is complete.',
                icon: 'Hourglass',
                steps: [
                    { icon: 'CheckCircle', label: 'Driver Account Created', done: true, tone: 'success' },
                    { icon: 'CheckCircle', label: 'Application Submitted', done: true, tone: 'success' },
                    { icon: 'Hourglass', label: 'Under Administrative Review', done: false, tone: 'pending' },
                ],
            }
        case 'error':
            return {
                headTitle: 'Application Rejected',
                panelTitle: 'Application Rejected',
                panelMessage: 'Your application did not meet the requirements at this time. Please contact support or the carrier administrator to review the decision.',
                heroTitle: 'Application\nRejected',
                heroSubtitle: "We're sorry — your application wasn't approved. Reach out to support if you believe this was a mistake.",
                noticeTitle: 'Next steps',
                noticeHint: 'Contact support to review the decision or submit a new application.',
                icon: 'XCircle',
                steps: [
                    { icon: 'CheckCircle', label: 'Account Created', done: true, tone: 'success' },
                    { icon: 'CheckCircle', label: 'Application Submitted', done: true, tone: 'success' },
                    { icon: 'XCircle', label: 'Review Outcome: Rejected', done: true, tone: 'danger' },
                ],
            }
        case 'warning':
            return {
                headTitle: 'Account Pending Approval',
                panelTitle: 'Account Pending Approval',
                panelMessage: 'Your driver account has been created and is awaiting approval from the carrier administrator. You will be notified as soon as your account is activated.',
                heroTitle: 'Almost\nThere!',
                heroSubtitle: 'Your driver account is almost ready. The carrier administrator is reviewing your account and will activate it shortly.',
                noticeTitle: 'While you wait',
                noticeHint: "Keep an eye on your inbox — we'll notify you the moment your account is activated.",
                icon: 'Clock',
                steps: [
                    { icon: 'CheckCircle', label: 'Account Created', done: true, tone: 'success' },
                    { icon: 'CheckCircle', label: 'Registration Submitted', done: true, tone: 'success' },
                    { icon: 'Clock', label: 'Administrator Approval Pending', done: false, tone: 'pending' },
                ],
            }
        case 'success':
            return {
                headTitle: 'Application Approved',
                panelTitle: "You're All Set",
                panelMessage: 'Your account has been approved and is active. You can continue using the driver portal.',
                heroTitle: 'Welcome\nAboard!',
                heroSubtitle: 'Your account is active and ready to go. Head over to the dashboard to continue.',
                noticeTitle: 'All good!',
                noticeHint: 'You can return to the portal whenever you are ready.',
                icon: 'CheckCircle',
                steps: [
                    { icon: 'CheckCircle', label: 'Account Created', done: true, tone: 'success' },
                    { icon: 'CheckCircle', label: 'Application Submitted', done: true, tone: 'success' },
                    { icon: 'CheckCircle', label: 'Application Approved', done: true, tone: 'success' },
                ],
            }
        case 'info':
        default:
            return {
                headTitle: 'Application Under Review',
                panelTitle: 'Application Under Review',
                panelMessage: 'Your application has been submitted and is being reviewed by our team. We will notify you by email as soon as your application has been processed.',
                heroTitle: 'Under\nReview',
                heroSubtitle: "Thanks for submitting your application. We'll notify you by email as soon as it has been processed.",
                noticeTitle: 'What happens next',
                noticeHint: 'Keep an eye on your inbox — your status will update as soon as the review is complete.',
                icon: 'Hourglass',
                steps: [
                    { icon: 'CheckCircle', label: 'Account Created', done: true, tone: 'success' },
                    { icon: 'CheckCircle', label: 'Application Submitted', done: true, tone: 'success' },
                    { icon: 'Hourglass', label: 'Under Administrative Review', done: false, tone: 'pending' },
                ],
            }
    }
})

const displayMessage = computed(() => flashMessage.value ?? statusMeta.value.panelMessage)

function stepClasses(tone: 'success' | 'pending' | 'danger') {
    if (tone === 'success') return { box: 'bg-green-50', icon: 'text-green-600', text: 'text-green-800' }
    if (tone === 'danger') return { box: 'bg-red-50', icon: 'text-red-600', text: 'text-red-800' }
    return { box: 'bg-yellow-50', icon: 'text-yellow-600', text: 'text-yellow-800' }
}

function logout() {
    router.post(route('logout'))
}
</script>

<template>
    <Head :title="statusMeta.headTitle" />

    <div class="container grid lg:h-screen grid-cols-12 lg:max-w-[1550px] 2xl:max-w-[1750px] py-10 px-5 sm:py-14 sm:px-10 md:px-36 lg:py-0 lg:pl-14 lg:pr-12 xl:px-24">
        <div :class="[
            'relative z-50 h-full col-span-12 p-7 sm:p-14 bg-white rounded-2xl lg:bg-transparent lg:pr-10 lg:col-span-5 xl:pr-24 2xl:col-span-4 lg:p-0',
            'before:content-[\'\'] before:absolute before:inset-0 before:-mb-3.5 before:bg-white/40 before:rounded-2xl before:mx-5',
        ]">
            <div class="relative z-10 flex flex-col justify-center w-full h-full py-2 lg:py-24">
                <div class="rounded-[0.8rem] w-[55px] h-[55px] border border-primary/30 flex items-center justify-center">
                    <div class="relative flex items-center justify-center w-[50px] rounded-[0.6rem] h-[50px] bg-linear-to-b from-theme-1/90 to-theme-2/90 bg-white">
                        <Lucide :icon="statusMeta.icon as any" class="w-8 h-8 text-white" />
                    </div>
                </div>

                <div class="mt-10">
                    <div class="text-2xl font-medium">{{ statusMeta.panelTitle }}</div>
                    <div class="mt-2.5 text-slate-600">
                        {{ displayMessage }}
                    </div>

                    <!-- Steps -->
                    <div class="mt-6 space-y-3">
                        <div
                            v-for="(step, idx) in statusMeta.steps"
                            :key="idx"
                            class="flex items-center gap-3 rounded-[0.6rem] p-3"
                            :class="stepClasses(step.tone).box"
                        >
                            <Lucide :icon="step.icon as any" class="w-5 h-5 shrink-0" :class="stepClasses(step.tone).icon" />
                            <span class="text-sm" :class="stepClasses(step.tone).text">{{ step.label }}</span>
                        </div>
                    </div>

                    <!-- Hint -->
                    <div class="mt-6 rounded-[0.6rem] bg-blue-50 p-4">
                        <div class="flex items-start gap-2">
                            <Lucide icon="Info" class="w-5 h-5 shrink-0 text-blue-600 mt-0.5" />
                            <div>
                                <p class="text-sm font-medium text-blue-900">{{ statusMeta.noticeTitle }}</p>
                                <p class="text-xs text-blue-800/80 mt-0.5">{{ statusMeta.noticeHint }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-6 flex flex-col gap-3">
                        <a
                            href="mailto:support@efservices.com"
                            class="flex items-center justify-center gap-2 rounded-[0.6rem] border border-slate-300/80 px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        >
                            <Lucide icon="Mail" class="w-4 h-4" />
                            Contact Support
                        </a>
                        <button
                            @click="logout"
                            class="flex items-center justify-center gap-2 px-4 py-3 text-sm text-slate-500 transition hover:text-slate-700"
                        >
                            <Lucide icon="LogOut" class="w-4 h-4" />
                            Sign Out
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Background -->
    <div class="fixed container grid w-screen inset-0 h-screen grid-cols-12 lg:max-w-[1550px] 2xl:max-w-[1750px] pl-14 pr-12 xl:px-24">
        <div :class="[
            'relative h-screen col-span-12 lg:col-span-5 2xl:col-span-4 z-20',
            'after:bg-white after:hidden after:lg:block after:content-[\'\'] after:absolute after:right-0 after:inset-y-0 after:bg-linear-to-b after:from-white after:to-slate-100/80 after:w-[800%] after:rounded-[0_1.2rem_1.2rem_0/0_1.7rem_1.7rem_0]',
            'before:content-[\'\'] before:hidden before:lg:block before:absolute before:right-0 before:inset-y-0 before:my-6 before:bg-linear-to-b before:from-white/10 before:to-slate-50/10 before:bg-white/50 before:w-[800%] before:-mr-4 before:rounded-[0_1.2rem_1.2rem_0/0_1.7rem_1.7rem_0]',
        ]" />
        <div :class="[
            'h-full col-span-7 2xl:col-span-8 lg:relative',
            'before:content-[\'\'] before:absolute before:lg:-ml-10 before:left-0 before:inset-y-0 before:bg-linear-to-b before:from-theme-1 before:to-theme-2 before:w-screen before:lg:w-[800%]',
            'after:content-[\'\'] after:absolute after:inset-y-0 after:left-0 after:w-screen after:lg:w-[800%] after:bg-texture-white after:bg-fixed after:bg-center after:lg:bg-[25rem_-25rem] after:bg-no-repeat',
        ]">
            <div class="sticky top-0 z-10 flex-col justify-center hidden h-screen ml-16 lg:flex xl:ml-28 2xl:ml-36">
                <div class="leading-[1.4] text-[2.6rem] xl:text-5xl font-medium xl:leading-[1.2] text-white whitespace-pre-line">{{ statusMeta.heroTitle }}</div>
                <div class="mt-5 text-base leading-relaxed xl:text-lg text-white/70">
                    {{ statusMeta.heroSubtitle }}
                </div>
            </div>
        </div>
    </div>
</template>
