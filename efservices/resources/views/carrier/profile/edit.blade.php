@extends('../themes/' . $activeTheme)
@section('title', 'Edit Profile - ' . $carrier->name)

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
        ['label' => 'Company Profile', 'url' => route('carrier.profile')],
        ['label' => 'Edit Profile', 'active' => true],
    ];
@endphp

@pushOnce('styles')
    @vite('resources/css/vendors/toastify.css')
@endPushOnce

@section('subcontent')
<div class="grid grid-cols-12 gap-x-6 gap-y-10">
    {{-- Header --}}
    <div class="col-span-12">
        <div class="flex flex-col gap-y-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Edit Company Profile</h2>
                <p class="mt-1 text-slate-500">Update your company information and settings</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('carrier.profile') }}" 
                   class="flex items-center gap-2 px-4 py-2 text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                    <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                    Back to Profile
                </a>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <div class="col-span-12">
        <form action="{{ route('carrier.profile.update') }}" method="POST" enctype="multipart/form-data" id="profileForm">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-12 gap-6">
                {{-- Main Form --}}
                <div class="col-span-12 xl:col-span-8">
                    {{-- Company Information --}}
                    <div class="box box--stacked p-6 mb-6">
                        <div class="flex items-center gap-3 border-b border-slate-200/60 pb-4 mb-6">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary/10">
                                <x-base.lucide class="w-5 h-5 text-primary" icon="Building2" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-800">Company Information</h3>
                                <p class="text-sm text-slate-500">Basic details about your company</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {{-- Company Name --}}
                            <div class="md:col-span-2">
                                <x-base.form-label class="flex items-center gap-1">
                                    Company Name
                                    <span class="text-danger">*</span>
                                </x-base.form-label>
                                <x-base.form-input 
                                    type="text"
                                    name="name"
                                    value="{{ old('name', $carrier->name) }}"
                                    placeholder="Enter company name"
                                    class="{{ $errors->has('name') ? 'border-danger' : '' }}"
                                />
                                @error('name')
                                    <div class="mt-1 text-danger text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- EIN Number --}}
                            <div>
                                <x-base.form-label class="flex items-center gap-1">
                                    EIN Number
                                    <span class="text-danger">*</span>
                                </x-base.form-label>
                                <x-base.form-input 
                                    type="text"
                                    name="ein_number"
                                    value="{{ old('ein_number', $carrier->ein_number) }}"
                                    placeholder="XX-XXXXXXX"
                                    class="{{ $errors->has('ein_number') ? 'border-danger' : '' }}"
                                />
                                @error('ein_number')
                                    <div class="mt-1 text-danger text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Phone --}}
                            <div>
                                <x-base.form-label class="flex items-center gap-1">
                                    Phone Number
                                    <span class="text-danger">*</span>
                                </x-base.form-label>
                                <x-base.form-input 
                                    type="text"
                                    name="phone"
                                    value="{{ old('phone', $carrierDetail->phone) }}"
                                    placeholder="(XXX) XXX-XXXX"
                                    class="{{ $errors->has('phone') ? 'border-danger' : '' }}"
                                />
                                @error('phone')
                                    <div class="mt-1 text-danger text-sm">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Address Information --}}
                    <div class="box box--stacked p-6 mb-6">
                        <div class="flex items-center gap-3 border-b border-slate-200/60 pb-4 mb-6">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-info/10">
                                <x-base.lucide class="w-5 h-5 text-info" icon="MapPin" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-800">Address Information</h3>
                                <p class="text-sm text-slate-500">Company location details</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {{-- Address --}}
                            <div class="md:col-span-2">
                                <x-base.form-label class="flex items-center gap-1">
                                    Street Address
                                    <span class="text-danger">*</span>
                                </x-base.form-label>
                                <x-base.form-input 
                                    type="text"
                                    name="address"
                                    value="{{ old('address', $carrier->address) }}"
                                    placeholder="Enter street address"
                                    class="{{ $errors->has('address') ? 'border-danger' : '' }}"
                                />
                                @error('address')
                                    <div class="mt-1 text-danger text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- State --}}
                            <div>
                                <x-base.form-label class="flex items-center gap-1">
                                    State
                                    <span class="text-danger">*</span>
                                </x-base.form-label>
                                <select name="state" class="form-select w-full {{ $errors->has('state') ? 'border-danger' : '' }}">
                                    <option value="">Select State</option>
                                    @php
                                        $states = [
                                            'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
                                            'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
                                            'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
                                            'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
                                            'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
                                            'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
                                            'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
                                            'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
                                            'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
                                            'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
                                            'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
                                            'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
                                            'WI' => 'Wisconsin', 'WY' => 'Wyoming', 'DC' => 'District of Columbia'
                                        ];
                                    @endphp
                                    @foreach($states as $code => $name)
                                        <option value="{{ $code }}" {{ old('state', $carrier->state) == $code ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('state')
                                    <div class="mt-1 text-danger text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Zipcode --}}
                            <div>
                                <x-base.form-label class="flex items-center gap-1">
                                    ZIP Code
                                    <span class="text-danger">*</span>
                                </x-base.form-label>
                                <x-base.form-input 
                                    type="text"
                                    name="zipcode"
                                    value="{{ old('zipcode', $carrier->zipcode) }}"
                                    placeholder="XXXXX"
                                    maxlength="10"
                                    class="{{ $errors->has('zipcode') ? 'border-danger' : '' }}"
                                />
                                @error('zipcode')
                                    <div class="mt-1 text-danger text-sm">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- DOT & Authority Information --}}
                    <div class="box box--stacked p-6 mb-6">
                        <div class="flex items-center gap-3 border-b border-slate-200/60 pb-4 mb-6">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-success/10">
                                <x-base.lucide class="w-5 h-5 text-success" icon="Shield" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-800">DOT & Authority Information</h3>
                                <p class="text-sm text-slate-500">Federal and state authority numbers</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {{-- DOT Number --}}
                            <div>
                                <x-base.form-label class="flex items-center gap-1">
                                    USDOT Number
                                    <span class="text-danger">*</span>
                                </x-base.form-label>
                                <div class="relative">
                                    <x-base.form-input 
                                        type="text"
                                        name="dot_number"
                                        value="{{ old('dot_number', $carrier->dot_number) }}"
                                        placeholder="XXXXXXX"
                                        class="{{ $errors->has('dot_number') ? 'border-danger' : '' }}"
                                    />
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <x-base.lucide class="w-4 h-4 text-slate-400" icon="FileText" />
                                    </div>
                                </div>
                                @error('dot_number')
                                    <div class="mt-1 text-danger text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- MC Number --}}
                            <div>
                                <x-base.form-label>MC Number</x-base.form-label>
                                <div class="relative">
                                    <x-base.form-input 
                                        type="text"
                                        name="mc_number"
                                        value="{{ old('mc_number', $carrier->mc_number) }}"
                                        placeholder="MC-XXXXXX"
                                        class="{{ $errors->has('mc_number') ? 'border-danger' : '' }}"
                                    />
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <x-base.lucide class="w-4 h-4 text-slate-400" icon="Shield" />
                                    </div>
                                </div>
                                @error('mc_number')
                                    <div class="mt-1 text-danger text-sm">{{ $message }}</div>
                                @enderror
                                <p class="mt-1 text-xs text-slate-400">Motor Carrier Number (if applicable)</p>
                            </div>

                            {{-- State DOT --}}
                            <div>
                                <x-base.form-label>State DOT Number</x-base.form-label>
                                <x-base.form-input 
                                    type="text"
                                    name="state_dot"
                                    value="{{ old('state_dot', $carrier->state_dot) }}"
                                    placeholder="State DOT Number"
                                    class="{{ $errors->has('state_dot') ? 'border-danger' : '' }}"
                                />
                                @error('state_dot')
                                    <div class="mt-1 text-danger text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- IFTA Account --}}
                            <div>
                                <x-base.form-label>IFTA Account Number</x-base.form-label>
                                <x-base.form-input 
                                    type="text"
                                    name="ifta_account"
                                    value="{{ old('ifta_account', $carrier->ifta_account) }}"
                                    placeholder="IFTA Account Number"
                                    class="{{ $errors->has('ifta_account') ? 'border-danger' : '' }}"
                                />
                                @error('ifta_account')
                                    <div class="mt-1 text-danger text-sm">{{ $message }}</div>
                                @enderror
                                <p class="mt-1 text-xs text-slate-400">International Fuel Tax Agreement</p>
                            </div>
                        </div>
                    </div>

                    {{-- Form Actions --}}
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('carrier.profile') }}" 
                           class="px-6 py-2.5 text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="px-6 py-2.5 bg-primary hover:bg-primary/90 text-white font-medium rounded-lg transition-colors flex items-center gap-2">
                            <x-base.lucide class="w-4 h-4" icon="Save" />
                            Save Changes
                        </button>
                    </div>
                </div>

                {{-- Sidebar --}}
                <div class="col-span-12 xl:col-span-4">
                    {{-- Company Logo --}}
                    <div class="box box--stacked p-6 mb-6">
                        <div class="flex items-center gap-3 border-b border-slate-200/60 pb-4 mb-6">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-warning/10">
                                <x-base.lucide class="w-5 h-5 text-warning" icon="Image" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-800">Company Logo</h3>
                                <p class="text-sm text-slate-500">Upload your company logo</p>
                            </div>
                        </div>
                        
                        <div class="flex flex-col items-center">
                            <div class="relative mb-5">
                                <div class="w-36 h-36 rounded-full overflow-hidden border-4 border-slate-200 shadow-lg bg-slate-100">
                                    <img id="logoPreview"
                                         src="{{ $carrier->getFirstMediaUrl('logo_carrier') ?: asset('build/assets/images/placeholders/200x200.jpg') }}"
                                         alt="{{ $carrier->name }}"
                                         class="w-full h-full object-cover">
                                </div>
                                <label for="logo_carrier" 
                                       class="absolute bottom-1 right-1 w-10 h-10 bg-primary hover:bg-primary/90 text-white rounded-full flex items-center justify-center cursor-pointer shadow-lg transition-colors">
                                    <x-base.lucide class="w-5 h-5" icon="Camera" />
                                </label>
                            </div>
                            
                            <input type="file" 
                                   id="logo_carrier" 
                                   name="logo_carrier" 
                                   accept="image/*" 
                                   class="hidden"
                                   onchange="previewLogo(this)">
                            
                            <p class="text-xs text-slate-400 text-center">
                                Recommended: Square image, at least 200x200px<br>
                                Max file size: 2MB (JPG, PNG, WebP)
                            </p>
                        </div>
                    </div>

                    {{-- Current Info Summary --}}
                    <div class="box box--stacked p-6 mb-6">
                        <div class="flex items-center gap-3 border-b border-slate-200/60 pb-4 mb-4">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-slate-100">
                                <x-base.lucide class="w-5 h-5 text-slate-500" icon="Info" />
                            </div>
                            <h3 class="text-lg font-semibold text-slate-800">Current Info</h3>
                        </div>
                        
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                <span class="text-slate-500">Status</span>
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $carrier->status == 1 ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning' }}">
                                    {{ $carrier->status_name }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                <span class="text-slate-500">Created</span>
                                <span class="font-medium text-slate-700">{{ $carrier->created_at->format('M d, Y') }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                <span class="text-slate-500">Last Updated</span>
                                <span class="font-medium text-slate-700">{{ $carrier->updated_at->format('M d, Y') }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-slate-500">Referral Token</span>
                                <code class="px-2 py-1 bg-slate-100 rounded text-xs font-mono">{{ $carrier->referrer_token }}</code>
                            </div>
                        </div>
                    </div>

                    {{-- Help Card --}}
                    <div class="box box--stacked p-6 bg-gradient-to-br from-primary/5 to-transparent border-primary/20">
                        <div class="flex items-start gap-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary/10 flex-shrink-0">
                                <x-base.lucide class="w-5 h-5 text-primary" icon="HelpCircle" />
                            </div>
                            <div>
                                <h4 class="font-semibold text-slate-800 mb-1">Need Help?</h4>
                                <p class="text-sm text-slate-500 mb-3">
                                    If you need to update your DOT or MC numbers, please contact our support team.
                                </p>
                                <a href="mailto:support@efct.com" 
                                   class="text-sm text-primary hover:text-primary/80 font-medium flex items-center gap-1">
                                    <x-base.lucide class="w-4 h-4" icon="Mail" />
                                    Contact Support
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function previewLogo(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('logoPreview').src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Form validation feedback
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        const requiredFields = this.querySelectorAll('[required]');
        let hasErrors = false;
        
        requiredFields.forEach(function(field) {
            if (!field.value.trim()) {
                field.classList.add('border-danger');
                hasErrors = true;
            } else {
                field.classList.remove('border-danger');
            }
        });
        
        if (hasErrors) {
            e.preventDefault();
            if (typeof Toastify !== 'undefined') {
                Toastify({
                    text: "Please fill in all required fields",
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#EF4444",
                }).showToast();
            }
        }
    });
</script>
@endpush
