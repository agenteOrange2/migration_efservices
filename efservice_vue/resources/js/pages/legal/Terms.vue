<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
</script>

<template>
    <Head title="Terms of Service" />
    <div class="min-h-screen bg-slate-50">
        <div class="max-w-4xl mx-auto px-6 py-16">
            <div class="mb-8">
                <Link href="/" class="inline-flex items-center text-sm text-primary hover:underline">
                    &larr; Back to Home
                </Link>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 p-8 sm:p-12">
                <h1 class="text-3xl font-bold text-slate-800 mb-2">Terms of Service</h1>
                <p class="text-sm text-slate-400 mb-10">Last updated: March 5, 2026</p>

                <div class="prose prose-slate max-w-none space-y-8 text-slate-600 leading-relaxed">
                    <section>
                        <h2 class="text-xl font-semibold text-slate-800">1. Acceptance of Terms</h2>
                        <p>By accessing and using eFServices ("the Platform"), you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use the Platform.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-slate-800">2. Description of Service</h2>
                        <p>eFServices provides a transportation management platform connecting carriers, drivers, and shippers. The Platform facilitates fleet management, load booking, compliance tracking, and related logistics services.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-slate-800">3. User Accounts</h2>
                        <p>To use certain features of the Platform, you must register and create an account. You are responsible for:</p>
                        <ul class="list-disc pl-6 space-y-1">
                            <li>Providing accurate and complete registration information</li>
                            <li>Maintaining the security of your account credentials</li>
                            <li>All activities that occur under your account</li>
                            <li>Notifying us immediately of any unauthorized use</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-slate-800">4. Carrier Responsibilities</h2>
                        <p>Carriers using the Platform agree to:</p>
                        <ul class="list-disc pl-6 space-y-1">
                            <li>Maintain valid USDOT authority and operating credentials</li>
                            <li>Provide accurate company and insurance information</li>
                            <li>Comply with all applicable federal and state transportation regulations</li>
                            <li>Ensure all drivers meet legal requirements for operation</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-slate-800">5. Driver Responsibilities</h2>
                        <p>Drivers using the Platform agree to:</p>
                        <ul class="list-disc pl-6 space-y-1">
                            <li>Hold a valid Commercial Driver's License (CDL) where applicable</li>
                            <li>Comply with Hours of Service (HOS) regulations</li>
                            <li>Maintain required medical certificates</li>
                            <li>Follow all safety protocols and guidelines</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-slate-800">6. Payment Terms</h2>
                        <p>Membership fees and payment terms are outlined during the registration process. All payments are due according to the selected membership plan. Failure to maintain payment may result in account suspension.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-slate-800">7. Limitation of Liability</h2>
                        <p>eFServices provides the Platform on an "as-is" basis. We are not liable for any direct, indirect, incidental, or consequential damages arising from your use of the Platform.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-slate-800">8. Termination</h2>
                        <p>We reserve the right to suspend or terminate your account at any time for violation of these terms or applicable laws. You may also terminate your account by contacting our support team.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-slate-800">9. Contact Information</h2>
                        <p>For questions about these Terms of Service, please contact us at:</p>
                        <p class="font-medium">support@efservices.com</p>
                    </section>
                </div>
            </div>
        </div>
    </div>
</template>
