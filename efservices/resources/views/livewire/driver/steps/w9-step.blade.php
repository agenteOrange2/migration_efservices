<div class="bg-white rounded-lg shadow">
    <h3 class="text-lg font-semibold mb-4 border-b pb-2">W-9 Tax Form</h3>

    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            {{ session('error') }}
        </div>
    @endif
    @if (session()->has('warning'))
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4">
            {{ session('warning') }}
        </div>
    @endif

    <!-- PDF Download Link -->
    @if ($saved && $pdfPath)
        <div class="mb-6 p-4 border rounded-lg bg-blue-50 border-blue-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="text-sm font-medium text-blue-800">W-9 PDF generated successfully</span>
                </div>
                <a href="{{ route('admin.w9.download', $formId) }}" target="_blank"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Download W-9 PDF
                </a>
            </div>
        </div>
    @endif

    <!-- Line 1: Name -->
    <div class="mb-6 p-4 border rounded-lg bg-white shadow-sm">
        <h4 class="font-medium text-lg mb-3">Line 1 - Name <span class="text-red-500">*</span></h4>
        <p class="text-xs text-gray-500 mb-2">Name of entity/individual. An entry is required.</p>
        <input type="text" wire:model="name" 
               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
               placeholder="Enter name as shown on your income tax return">
        @error('name')
            <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
        @enderror
    </div>

    <!-- Line 2: Business Name -->
    <div class="mb-6 p-4 border rounded-lg bg-white shadow-sm">
        <h4 class="font-medium text-lg mb-3">Line 2 - Business Name</h4>
        <p class="text-xs text-gray-500 mb-2">Business name/disregarded entity name, if different from above.</p>
        <input type="text" wire:model="business_name"
               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
               placeholder="Business name (optional)">
        @error('business_name')
            <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
        @enderror
    </div>

    <!-- Line 3a: Tax Classification -->
    <div class="mb-6 p-4 border rounded-lg bg-white shadow-sm">
        <h4 class="font-medium text-lg mb-3">Line 3a - Federal Tax Classification <span class="text-red-500">*</span></h4>
        <p class="text-xs text-gray-500 mb-3">Check the appropriate box for federal tax classification. Check only one.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-4">
            @php
                $classifications = [
                    'individual' => 'Individual/sole proprietor or single-member LLC',
                    'c_corporation' => 'C Corporation',
                    's_corporation' => 'S Corporation',
                    'partnership' => 'Partnership',
                    'trust_estate' => 'Trust/estate',
                    'llc' => 'Limited liability company (LLC)',
                    'other' => 'Other (see instructions)',
                ];
            @endphp

            @foreach ($classifications as $value => $label)
                <label class="relative flex items-start p-3 border rounded-lg cursor-pointer transition-all
                    {{ $tax_classification === $value ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-500' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50' }}">
                    <input type="radio" wire:model.live="tax_classification" value="{{ $value }}"
                           class="form-radio h-4 w-4 text-indigo-600 border-gray-300 mt-0.5">
                    <span class="ml-2 text-sm text-gray-700">{{ $label }}</span>
                </label>
            @endforeach
        </div>
        @error('tax_classification')
            <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
        @enderror

        <!-- LLC Sub-options -->
        @if ($tax_classification === 'llc')
            <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    LLC Tax Classification <span class="text-red-500">*</span>
                </label>
                <p class="text-xs text-gray-500 mb-2">Enter the tax classification (C = C corporation, S = S corporation, P = Partnership)</p>
                <div class="flex items-center space-x-4">
                    @foreach (['C' => 'C Corporation', 'S' => 'S Corporation', 'P' => 'Partnership'] as $val => $lbl)
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" wire:model="llc_classification" value="{{ $val }}"
                                   class="form-radio h-4 w-4 text-indigo-600 border-gray-300">
                            <span class="ml-2 text-sm text-gray-700">{{ $val }} - {{ $lbl }}</span>
                        </label>
                    @endforeach
                </div>
                @error('llc_classification')
                    <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                @enderror
            </div>
        @endif

        <!-- Other text input -->
        @if ($tax_classification === 'other')
            <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Specify Other Classification <span class="text-red-500">*</span>
                </label>
                <input type="text" wire:model="other_classification"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="Enter classification">
                @error('other_classification')
                    <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                @enderror
            </div>
        @endif
    </div>

    <!-- Line 3b: Foreign Partners -->
    @if (in_array($tax_classification, ['partnership', 'trust_estate']) || ($tax_classification === 'llc' && $llc_classification === 'P'))
        <div class="mb-6 p-4 border rounded-lg bg-white shadow-sm">
            <h4 class="font-medium text-lg mb-3">Line 3b - Foreign Partners</h4>
            <div class="flex items-start">
                <input type="checkbox" id="has_foreign_partners" wire:model="has_foreign_partners"
                       class="form-checkbox h-4 w-4 text-indigo-600 border-gray-300 rounded mt-1 mr-2">
                <label for="has_foreign_partners" class="text-sm text-gray-700">
                    If on line 3a you checked "Partnership" or "Trust/estate," or checked "LLC" and entered "P" as its tax classification,
                    and you are providing this form to a partnership, trust, or estate in which you have an ownership interest, check
                    this box if you have any foreign partners, owners, or beneficiaries.
                </label>
            </div>
        </div>
    @endif

    <!-- Line 4: Exemptions -->
    <div class="mb-6 p-4 border rounded-lg bg-white shadow-sm">
        <h4 class="font-medium text-lg mb-3">Line 4 - Exemptions</h4>
        <p class="text-xs text-gray-500 mb-3">Codes apply only to certain entities, not individuals; see instructions on page 3.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Exempt payee code (if any)</label>
                <input type="text" wire:model="exempt_payee_code"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="Code">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">FATCA reporting code (if any)</label>
                <input type="text" wire:model="fatca_exemption_code"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="Code">
            </div>
        </div>
    </div>

    <!-- Line 5 & 6: Address -->
    <div class="mb-6 p-4 border rounded-lg bg-white shadow-sm">
        <h4 class="font-medium text-lg mb-3">Lines 5 & 6 - Address <span class="text-red-500">*</span></h4>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Address (number, street, and apt. or suite no.)</label>
            <input type="text" wire:model="address"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                   placeholder="Street address">
            @error('address')
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
            @enderror
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">City <span class="text-red-500">*</span></label>
                <input type="text" wire:model="city"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="City">
                @error('city')
                    <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">State <span class="text-red-500">*</span></label>
                <input type="text" wire:model="state" maxlength="2"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 uppercase"
                       placeholder="XX">
                @error('state')
                    <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ZIP Code <span class="text-red-500">*</span></label>
                <input type="text" wire:model="zip_code" maxlength="10"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="XXXXX">
                @error('zip_code')
                    <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>

    <!-- Line 7: Account Numbers -->
    <div class="mb-6 p-4 border rounded-lg bg-white shadow-sm">
        <h4 class="font-medium text-lg mb-3">Line 7 - Account Numbers</h4>
        <p class="text-xs text-gray-500 mb-2">List account number(s) here (optional).</p>
        <input type="text" wire:model="account_numbers"
               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
               placeholder="Account numbers (optional)">
    </div>

    <!-- Part I: TIN -->
    <div class="mb-6 p-4 border rounded-lg bg-white shadow-sm">
        <h4 class="font-medium text-lg mb-3">Part I - Taxpayer Identification Number (TIN) <span class="text-red-500">*</span></h4>
        <p class="text-xs text-gray-500 mb-3">Enter your TIN in the appropriate box. The TIN provided must match the name given on line 1.</p>

        <div class="flex items-center space-x-6 mb-4">
            <label class="inline-flex items-center cursor-pointer">
                <input type="radio" wire:model.live="tin_type" value="ssn"
                       class="form-radio h-4 w-4 text-indigo-600 border-gray-300">
                <span class="ml-2 text-sm font-medium text-gray-700">Social Security Number (SSN)</span>
            </label>
            <label class="inline-flex items-center cursor-pointer">
                <input type="radio" wire:model.live="tin_type" value="ein"
                       class="form-radio h-4 w-4 text-indigo-600 border-gray-300">
                <span class="ml-2 text-sm font-medium text-gray-700">Employer Identification Number (EIN)</span>
            </label>
        </div>

        <div class="max-w-sm">
            <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ $tin_type === 'ssn' ? 'Social Security Number' : 'Employer Identification Number' }}
            </label>
            <input type="password" wire:model="tin"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                   placeholder="{{ $tin_type === 'ssn' ? 'XXX-XX-XXXX' : 'XX-XXXXXXX' }}">
            @error('tin')
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <!-- Note: Signature will be applied from the Certification step when the application is completed -->
    <div class="mb-6 p-4 border rounded-lg bg-blue-50 border-blue-200">
        <div class="flex items-start space-x-2">
            <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-sm text-blue-800">The signature for Part II (Certification) will be applied automatically from the Certification step when the application is completed.</p>
        </div>
    </div>

    <!-- Save Button -->
    <div class="mb-6 p-4">
        <button type="button" wire:click="save"
                class="inline-flex items-center px-6 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition disabled:opacity-50"
                wire:loading.attr="disabled" wire:target="save">
            <span wire:loading.remove wire:target="save">
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Save W-9 & Generate PDF
            </span>
            <span wire:loading wire:target="save" class="inline-flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Generating...
            </span>
        </button>
    </div>

    <!-- Navigation Buttons -->
    <div class="mt-8 px-5 py-5 border-t border-gray-200">
        <div class="flex flex-col sm:flex-row justify-between gap-4">
            <button type="button" wire:click="previous"
                    class="w-full sm:w-44 inline-flex justify-center items-center px-4 py-2.5 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg> Previous
            </button>
            <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
                <button type="button" wire:click="saveAndExit"
                        class="w-full sm:w-44 inline-flex justify-center items-center px-4 py-2.5 bg-yellow-500 text-white text-sm font-medium rounded-lg hover:bg-yellow-600 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V8a2 2 0 00-2-2h-5L9 4H4z" />
                    </svg>
                    Save & Exit
                </button>
                <button type="button" wire:click="next"
                        class="w-full sm:w-44 inline-flex justify-center items-center px-4 py-2.5 bg-[#03045E] text-white text-sm font-medium rounded-lg hover:bg-[#020347] transition">
                    Next
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
