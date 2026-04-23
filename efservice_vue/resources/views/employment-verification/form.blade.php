<x-guest-layout>
    <style>
        /* Estilo sutil para el canvas de la firma */
        .signature-pad-wrapper {
            position: relative;
            background-color: #fff;
            border-radius: 0.75rem;
            /* rounded-xl */
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            /* shadow-sm */
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .signature-pad-wrapper:focus-within {
            border-color: #3b82f6;
            ring: 2px solid #93c5fd;
        }

        /* Personalización sutil de radios para que se vean modernos */
        .custom-radio:checked {
            background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3ccircle cx='8' cy='8' r='3'/%3e%3c/svg%3e");
            border-color: transparent;
            background-color: #040A60;
        }
    </style>

<div class="min-h-screen bg-slate-50 py-12 px-0 md:px-4 sm:px-6 lg:px-8 text-slate-600">

    <div class="max-w-6xl mx-auto mb-8 text-center">
        <div class="inline-flex items-center justify-center p-3 bg-blue-50 rounded-full mb-4">
            <svg class="w-8 h-8 text-[#040A60]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                </path>
            </svg>
        </div>
        <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Employment Verification</h1>
        <p class="mt-2 text-lg text-slate-500">Official Request for Information</p>
    </div>

    <div class="max-w-6xl mx-auto bg-white rounded-2xl shadow-sm shadow-slate-200/60 overflow-hidden border border-slate-100">

        <div class="bg-white border-b border-slate-100 px-8 py-6">
            <h2 class="text-xl font-semibold text-slate-800">
                To: <span class="text-[#040A60]">{{ $masterCompany ? $masterCompany->company_name : $employmentCompany->company_name ?? $verification->company_name }}</span>
            </h2>
            <p class="mt-2 text-[#040A60] leading-relaxed">
                <strong class="text-bg-700 font-medium">{{ $driver->user->name }} {{ $driver->last_name }}</strong>
                has listed your company as a previous employer in their employment history. As part of our
                verification process, we kindly request your confirmation of the following employment details:
            </p>
        </div>

        <form action="{{ route('employment-verification.process', $token) }}" method="POST" id="verificationForm"
            x-data="{
                datesCorrect: null,
                safeDriver: null,
                hadAccidents: null,
                reasonConfirmed: null,
                drugTest: null,
                alcoholTest: null,
                refusedTest: null,
                otherViolations: null
            }">
            @csrf

            <div class="p-3 md:p-6 space-y-10">

                <div class="bg-slate-50 rounded-xl p-6 border border-slate-200">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Candidate Information</h3>
                        <span class="px-3 py-1 bg-blue-100 text-[#040A60] text-xs font-semibold rounded-full">DOT Regulated</span>
                    </div>

                    <div class="mb-8 text-center border-b border-slate-200 pb-6">
                        <h4 class="text-base font-bold text-[#040A60] uppercase tracking-wide">Safety Performance History Investigation</h4>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mt-1 mb-3">Previous USDOT Regulated Employers</p>
                        <p class="text-xs text-slate-500 leading-relaxed max-w-4xl mx-auto">
                            In accordance with 49 CFR 40.25 and 391.23, we are hereby requesting that you supply us with the Safety Performance History of this individual. Under DOT rule 391.23(g), you must respond to this inquiry within 30 days of receipt.
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-xs text-slate-400 font-medium uppercase mb-1">Applicant Name</p>
                            <p class="text-slate-900 font-semibold text-lg">{{ $driver->user->name }} {{ $driver->last_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-medium uppercase mb-1">SSN (Last 4)</p>
                            <p class="text-slate-900 font-mono text-lg tracking-widest">
                                @if (isset($ssn))
                                    •••-••-{{ substr($ssn, -4) }}
                                @else
                                    N/A
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-medium uppercase mb-1">Employment Dates</p>
                            <p class="text-slate-900 font-medium">{{ $employmentCompany->employed_from }} — {{ $employmentCompany->employed_to }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-medium uppercase mb-1">Position</p>
                            <p class="text-slate-900 font-medium">{{ $employmentCompany->positions_held }}</p>
                        </div>
                    </div>

                    @if ($employmentCompany->subject_to_fmcsr || $employmentCompany->safety_sensitive_function)
                        <div class="mt-6 pt-4 border-t border-slate-200 grid grid-cols-1 md:grid-cols-2 gap-4">
                            @if ($employmentCompany->subject_to_fmcsr)
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-sm text-slate-600">Subject to FMCSR</span>
                                </div>
                            @endif
                            @if ($employmentCompany->safety_sensitive_function)
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-sm text-slate-600">Performed Safety-Sensitive Functions</span>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="mt-5">
                    <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center">
                        <span class="bg-[#040A60] w-1.5 h-6 rounded-r mr-3"></span>
                        Safety Performance History Questions
                    </h3>

                    <div class="space-y-8">

                        <div class="group">
                            <label class="block text-slate-800 font-medium mb-3">1. Are the dates of employment correct as stated above?</label>
                            <div class="flex space-x-6">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio" name="dates_confirmed" value="1" x-model="datesCorrect"
                                        class="w-5 h-5 text-[#040A60] border-gray-300 focus:ring-blue-900" required>
                                    <span class="ml-2">Yes</span>
                                </label>
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio" name="dates_confirmed" value="0" x-model="datesCorrect"
                                        class="w-5 h-5 text-[#040A60] border-gray-300 focus:ring-blue-900">
                                    <span class="ml-2">No</span>
                                </label>
                            </div>
                            <div x-show="datesCorrect === '0'" x-transition class="mt-3 pl-4 border-l-2 border-blue-200">
                                <label class="block text-sm text-slate-500 mb-1">Please provide correct dates:</label>
                                <input type="text" name="correct_dates"
                                    class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm placeholder-slate-400">
                            </div>
                        </div>

                        <div class="group border-t border-slate-100 pt-6">
                            <label class="block text-slate-800 font-medium mb-3">2. Did the applicant drive commercial vehicles for your company?</label>
                            <div class="flex space-x-6">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio" name="drove_commercial" value="1"
                                        class="w-5 h-5 text-[#040A60] border-gray-300 focus:ring-blue-900" required>
                                    <span class="ml-2">Yes</span>
                                </label>
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio" name="drove_commercial" value="0"
                                        class="w-5 h-5 text-[#040A60] border-gray-300 focus:ring-blue-900">
                                    <span class="ml-2">No</span>
                                </label>
                            </div>
                        </div>

                        <div class="group border-t border-slate-100 pt-6">
                            <label class="block text-slate-800 font-medium mb-3">3. Was the applicant a safe and efficient driver?</label>
                            <div class="flex space-x-6">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio" name="safe_driver" value="1" x-model="safeDriver"
                                        class="w-5 h-5 text-[#040A60] border-gray-300 focus:ring-blue-900" required>
                                    <span class="ml-2">Yes</span>
                                </label>
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio" name="safe_driver" value="0" x-model="safeDriver"
                                        class="w-5 h-5 text-[#040A60] border-gray-300 focus:ring-blue-900">
                                    <span class="ml-2">No</span>
                                </label>
                            </div>
                            <div x-show="safeDriver === '0'" x-transition class="mt-3 pl-4 border-l-2 border-blue-200">
                                <textarea name="unsafe_driver_details" rows="2"
                                    class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                                    placeholder="Please explain why..."></textarea>
                            </div>
                        </div>

                        <div class="group border-t border-slate-100 pt-6">
                            <label class="block text-slate-800 font-medium mb-3">4. Was the applicant involved in any vehicle accidents while employed with your company?</label>
                            <div class="flex space-x-6">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio" name="had_accidents" value="1" x-model="hadAccidents"
                                        class="w-5 h-5 text-[#040A60] border-gray-300 focus:ring-blue-900" required>
                                    <span class="ml-2">Yes</span>
                                </label>
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio" name="had_accidents" value="0" x-model="hadAccidents"
                                        class="w-5 h-5 text-[#040A60] border-gray-300 focus:ring-blue-900">
                                    <span class="ml-2">No</span>
                                </label>
                            </div>
                            <div x-show="hadAccidents === '1'" x-transition class="mt-3 pl-4 border-l-2 border-blue-200">
                                <textarea name="accidents_details" rows="3"
                                    class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                                    placeholder="Date, description, and outcome of each accident..."></textarea>
                            </div>
                        </div>

                        <div class="group border-t border-slate-100 pt-6">
                            <label class="block text-slate-800 font-medium mb-3">5. Reason for leaving your employment:</label>
                            <div class="bg-blue-50/50 p-4 rounded-lg mb-3 border border-blue-100">
                                <p class="text-sm text-blue-800 font-medium">Stated reason: "{{ $employmentCompany->reason_for_leaving }}"</p>
                            </div>
                            <div class="flex space-x-6">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio" name="reason_confirmed" value="1" x-model="reasonConfirmed"
                                        class="w-5 h-5 text-[#040A60] border-gray-300 focus:ring-blue-900" required>
                                    <span class="ml-2">Confirm</span>
                                </label>
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio" name="reason_confirmed" value="0" x-model="reasonConfirmed"
                                        class="w-5 h-5 text-[#040A60] border-gray-300 focus:ring-blue-900">
                                    <span class="ml-2">Different Reason</span>
                                </label>
                            </div>
                            <div x-show="reasonConfirmed === '0'" x-transition class="mt-3 pl-4 border-l-2 border-blue-200">
                                <input type="text" name="different_reason"
                                    class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                                    placeholder="Specify correct reason">
                            </div>
                        </div>

                        <div class="border-t border-slate-100 pt-6">
                            <h4 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-6">Drug & Alcohol History (Last 3 Years)</h4>

                            <div class="grid grid-cols-1 gap-8">
                                <div>
                                    <label class="block text-slate-800 font-medium mb-2">6. Has the applicant tested positive for a controlled substance in the last three (3) years?</label>
                                    <div class="flex items-center space-x-4 mb-2">
                                        <label class="inline-flex items-center"><input type="radio"
                                                name="positive_drug_test" value="1" x-model="drugTest"
                                                class="w-4 h-4 text-[#040A60] border-gray-300"><span
                                                class="ml-2">Yes</span></label>
                                        <label class="inline-flex items-center"><input type="radio"
                                                name="positive_drug_test" value="0" x-model="drugTest"
                                                class="w-4 h-4 text-[#040A60] border-gray-300"><span
                                                class="ml-2">No</span></label>
                                    </div>
                                    <div x-show="drugTest === '1'" x-transition class="mt-2"><input type="text" name="drug_test_details"
                                            class="w-full text-sm rounded-md border-slate-300"
                                            placeholder="Date and substance"></div>
                                </div>

                                <div>
                                    <label class="block text-slate-800 font-medium mb-2">7. Has the applicant had an alcohol test with a B.A.C. of 0.04 or greater in the last three (3) years?</label>
                                    <div class="flex items-center space-x-4 mb-2">
                                        <label class="inline-flex items-center"><input type="radio"
                                                name="positive_alcohol_test" value="1" x-model="alcoholTest"
                                                class="w-4 h-4 text-[#040A60] border-gray-300"><span
                                                class="ml-2">Yes</span></label>
                                        <label class="inline-flex items-center"><input type="radio"
                                                name="positive_alcohol_test" value="0" x-model="alcoholTest"
                                                class="w-4 h-4 text-[#040A60] border-gray-300"><span
                                                class="ml-2">No</span></label>
                                    </div>
                                    <div x-show="alcoholTest === '1'" x-transition class="mt-2"><input type="text" name="alcohol_test_details"
                                            class="w-full text-sm rounded-md border-slate-300"
                                            placeholder="Date and level"></div>
                                </div>

                                <div>
                                    <label class="block text-slate-800 font-medium mb-2">8. Has the applicant refused a required test for drugs or alcohol in the last three (3) years?</label>
                                    <div class="flex items-center space-x-4 mb-2">
                                        <label class="inline-flex items-center"><input type="radio"
                                                name="refused_test" value="1" x-model="refusedTest"
                                                class="w-4 h-4 text-[#040A60] border-gray-300"><span
                                                class="ml-2">Yes</span></label>
                                        <label class="inline-flex items-center"><input type="radio"
                                                name="refused_test" value="0" x-model="refusedTest"
                                                class="w-4 h-4 text-[#040A60] border-gray-300"><span
                                                class="ml-2">No</span></label>
                                    </div>
                                    <div x-show="refusedTest === '1'" x-transition class="mt-2"><input type="text" name="refused_test_details"
                                            class="w-full text-sm rounded-md border-slate-300"
                                            placeholder="Details of refusal"></div>
                                </div>

                                <div>
                                    <label class="block text-slate-800 font-medium mb-2" for="completed_rehab_yes">9. Did the applicant complete a substance abuse rehabilitation program, if required?</label>
                                    <div class="flex items-center space-x-4 mb-2">
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="completed_rehab" id="completed_rehab_yes" value="1"
                                                class="w-4 h-4 text-[#040A60] border-gray-300">
                                            <span class="ml-2">Yes</span></label>
                                        <label class="inline-flex items-center"><input type="radio"
                                                name="completed_rehab" id="completed_rehab_no" value="0"
                                                class="w-4 h-4 text-[#040A60] border-gray-300"><span
                                                class="ml-2">No</span></label>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-slate-800 font-medium mb-2">10. Other DOT violations?</label>
                                    <div class="flex items-center space-x-4 mb-2">
                                        <label class="inline-flex items-center"><input type="radio"
                                                name="other_violations" value="1" x-model="otherViolations"
                                                class="w-4 h-4 text-[#040A60] border-gray-300"><span
                                                class="ml-2">Yes</span></label>
                                        <label class="inline-flex items-center"><input type="radio"
                                                name="other_violations" value="0" x-model="otherViolations"
                                                class="w-4 h-4 text-[#040A60] border-gray-300"><span
                                                class="ml-2">No</span></label>
                                    </div>
                                    <div x-show="otherViolations === '1'" x-transition class="mt-2"><input type="text" name="violation_details"
                                            class="w-full text-sm rounded-md border-slate-300"
                                            placeholder="Violation details"></div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="bg-slate-50 rounded-xl p-3 md:p-6 border border-slate-200 mt-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <div class="space-y-4">
                            <div class="flex items-center p-4 bg-white rounded-lg border border-slate-200 shadow-sm">
                                <input type="checkbox" name="employment_confirmed" id="employment_confirmed" value="1"
                                    class="w-5 h-5 text-[#040A60] rounded border-gray-300 focus:ring-blue-900" required>
                                <label for="employment_confirmed" class="ml-3 font-medium text-slate-700">I confirm this person was employed here</label>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Verification Decision</label>
                                <select name="verification_status"
                                    class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-900 focus:ring-blue-900" required>
                                    <option value="verified">Verified - Information Correct</option>
                                    <option value="rejected">Rejected - Information Incorrect</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Verified By (Full Name)</label>
                                <input type="text" name="verification_by"
                                    class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"
                                    placeholder="John Doe" required>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-slate-700">Digital Signature</label>
                            <div class="signature-pad-wrapper h-48 w-full bg-white relative cursor-crosshair">
                                <canvas id="signature-pad" class="absolute inset-0 w-full h-full"></canvas>
                                <input type="hidden" name="signature" id="signature-data">
                                <div class="absolute bottom-2 right-2 text-xs text-slate-300 pointer-events-none select-none">Sign inside the box</div>
                            </div>
                            <button type="button" id="clear-signature"
                                class="text-sm text-[#040A60] hover:text-blue-800 font-medium">Clear Signature</button>
                        </div>
                    </div>

                    <div class="mt-6">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Additional Comments (Optional)</label>
                        <textarea name="verification_notes" rows="2"
                            class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"></textarea>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" id="submit-verification"
                        class="w-full flex justify-center py-4 px-6 border border-transparent rounded-lg shadow-sm text-base font-bold text-white bg-blue-900 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-900 transition-all duration-200 transform hover:scale-[1.01]">
                        Submit Official Verification
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Función de ayuda para verificar si un elemento existe
            function elementExists(id) {
                const element = document.getElementById(id);
                if (!element) {
                    console.warn(`Elemento con ID '${id}' no encontrado en el DOM`);
                    return false;
                }
                return element;
            }

            // Verificar que la biblioteca SignaturePad esté cargada
            if (typeof SignaturePad === 'undefined') {
                console.error('SignaturePad library not loaded');
                return;
            }

            // Inicializar SignaturePad
            let signaturePad = null;
            const canvas = elementExists('signature-pad');

            if (canvas) {
                try {
                    signaturePad = new SignaturePad(canvas, {
                        backgroundColor: 'rgb(255, 255, 255)',
                        penColor: 'rgb(0, 0, 0)'
                    });
                    console.log('SignaturePad initialized successfully');
                } catch (error) {
                    console.error('Error initializing SignaturePad:', error);
                }
            } else {
                console.error('Canvas element not found');
            }

            const loadingOverlay = elementExists('loading-overlay');

            // Función para redimensionar el canvas
            function resizeCanvas() {
                try {
                    if (canvas && signaturePad) {
                        const ratio = Math.max(window.devicePixelRatio || 1, 1);
                        const rect = canvas.getBoundingClientRect();
                        const oldData = signaturePad.isEmpty() ? null : signaturePad.toDataURL();

                        // Redimensionar el canvas
                        canvas.width = rect.width * ratio;
                        canvas.height = rect.height * ratio;
                        canvas.style.width = rect.width + 'px';
                        canvas.style.height = rect.height + 'px';

                        const ctx = canvas.getContext('2d');
                        ctx.scale(ratio, ratio);

                        // Limpiar el signaturePad y configurar de nuevo
                        signaturePad.clear();

                        // Si había una firma, restaurarla
                        if (oldData) {
                            signaturePad.fromDataURL(oldData);
                        }

                        console.log('Canvas redimensionado correctamente');
                    }
                } catch (error) {
                    console.error('Error al redimensionar el canvas:', error);
                }
            }

            // Redimensionar el canvas al cargar
            if (canvas && signaturePad) {
                resizeCanvas();

                // Verificar que window exista antes de agregar el event listener
                if (typeof window !== 'undefined') {
                    window.addEventListener('resize', resizeCanvas);
                    console.log('Event listener de resize agregado correctamente');
                }
            }

            // Botón para limpiar firma
            const clearButton = elementExists('clear-signature');
            if (clearButton) {
                try {
                    clearButton.addEventListener('click', function() {
                        if (signaturePad) {
                            signaturePad.clear();
                            console.log('Firma limpiada correctamente');
                        }
                    });
                } catch (error) {
                    console.error('Error al agregar evento al botón de limpiar firma:', error);
                }
            }

            // Manejar campos condicionales - usando el mismo patrón que funciona para pregunta 1 y 5
            // Función para configurar toggle de radio buttons
            function setupRadioToggle(showRadioId, hideRadioId, containerId) {
                var showRadio = document.getElementById(showRadioId);
                var hideRadio = document.getElementById(hideRadioId);
                var container = document.getElementById(containerId);

                if (showRadio && container) {
                    showRadio.addEventListener('change', function() {
                        if (this.checked) container.classList.remove('hidden');
                    });
                }
                if (hideRadio && container) {
                    hideRadio.addEventListener('change', function() {
                        if (this.checked) container.classList.add('hidden');
                    });
                }
            }

            // Pregunta 1 - Fechas (No = mostrar)
            setupRadioToggle('dates_confirmed_no', 'dates_confirmed_yes', 'correct_dates_container');

            // Pregunta 3 - Safe driver (No = mostrar)
            setupRadioToggle('safe_driver_no', 'safe_driver_yes', 'unsafe_driver_container');

            // Pregunta 4 - Accidentes (Yes = mostrar)
            setupRadioToggle('had_accidents_yes', 'had_accidents_no', 'accidents_details_container');

            // Pregunta 5 - Razón (Different = mostrar)
            setupRadioToggle('reason_confirmed_no', 'reason_confirmed_yes', 'different_reason_container');

            // Pregunta 6 - Drug test (Yes = mostrar)
            setupRadioToggle('positive_drug_test_yes', 'positive_drug_test_no', 'drug_test_details_container');

            // Pregunta 7 - Alcohol test (Yes = mostrar)
            setupRadioToggle('positive_alcohol_test_yes', 'positive_alcohol_test_no',
                'alcohol_test_details_container');

            // Pregunta 8 - Refused test (Yes = mostrar)
            setupRadioToggle('refused_test_yes', 'refused_test_no', 'refused_test_details_container');

            // Pregunta 10 - Violations (Yes = mostrar)
            setupRadioToggle('other_violations_yes', 'other_violations_no', 'violations_details_container');

            // Submit form
            const submitButton = elementExists('submit-verification');
            if (submitButton) {
                submitButton.addEventListener('click', function(e) {
                    e.preventDefault(); // Prevenir envío múltiple

                    try {
                        // Validar campos requeridos
                        const employmentConfirmed = elementExists('employment_confirmed');
                        if (employmentConfirmed && !employmentConfirmed.checked) {
                            alert('Please confirm employment');
                            return;
                        }

                        // Validar radio buttons requeridos
                        const requiredRadioGroups = [
                            'dates_confirmed',
                            'drove_commercial',
                            'safe_driver',
                            'had_accidents',
                            'reason_confirmed',
                            'positive_drug_test',
                            'positive_alcohol_test',
                            'refused_test',
                            'completed_rehab',
                            'other_violations'
                        ];

                        let missingFields = [];

                        requiredRadioGroups.forEach(function(groupName) {
                            if (!document.querySelector(`input[name="${groupName}"]:checked`)) {
                                missingFields.push(groupName.replace(/_/g, ' ').replace(/\b\w/g,
                                    l => l.toUpperCase()));
                            }
                        });

                        if (missingFields.length > 0) {
                            alert('Please answer all required questions: ' + missingFields.join(', '));
                            return;
                        }

                        // Verificar firma
                        if (signaturePad && signaturePad.isEmpty()) {
                            alert('Please provide a signature');
                            return;
                        }

                        // Mostrar overlay de carga
                        if (loadingOverlay) loadingOverlay.style.display = 'flex';

                        // Deshabilitar botón de envío
                        submitButton.disabled = true;

                        // Get signature data as PNG image
                        let signatureData = '';
                        if (signaturePad) {
                            signatureData = signaturePad.toDataURL('image/png');
                        }

                        // Guardar la firma en el campo oculto
                        const signatureField = elementExists('signature-data');
                        if (signatureField) signatureField.value = signatureData;

                        // Crear el objeto FormData con el formulario actual
                        const form = document.querySelector('form');
                        if (!form) {
                            throw new Error('Form not found');
                        }

                        const formData = new FormData(form);

                        // Asegurarse de que la firma esté incluida
                        if (signatureData) formData.set('signature', signatureData);

                        // Submit form via AJAX
                        const url = form.getAttribute('action');
                        console.log('Enviando formulario a:', url);

                        fetch(url, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            })
                            .then(response => {
                                console.log('Respuesta recibida:', response.status);
                                if (!response.ok) {
                                    if (response.status === 422) {
                                        return response.json().then(data => {
                                            throw new Error(data.message || 'Validation error');
                                        });
                                    }
                                    throw new Error(`HTTP error! status: ${response.status}`);
                                }
                                return response.json();
                            })
                            .then(data => {
                                console.log('Datos recibidos:', data);
                                if (data.success) {
                                    console.log('Redirigiendo a página de agradecimiento');
                                    // Usar redirect o redirect_url según lo que devuelva el servidor
                                    window.location.href = data.redirect || data.redirect_url ||
                                        '/employment-verification/thank-you';
                                } else {
                                    throw new Error(data.message || 'Unknown error occurred');
                                }
                            })
                            .catch(error => {
                                console.error('Error en la petición:', error);
                                alert('Ha ocurrido un error al procesar la verificación: ' + error
                                    .message);
                            })
                            .finally(() => {
                                // Ocultar overlay y rehabilitar botón
                                if (loadingOverlay) loadingOverlay.style.display = 'none';
                                submitButton.disabled = false;
                            });

                    } catch (error) {
                        console.error('Error en el proceso de envío:', error);
                        alert('Ha ocurrido un error al preparar el formulario: ' + error.message);
                        if (loadingOverlay) loadingOverlay.style.display = 'none';
                        submitButton.disabled = false;
                    }
                });
            }
        });
    </script>
</x-guest-layout>
