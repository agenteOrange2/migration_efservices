@extends('../themes/' . $activeTheme)
@section('title', 'Edit License')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Licenses', 'url' => route('admin.licenses.index')],
        ['label' => 'Edit', 'active' => true],
    ];
@endphp

@section('subcontent')
    <div>
        <!-- Flash Messages -->
        @if (session()->has('success'))
            <div class="alert alert-success flex items-center mb-5">
                <x-base.lucide class="w-6 h-6 mr-2" icon="check-circle" />
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert alert-danger flex items-center mb-5">
                <x-base.lucide class="w-6 h-6 mr-2" icon="alert-circle" />
                {{ session('error') }}
            </div>
        @endif

        <!-- Professional Header -->
        <div class="box box--stacked p-8 mb-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <x-base.lucide class="w-8 h-8 text-primary" icon="UserCheck" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-2">Edit License</h1>
                        <p class="text-slate-600">Edit license: {{ $license->license_number }}</p>
                    </div>
                </div>
                <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                    <x-base.button as="a" href="{{ route('admin.licenses.index') }}" class="w-full sm:w-auto"
                        variant="primary">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="arrow-left" />
                        Back to Licenses
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('admin.licenses.show', $license->id) }}"
                        class="w-full sm:w-auto">
                        <x-base.lucide class="w-4 h-4 mr-1" icon="file-text" />
                        View Documents
                    </x-base.button>
                </div>
            </div>
        </div>

        <!-- Contenido principal -->
        <div class="box box--stacked mt-5">
            <div class="box-body p-5">
                <form id="licenseForm" action="{{ route('admin.licenses.update', $license) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <!-- Sección 1: Información Básica -->
                    <div class="mb-8">
                        <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">Edit Information</h4>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Carrier -->
                            <div>
                                <x-base.form-label for="carrier_id" class="form-label required">Carrier</x-base.form-label>
                                <x-base.form-select id="carrier_id" name="carrier_id"
                                    class="form-select @error('carrier_id') is-invalid @enderror" required>
                                    <option value="">Select Carrier</option>
                                    @foreach ($carriers as $carrier)
                                        <option value="{{ $carrier->id }}"
                                            {{ old('carrier_id', $license->driverDetail->carrier_id) == $carrier->id ? 'selected' : '' }}>
                                            {{ $carrier->name }}
                                        </option>
                                    @endforeach
                                </x-base.form-select>
                                @error('carrier_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Driver -->
                            <div>
                                <x-base.form-label for="user_driver_detail_id"
                                    class="form-label required">Driver</x-base.form-label>
                                <x-base.form-select id="user_driver_detail_id" name="user_driver_detail_id"
                                    class="form-select @error('user_driver_detail_id') is-invalid @enderror" required>
                                    <option value="">Select Driver</option>
                                    @foreach ($drivers as $driver)
                                        <option value="{{ $driver->id }}"
                                            {{ old('user_driver_detail_id', $license->user_driver_detail_id) == $driver->id ? 'selected' : '' }}>
                                            {{ implode(' ', array_filter([$driver->user->name, $driver->middle_name, $driver->last_name])) }}
                                        </option>
                                    @endforeach
                                </x-base.form-select>
                                @error('user_driver_detail_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Sección 2: Información de Licencia -->
                    <div class="mb-8">
                        <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">License Information</h4>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- License Number -->
                            <div>
                                <x-base.form-label for="license_number" class="form-label required">License
                                    Number</x-base.form-label>
                                <x-base.form-input type="text" id="license_number" name="license_number"
                                    class="form-control @error('license_number') is-invalid @enderror"
                                    value="{{ old('license_number', $license->license_number) }}" required />
                                @error('license_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- License Class -->
                            <div>
                                <x-base.form-label for="license_class" class="form-label">License Class</x-base.form-label>
                                <x-base.form-select id="license_class" name="license_class"
                                    class="form-select @error('license_class') is-invalid @enderror">
                                    <option value="">Select License Class</option>
                                    <option value="A"
                                        {{ old('license_class', $license->license_class) == 'A' ? 'selected' : '' }}>Class
                                        A</option>
                                    <option value="B"
                                        {{ old('license_class', $license->license_class) == 'B' ? 'selected' : '' }}>Class
                                        B</option>
                                    <option value="C"
                                        {{ old('license_class', $license->license_class) == 'C' ? 'selected' : '' }}>Class
                                        C</option>
                                </x-base.form-select>
                                @error('license_class')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- State of Issue -->
                            <div>
                                <x-base.form-label for="state_of_issue" class="form-label">State of
                                    Issue</x-base.form-label>
                                <x-base.form-select id="state_of_issue" name="state_of_issue"
                                    class="form-select @error('state_of_issue') is-invalid @enderror">
                                    <option value="">Select State</option>
                                    <option value="AL"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'AL' ? 'selected' : '' }}>
                                        Alabama</option>
                                    <option value="AK"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'AK' ? 'selected' : '' }}>
                                        Alaska</option>
                                    <option value="AZ"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'AZ' ? 'selected' : '' }}>
                                        Arizona</option>
                                    <option value="AR"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'AR' ? 'selected' : '' }}>
                                        Arkansas</option>
                                    <option value="CA"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'CA' ? 'selected' : '' }}>
                                        California</option>
                                    <option value="CO"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'CO' ? 'selected' : '' }}>
                                        Colorado</option>
                                    <option value="CT"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'CT' ? 'selected' : '' }}>
                                        Connecticut</option>
                                    <option value="DE"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'DE' ? 'selected' : '' }}>
                                        Delaware</option>
                                    <option value="FL"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'FL' ? 'selected' : '' }}>
                                        Florida</option>
                                    <option value="GA"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'GA' ? 'selected' : '' }}>
                                        Georgia</option>
                                    <option value="HI"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'HI' ? 'selected' : '' }}>
                                        Hawaii</option>
                                    <option value="ID"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'ID' ? 'selected' : '' }}>
                                        Idaho</option>
                                    <option value="IL"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'IL' ? 'selected' : '' }}>
                                        Illinois</option>
                                    <option value="IN"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'IN' ? 'selected' : '' }}>
                                        Indiana</option>
                                    <option value="IA"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'IA' ? 'selected' : '' }}>
                                        Iowa</option>
                                    <option value="KS"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'KS' ? 'selected' : '' }}>
                                        Kansas</option>
                                    <option value="KY"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'KY' ? 'selected' : '' }}>
                                        Kentucky</option>
                                    <option value="LA"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'LA' ? 'selected' : '' }}>
                                        Louisiana</option>
                                    <option value="ME"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'ME' ? 'selected' : '' }}>
                                        Maine</option>
                                    <option value="MD"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'MD' ? 'selected' : '' }}>
                                        Maryland</option>
                                    <option value="MA"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'MA' ? 'selected' : '' }}>
                                        Massachusetts</option>
                                    <option value="MI"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'MI' ? 'selected' : '' }}>
                                        Michigan</option>
                                    <option value="MN"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'MN' ? 'selected' : '' }}>
                                        Minnesota</option>
                                    <option value="MS"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'MS' ? 'selected' : '' }}>
                                        Mississippi</option>
                                    <option value="MO"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'MO' ? 'selected' : '' }}>
                                        Missouri</option>
                                    <option value="MT"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'MT' ? 'selected' : '' }}>
                                        Montana</option>
                                    <option value="NE"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'NE' ? 'selected' : '' }}>
                                        Nebraska</option>
                                    <option value="NV"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'NV' ? 'selected' : '' }}>
                                        Nevada</option>
                                    <option value="NH"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'NH' ? 'selected' : '' }}>New
                                        Hampshire</option>
                                    <option value="NJ"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'NJ' ? 'selected' : '' }}>New
                                        Jersey</option>
                                    <option value="NM"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'NM' ? 'selected' : '' }}>New
                                        Mexico</option>
                                    <option value="NY"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'NY' ? 'selected' : '' }}>New
                                        York</option>
                                    <option value="NC"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'NC' ? 'selected' : '' }}>
                                        North Carolina</option>
                                    <option value="ND"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'ND' ? 'selected' : '' }}>
                                        North Dakota</option>
                                    <option value="OH"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'OH' ? 'selected' : '' }}>
                                        Ohio</option>
                                    <option value="OK"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'OK' ? 'selected' : '' }}>
                                        Oklahoma</option>
                                    <option value="OR"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'OR' ? 'selected' : '' }}>
                                        Oregon</option>
                                    <option value="PA"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'PA' ? 'selected' : '' }}>
                                        Pennsylvania</option>
                                    <option value="RI"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'RI' ? 'selected' : '' }}>
                                        Rhode Island</option>
                                    <option value="SC"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'SC' ? 'selected' : '' }}>
                                        South Carolina</option>
                                    <option value="SD"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'SD' ? 'selected' : '' }}>
                                        South Dakota</option>
                                    <option value="TN"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'TN' ? 'selected' : '' }}>
                                        Tennessee</option>
                                    <option value="TX"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'TX' ? 'selected' : '' }}>
                                        Texas</option>
                                    <option value="UT"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'UT' ? 'selected' : '' }}>
                                        Utah</option>
                                    <option value="VT"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'VT' ? 'selected' : '' }}>
                                        Vermont</option>
                                    <option value="VA"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'VA' ? 'selected' : '' }}>
                                        Virginia</option>
                                    <option value="WA"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'WA' ? 'selected' : '' }}>
                                        Washington</option>
                                    <option value="WV"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'WV' ? 'selected' : '' }}>
                                        West Virginia</option>
                                    <option value="WI"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'WI' ? 'selected' : '' }}>
                                        Wisconsin</option>
                                    <option value="WY"
                                        {{ old('state_of_issue', $license->state_of_issue) == 'WY' ? 'selected' : '' }}>
                                        Wyoming</option>
                                </x-base.form-select>
                                @error('state_of_issue')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Expiration Date -->
                            <div>
                                <x-base.form-label for="expiration_date" class="form-label required">Expiration
                                    Date</x-base.form-label>
                                <x-base.litepicker id="date_end" name="expiration_date"
                                    value="{{ old('expiration_date', $license->expiration_date ? $license->expiration_date->format('m/d/Y') : '') }}"
                                    class="@error('expiration_date') @enderror" placeholder="MM/DD/YYYY" required />
                                @error('expiration_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Sección 3: CDL y Endorsements -->
                    <div class="mb-8">
                        <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">CDL Information</h4>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- CDL Checkbox -->
                            <div>
                                <x-base.form-label class="form-label">Commercial Driver's License (CDL)</x-base.form-label>
                                <div class="flex items-center mb-2">
                                    <input id="is_cdl" name="is_cdl" type="checkbox" value="1"
                                        {{ old('is_cdl', $license->is_cdl) ? 'checked' : '' }}
                                        class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                    <label for="is_cdl" class="form-check-label ml-2">
                                        This is a CDL License
                                    </label>
                                </div>
                                @error('is_cdl')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Primary License Checkbox -->
                            <div>
                                <x-base.form-label class="form-label">Primary License</x-base.form-label>
                                <div class="flex items-center mb-2">
                                    <input id="is_primary" name="is_primary" type="checkbox" value="1"
                                        {{ old('is_primary', $license->is_primary) ? 'checked' : '' }}
                                        class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                    <label for="is_primary" class="form-check-label ml-2">
                                        Set as primary license
                                    </label>
                                </div>
                                <p class="text-xs text-slate-500">If checked, this will be set as the driver's primary license.</p>
                                @error('is_primary')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- CDL Endorsements -->
                            <div id="cdl_endorsements" class="{{ old('is_cdl', $license->is_cdl) ? '' : 'hidden' }}">
                                <x-base.form-label class="form-label">CDL Endorsements</x-base.form-label>
                                @php
                                    $currentEndorsements = old(
                                        'endorsements',
                                        $license->endorsements->pluck('code')->toArray(),
                                    );
                                @endphp
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-2">
                                    <div class="flex items-center">
                                        <input id="endorsement_n" name="endorsement_n" type="checkbox" value="1"
                                            {{ in_array('N', $currentEndorsements) ? 'checked' : '' }}
                                            class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                        <label for="endorsement_n" class="form-check-label ml-2">
                                            N - Tank Vehicle
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="endorsement_h" name="endorsement_h" type="checkbox" value="1"
                                            {{ in_array('H', $currentEndorsements) ? 'checked' : '' }}
                                            class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                        <label for="endorsement_h" class="form-check-label ml-2">
                                            H - Hazardous Materials
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="endorsement_x" name="endorsement_x" type="checkbox" value="1"
                                            {{ in_array('X', $currentEndorsements) ? 'checked' : '' }}
                                            class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                        <label for="endorsement_x" class="form-check-label ml-2">
                                            X - Hazmat & Tank
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="endorsement_t" name="endorsement_t" type="checkbox" value="1"
                                            {{ in_array('T', $currentEndorsements) ? 'checked' : '' }}
                                            class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                        <label for="endorsement_t" class="form-check-label ml-2">
                                            T - Double/Triple Trailers
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="endorsement_p" name="endorsement_p" type="checkbox" value="1"
                                            {{ in_array('P', $currentEndorsements) ? 'checked' : '' }}
                                            class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                        <label for="endorsement_p" class="form-check-label ml-2">
                                            P - Passenger
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="endorsement_s" name="endorsement_s" type="checkbox" value="1"
                                            {{ in_array('S', $currentEndorsements) ? 'checked' : '' }}
                                            class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                        <label for="endorsement_s" class="form-check-label ml-2">
                                            S - School Bus
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: License Images -->
                    <div class="mb-8">
                        <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">License Images</h4>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- License Front Image -->
                            <div>
                                <x-base.form-label for="license_front_image" class="form-label">License Front
                                    Image</x-base.form-label>

                                @php
                                    $frontImage = $license->getFirstMedia('license_front');
                                @endphp

                                @if ($frontImage)
                                    <div class="mb-3">
                                        <p class="text-sm text-gray-600 mb-2">Current Image:</p>
                                        <div class="relative inline-block">
                                            <img src="{{ $frontImage->getUrl() }}" alt="License Front"
                                                class="img-thumbnail border rounded"
                                                style="max-width: 200px; max-height: 150px;">
                                            <div class="mt-2">
                                                <a href="{{ route('admin.licenses.doc.preview', $frontImage->id) }}"
                                                    target="_blank" class="text-primary text-sm hover:underline">
                                                    <x-base.lucide class="w-4 h-4 inline" icon="eye" /> View Full Size
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <x-base.form-input type="file" id="license_front_image" name="license_front_image"
                                    class="form-control @error('license_front_image') is-invalid @enderror"
                                    accept="image/*" />
                                <small
                                    class="form-text text-muted">{{ $frontImage ? 'Upload a new image to replace the current one' : 'Upload the front side of the driver\'s license' }}
                                    (JPG, PNG, GIF - Max 2MB)</small>
                                @error('license_front_image')
                                    <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                                <!-- Preview -->
                                <div id="front_image_preview" class="mt-2" style="display: none;">
                                    <p class="text-sm text-gray-600 mb-1">New Image Preview:</p>
                                    <img id="front_preview_img" src="" alt="Front Preview" class="img-thumbnail"
                                        style="max-width: 200px; max-height: 150px;">
                                </div>
                            </div>

                            <!-- License Back Image -->
                            <div>
                                <x-base.form-label for="license_back_image" class="form-label">License Back
                                    Image</x-base.form-label>

                                @php
                                    $backImage = $license->getFirstMedia('license_back');
                                @endphp

                                @if ($backImage)
                                    <div class="mb-3">
                                        <p class="text-sm text-gray-600 mb-2">Current Image:</p>
                                        <div class="relative inline-block">
                                            <img src="{{ $backImage->getUrl() }}" alt="License Back"
                                                class="img-thumbnail border rounded"
                                                style="max-width: 200px; max-height: 150px;">
                                            <div class="mt-2">
                                                <a href="{{ route('admin.licenses.doc.preview', $backImage->id) }}"
                                                    target="_blank" class="text-primary text-sm hover:underline">
                                                    <x-base.lucide class="w-4 h-4 inline" icon="eye" /> View Full Size
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <x-base.form-input type="file" id="license_back_image" name="license_back_image"
                                    class="form-control @error('license_back_image') is-invalid @enderror"
                                    accept="image/*" />
                                <small
                                    class="form-text text-muted">{{ $backImage ? 'Upload a new image to replace the current one' : 'Upload the back side of the driver\'s license' }}
                                    (JPG, PNG, GIF - Max 2MB)</small>
                                @error('license_back_image')
                                    <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                                <!-- Preview -->
                                <div id="back_image_preview" class="mt-2" style="display: none;">
                                    <p class="text-sm text-gray-600 mb-1">New Image Preview:</p>
                                    <img id="back_preview_img" src="" alt="Back Preview" class="img-thumbnail"
                                        style="max-width: 200px; max-height: 150px;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 5: Additional Documents -->
                    <div class="mb-8">
                        <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">Additional Documents</h4>

                        @php
                            $existingDocs = $license->getMedia('licenses');
                        @endphp

                        @if ($existingDocs->count() > 0)
                            <div class="mb-6">
                                <x-base.form-label class="form-label">Existing Documents</x-base.form-label>
                                <div class="space-y-2">
                                    @foreach ($existingDocs as $doc)
                                        <div
                                            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                            <div class="flex items-center gap-3">
                                                <div>
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900">{{ $doc->name }}</p>
                                                    <p class="text-xs text-gray-500">
                                                        {{ number_format($doc->size / 1024, 2) }} KB</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('admin.licenses.doc.preview', $doc->id) }}"
                                                    target="_blank" class="text-primary hover:text-primary-dark">
                                                    <x-base.lucide class="w-5 h-5" icon="eye" />
                                                </a>
                                                <form action="{{ route('admin.licenses.doc.delete', $doc->id) }}"
                                                    method="POST" class="inline"
                                                    onsubmit="return confirm('Are you sure you want to delete this document?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-500 hover:text-red-700">
                                                        <x-base.lucide class="w-5 h-5" icon="trash-2" />
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <x-base.form-label class="form-label">Upload Additional Documents</x-base.form-label>
                                <div id="file-upload-area"
                                    class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-primary transition-colors">
                                    <x-base.lucide class="w-12 h-12 mx-auto text-gray-400 mb-3" icon="upload-cloud" />
                                    <p class="text-sm text-gray-600 mb-2">Drag and drop files here or click to browse</p>
                                    <p class="text-xs text-gray-500">Supported formats: PDF, JPG, PNG, DOC, DOCX (Max 10MB
                                        per file)</p>
                                    <input type="file" id="additional_documents" name="additional_documents[]"
                                        multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="hidden" />
                                    <x-base.button type="button" variant="outline-primary" class="mt-3"
                                        onclick="document.getElementById('additional_documents').click()">
                                        <x-base.lucide class="w-4 h-4 mr-2" icon="file-plus" />
                                        Select Files
                                    </x-base.button>
                                </div>
                                <div id="file-list" class="mt-4 space-y-2"></div>
                                <!-- Hidden input to store file data for submission -->
                                <input type="hidden" id="uploaded_files_data" name="uploaded_files" value="">
                            </div>
                        </div>
                    </div>



                    <!-- Botones del formulario -->
                    <div class="flex justify-end mt-8 space-x-4">
                        <x-base.button type="button" class="mr-3" variant="outline-secondary" as="a"
                            href="{{ route('admin.licenses.index') }}">
                            Cancel
                        </x-base.button>
                        <x-base.button type="submit" variant="primary">
                            Save License
                        </x-base.button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Form initialization
        document.addEventListener('DOMContentLoaded', function() {
            // Handle CDL checkbox and show/hide endorsements
            const cdlCheckbox = document.getElementById('is_cdl');
            const endorsementsSection = document.getElementById('cdl_endorsements');

            function toggleEndorsements() {
                if (cdlCheckbox.checked) {
                    endorsementsSection.classList.remove('hidden');
                } else {
                    endorsementsSection.classList.add('hidden');
                    // Uncheck all endorsements when CDL is unchecked
                    const endorsementCheckboxes = endorsementsSection.querySelectorAll('input[type="checkbox"]');
                    endorsementCheckboxes.forEach(checkbox => {
                        checkbox.checked = false;
                    });
                }
            }

            // Initialize endorsements state
            toggleEndorsements();

            // Listen for changes in CDL checkbox
            cdlCheckbox.addEventListener('change', toggleEndorsements);

            // Handle image preview
            function setupImagePreview(inputId, previewId, imgId) {
                const input = document.getElementById(inputId);
                const preview = document.getElementById(previewId);
                const img = document.getElementById(imgId);

                input.addEventListener('change', function(event) {
                    const file = event.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            img.src = e.target.result;
                            preview.style.display = 'block';
                        };
                        reader.readAsDataURL(file);
                    } else {
                        preview.style.display = 'none';
                    }
                });
            }

            // Setup previews for both images
            setupImagePreview('license_front_image', 'front_image_preview', 'front_preview_img');
            setupImagePreview('license_back_image', 'back_image_preview', 'back_preview_img');

            // Handle additional documents upload
            const additionalDocsInput = document.getElementById('additional_documents');
            const fileList = document.getElementById('file-list');
            const uploadArea = document.getElementById('file-upload-area');
            let selectedFiles = [];

            // Click to upload
            uploadArea.addEventListener('click', function(e) {
                if (e.target.tagName !== 'BUTTON' && e.target.tagName !== 'INPUT') {
                    additionalDocsInput.click();
                }
            });

            // Drag and drop
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                uploadArea.classList.add('border-primary', 'bg-primary/5');
            });

            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('border-primary', 'bg-primary/5');
            });

            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('border-primary', 'bg-primary/5');

                const files = Array.from(e.dataTransfer.files);
                handleFiles(files);
            });

            additionalDocsInput.addEventListener('change', function(e) {
                const files = Array.from(e.target.files);
                handleFiles(files);
            });

            function handleFiles(files) {
                files.forEach(file => {
                    // Validate file size (10MB)
                    if (file.size > 10 * 1024 * 1024) {
                        alert(`File ${file.name} is too large. Maximum size is 10MB.`);
                        return;
                    }

                    // Validate file type
                    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    ];
                    if (!allowedTypes.includes(file.type)) {
                        alert(`File ${file.name} has an unsupported format.`);
                        return;
                    }

                    selectedFiles.push(file);
                    displayFile(file);
                });
            }

            function displayFile(file) {
                const fileItem = document.createElement('div');
                fileItem.className =
                    'flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200';

                const fileInfo = document.createElement('div');
                fileInfo.className = 'flex items-center gap-3';

                const icon = document.createElement('div');
                icon.innerHTML =
                    '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>';

                const fileDetails = document.createElement('div');
                fileDetails.innerHTML = `
                <p class="text-sm font-medium text-gray-900">${file.name}</p>
                <p class="text-xs text-gray-500">${formatFileSize(file.size)}</p>
            `;

                fileInfo.appendChild(icon);
                fileInfo.appendChild(fileDetails);

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'text-red-500 hover:text-red-700';
                removeBtn.innerHTML =
                    '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>';
                removeBtn.onclick = function() {
                    selectedFiles = selectedFiles.filter(f => f !== file);
                    fileItem.remove();
                };

                fileItem.appendChild(fileInfo);
                fileItem.appendChild(removeBtn);
                fileList.appendChild(fileItem);
            }

            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
            }

            // Form validation
            document.getElementById('licenseForm').addEventListener('submit', function(event) {
                const expirationDateEl = document.querySelector('input[name="expiration_date"]');

                // Verify expiration date is not in the past
                if (expirationDateEl.value) {
                    const expirationDate = new Date(expirationDateEl.value);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);

                    if (expirationDate < today) {
                        event.preventDefault();
                        alert('Expiration date cannot be in the past');
                        return;
                    }
                }

                // Handle file uploads - create FormData to include files
                if (selectedFiles.length > 0) {
                    // Note: Files will be handled by the browser's FormData automatically
                    // when the form is submitted with enctype="multipart/form-data"
                    // We just need to add them to the form
                    const dataTransfer = new DataTransfer();
                    selectedFiles.forEach(file => {
                        dataTransfer.items.add(file);
                    });
                    additionalDocsInput.files = dataTransfer.files;
                }
            });

            // Handle carrier change to filter drivers
            const carrierSelect = document.getElementById('carrier_id');
            const driverSelect = document.getElementById('user_driver_detail_id');

            if (carrierSelect) {
                carrierSelect.addEventListener('change', function() {
                    const carrierId = this.value;
                    const currentDriverId = "{{ $license->user_driver_detail_id }}";

                    // Clear driver select
                    driverSelect.innerHTML = '<option value="">Select Driver</option>';

                    if (carrierId) {
                        // Fetch active drivers for this carrier
                        fetch(`/api/active-drivers-by-carrier/${carrierId}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data && data.length > 0) {
                                    data.forEach(function(driver) {
                                        const option = document.createElement('option');
                                        option.value = driver.id;
                                        option.textContent = driver.full_name;

                                        if (driver.id == currentDriverId) {
                                            option.selected = true;
                                        }

                                        driverSelect.appendChild(option);
                                    });
                                } else {
                                    const option = document.createElement('option');
                                    option.value = '';
                                    option.disabled = true;
                                    option.textContent = 'No active drivers found for this carrier';
                                    driverSelect.appendChild(option);
                                }

                                driverSelect.dispatchEvent(new Event('change'));
                            })
                            .catch(error => {
                                console.error('Error loading drivers:', error);
                                const option = document.createElement('option');
                                option.value = '';
                                option.disabled = true;
                                option.textContent = 'Error loading drivers';
                                driverSelect.appendChild(option);
                                driverSelect.dispatchEvent(new Event('change'));
                            });
                    }
                });
            }
        });
    </script>
@endpush
