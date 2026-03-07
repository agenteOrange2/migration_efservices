<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
</script>

<template>
    <Head title="Privacy Policy" />
    <div class="min-h-screen bg-slate-50">
        <div class="max-w-4xl mx-auto px-6 py-16">
            <div class="mb-8">
                <Link href="/" class="inline-flex items-center text-sm text-primary hover:underline">
                    &larr; Back to Home
                </Link>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 p-8 sm:p-12">
                <h1 class="text-3xl font-bold text-slate-800 mb-2">Privacy Policy</h1>
                <p class="text-sm text-slate-400 mb-10">Last updated: March 5, 2026</p>

                <div class="prose prose-slate max-w-none space-y-8 text-slate-600 leading-relaxed">
                    <section>
                        <h2 class="text-xl font-semibold text-slate-800">1. Information We Collect</h2>
                        <p>We collect information you provide directly to us, including:</p>
                        <ul class="list-disc pl-6 space-y-1">
                            <li><strong>Personal Information:</strong> Name, email address, phone number, job position</li>
                            <li><strong>Business Information:</strong> Company name, USDOT number, MC number, EIN</li>
                            <li><strong>Financial Information:</strong> Banking details for payment processing</li>
                            <li><strong>Driver Information:</strong> CDL number, medical certificates, driving records</li>
                            <li><strong>Usage Data:</strong> How you interact with our Platform</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-slate-800">2. How We Use Your Information</h2>
                        <p>We use the collected information to:</p>
                        <ul class="list-disc pl-6 space-y-1">
                            <li>Provide, maintain, and improve our Platform</li>
                            <li>Process transactions and send related information</li>
                            <li>Verify carrier and driver credentials</li>
                            <li>Send you technical notices, updates, and support messages</li>
                            <li>Comply with legal obligations and regulatory requirements</li>
                            <li>Detect, investigate, and prevent fraudulent activities</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-slate-800">3. Information Sharing</h2>
                        <p>We may share your information in the following circumstances:</p>
                        <ul class="list-disc pl-6 space-y-1">
                            <li><strong>With Carriers/Drivers:</strong> To facilitate transportation services</li>
                            <li><strong>Service Providers:</strong> Third-party vendors who assist in our operations</li>
                            <li><strong>Legal Requirements:</strong> When required by law or to protect our rights</li>
                            <li><strong>Business Transfers:</strong> In connection with a merger or acquisition</li>
                        </ul>
                        <p>We do not sell your personal information to third parties.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-slate-800">4. Data Security</h2>
                        <p>We implement appropriate technical and organizational measures to protect your personal information, including encryption, access controls, and regular security assessments.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-slate-800">5. Data Retention</h2>
                        <p>We retain your personal information for as long as your account is active or as needed to provide services. We may also retain information as required by law for compliance, regulatory, and legal purposes.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-slate-800">6. Your Rights</h2>
                        <p>You have the right to:</p>
                        <ul class="list-disc pl-6 space-y-1">
                            <li>Access, update, or delete your personal information</li>
                            <li>Opt out of marketing communications</li>
                            <li>Request a copy of your data</li>
                            <li>Lodge a complaint with a data protection authority</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-slate-800">7. Cookies and Tracking</h2>
                        <p>We use cookies and similar technologies to enhance your experience, analyze usage patterns, and deliver personalized content. You can manage your cookie preferences through your browser settings.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-slate-800">8. Children's Privacy</h2>
                        <p>Our Platform is not intended for individuals under 18 years of age. We do not knowingly collect personal information from children.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-slate-800">9. Changes to This Policy</h2>
                        <p>We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new policy on this page and updating the "Last updated" date.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-slate-800">10. Contact Us</h2>
                        <p>If you have any questions about this Privacy Policy, please contact us at:</p>
                        <p class="font-medium">privacy@efservices.com</p>
                    </section>
                </div>
            </div>
        </div>
    </div>
</template>
