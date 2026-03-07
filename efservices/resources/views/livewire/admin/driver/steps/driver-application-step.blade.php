<div class="box box--stacked flex flex-col border-none">
    <div class="flex items-center px-5 py-5 border-b border-slate-200/60 dark:border-darkmode-400">
        <h2 class="font-medium text-base mr-auto">Application Details</h2>
    </div>
    <div class="p-1 md:p-5">

        <!-- Global Success/Error Messages -->
        @if (session()->has('message'))
            <div id="success-message"
                class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-r-lg shadow-sm animate-fade-in">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    <div class="flex-1">
                        <p class="font-semibold text-green-800">Success!</p>
                        <p class="text-sm text-green-700 mt-1">{{ session('message') }}</p>
                    </div>
                    <button type="button" onclick="this.parentElement.parentElement.remove()"
                        class="text-green-500 hover:text-green-700 ml-4">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        @if (session()->has('error'))
            <div id="error-message"
                class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg shadow-sm animate-fade-in">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-red-500 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                    <div class="flex-1">
                        <p class="font-semibold text-red-800">Error</p>
                        <p class="text-sm text-red-700 mt-1">{{ session('error') }}</p>
                    </div>
                    <button type="button" onclick="this.parentElement.parentElement.remove()"
                        class="text-red-500 hover:text-red-700 ml-4">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        <!-- Validation Error Summary (Requirement 7.3) -->
        <x-validation-error-summary :errors="$errors" />

        <!-- Optional Fields Notice (Requirement 3.2) -->
        @if (isset($hasIncompleteOptionalFields) && $hasIncompleteOptionalFields)
            <x-optional-fields-notice :fields="$incompleteOptionalFields ?? []" />
        @endif

        <!-- Position Applied For -->
        <div class="mt-5">
            <div class="mb-2 inline-block sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                <div class="text-left">
                    <div class="flex items-center">
                        <div class="font-medium">Position Applied For</div>
                        <div
                            class="ml-2.5 rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs text-slate-500">
                            Required</div>
                    </div>
                    <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">
                        Select the position you are applying for.
                    </div>
                </div>
            </div>
            <div class="mt-3 w-full flex-1 xl:mt-0">
                <!-- Position Select - SIMPLIFIED TO DRIVER AND OTHER -->
                <div class="mb-6">
                    <select wire:model="applying_position"
                        class="form-select w-full rounded-md border border-slate-300/60 bg-white px-3 py-2 shadow-sm">
                        <option value="">Select Position</option>
                        <option value="driver">Driver</option>
                        <option value="other">Other</option>
                    </select>
                    @error('applying_position')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror

                    <!-- Other Position Input -->
                    <div x-show="$wire.applying_position === 'other'" x-transition class="mt-2">
                        <label class="block mb-1">Specify Position *</label>
                        <x-base.form-input type="text" wire:model="applying_position_other"
                            class="w-full px-3 py-2 border rounded" placeholder="Enter position" />
                        @error('applying_position_other')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Vehicle Type Selection (Single Choice) -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium mb-4 text-primary border-b border-gray-200 pb-2">Vehicle Assignment
                        Type</h3>
                    <div class="text-sm text-gray-600 mb-4">Select ONE vehicle type you want to be assigned to:</div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <label
                            class="flex items-center p-4 bg-white rounded-lg border-2 border-gray-200 hover:border-blue-300 cursor-pointer transition-colors duration-200">
                            <input type="radio" wire:model.live="selectedDriverType" value="owner_operator"
                                class="mr-3 h-4 w-4 text-blue-600">
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">Owner Operator Vehicles</div>
                                <div class="text-sm text-gray-500">Manage owner operator assignments</div>
                            </div>
                        </label>

                        <label
                            class="flex items-center p-4 bg-white rounded-lg border-2 border-gray-200 hover:border-blue-300 cursor-pointer transition-colors duration-200">
                            <input type="radio" wire:model.live="selectedDriverType" value="third_party"
                                class="mr-3 h-4 w-4 text-blue-600">
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">Third Party Vehicles</div>
                                <div class="text-sm text-gray-500">Manage third party assignments</div>
                            </div>
                        </label>

                        <label
                            class="flex items-center p-4 bg-white rounded-lg border-2 border-gray-200 hover:border-blue-300 cursor-pointer transition-colors duration-200">
                            <input type="radio" wire:model.live="selectedDriverType" value="company_driver"
                                class="mr-3 h-4 w-4 text-blue-600">
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">Company Vehicles</div>
                                <div class="text-sm text-gray-500">Manage company vehicle assignments</div>
                            </div>
                        </label>
                    </div>
                    @error('selectedDriverType')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Owner Operator Information -->
                <div x-show="$wire.selectedDriverType === 'owner_operator'" x-transition
                    class="mt-4 p-4 border rounded bg-gray-50">
                    <h3 class="text-lg font-medium mb-4 text-primary border-b border-gray-200 pb-2">Owner Operator
                        Information</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block mb-1">Owner Name *</label>
                            <x-base.form-input type="text" wire:model="owner_name"
                                class="w-full px-3 py-2 border rounded" />
                            @error('owner_name')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-1">Phone Number *</label>
                            <x-base.form-input type="tel" wire:model="owner_phone"
                                class="w-full px-3 py-2 border rounded" />
                            @error('owner_phone')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-1">Email *</label>
                        <x-base.form-input type="email" wire:model="owner_email"
                            class="w-full px-3 py-2 border rounded" />
                        @error('owner_email')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <h4 class="font-medium text-lg text-primary mb-3 mt-5 border-b border-gray-200 pb-2">Vehicle
                        Information (will be assigned to carrier)</h4>

                    @include('livewire.driver.steps.partials.vehicle-mode-indicator')

                    @if (count($existingVehicles) > 0)
                        <div class="mb-5 p-4 bg-blue-50 rounded-lg border border-blue-100 shadow-sm">
                            <h5 class="font-medium mb-2">Existing Vehicles</h5>
                            <div class="overflow-x-auto">
                                <table class="w-full bg-white border border-gray-200 rounded-lg overflow-hidden">
                                    <thead class="bg-gray-50 border-b-2 border-gray-200">
                                        <tr>
                                            <th
                                                class="py-2.5 px-4 border-b text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                Make</th>
                                            <th
                                                class="py-2.5 px-4 border-b text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                Model</th>
                                            <th
                                                class="py-2.5 px-4 border-b text-left text-xs font-semibold text-gray-700 uppercase tracking-wider hidden md:table-cell">
                                                Year</th>
                                            <th
                                                class="py-2.5 px-4 border-b text-left text-xs font-semibold text-gray-700 uppercase tracking-wider hidden lg:table-cell">
                                                VIN</th>
                                            <th
                                                class="py-2.5 px-4 border-b text-left text-xs font-semibold text-gray-700 uppercase tracking-wider hidden lg:table-cell">
                                                Type</th>
                                            <th
                                                class="py-2.5 px-4 border-b text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach ($existingVehicles as $vehicle)
                                            <tr
                                                class="hover:bg-blue-50 transition-colors duration-150 {{ $selectedVehicleId == $vehicle->id ? 'bg-blue-100' : '' }}">
                                                <td class="py-2 px-3 text-sm text-gray-900 font-medium">
                                                    {{ $vehicle->make }}</td>
                                                <td class="py-2 px-3 text-sm text-gray-900">{{ $vehicle->model }}</td>
                                                <td class="py-2 px-3 text-sm text-gray-600 hidden md:table-cell">
                                                    {{ $vehicle->year }}</td>
                                                <td class="py-2 px-3 text-sm text-gray-600 hidden lg:table-cell">
                                                    {{ $vehicle->vin }}</td>
                                                <td class="py-2 px-3 text-sm text-gray-600 hidden lg:table-cell">
                                                    {{ ucfirst($vehicle->type) }}</td>
                                                <td class="py-2 px-3">
                                                    <div class="flex space-x-2">
                                                        <button type="button"
                                                            wire:click="selectVehicle({{ $vehicle->id }})"
                                                            class="px-2.5 py-1.5 bg-primary text-white rounded-md text-sm hover:bg-primary transition duration-150 ease-in-out flex items-center">
                                                            Select
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <button type="button" wire:click="clearVehicleForm"
                                    class="px-4 py-2 bg-success text-white rounded-md hover:bg-success transition duration-150 ease-in-out flex items-center shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Register New Vehicle
                                </button>
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block mb-1">Make *</label>
                            <x-base.form-input type="text" wire:model="vehicle_make"
                                class="w-full px-3 py-2 border rounded" />
                            @error('vehicle_make')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-1">Model *</label>
                            <x-base.form-input type="text" wire:model="vehicle_model"
                                class="w-full px-3 py-2 border rounded" />
                            @error('vehicle_model')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block mb-1">Year *</label>
                            <x-base.form-input type="number" wire:model="vehicle_year"
                                class="w-full px-3 py-2 border rounded" />
                            @error('vehicle_year')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-1">VIN *</label>
                            <x-base.form-input type="text" wire:model="vehicle_vin"
                                class="w-full px-3 py-2 border rounded" />
                            @error('vehicle_vin')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block mb-1">Company Unit Number</label>
                            <x-base.form-input type="text" wire:model="vehicle_company_unit_number"
                                class="w-full px-3 py-2 border rounded" />
                            @error('vehicle_company_unit_number')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-1">Type *</label>
                            <select wire:model="vehicle_type"
                                class="form-select w-full rounded-md border border-slate-300/60 bg-white px-3 py-2 shadow-sm">
                                <option value="">Select Type</option>
                                <option value="truck">Truck</option>
                                <option value="trailer">Trailer</option>
                                <option value="van">Van</option>
                                <option value="pickup">Pickup</option>
                                <option value="other">Other</option>
                            </select>
                            @error('vehicle_type')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block mb-1">GVWR</label>
                            <x-base.form-input type="text" wire:model="vehicle_gvwr"
                                class="w-full px-3 py-2 border rounded" />
                            @error('vehicle_gvwr')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-1">Tire Size</label>
                            <x-base.form-input type="text" wire:model="vehicle_tire_size"
                                class="w-full px-3 py-2 border rounded" />
                            @error('vehicle_tire_size')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block mb-1">Fuel Type *</label>
                            <select wire:model="vehicle_fuel_type"
                                class="form-select w-full rounded-md border border-slate-300/60 bg-white px-3 py-2 shadow-sm">
                                <option value="">Select Fuel Type</option>
                                <option value="diesel">Diesel</option>
                                <option value="gasoline">Gasoline</option>
                                <option value="electric">Electric</option>
                                <option value="hybrid">Hybrid</option>
                                <option value="other">Other</option>
                            </select>
                            @error('vehicle_fuel_type')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-1">IRP Apportioned Plate</label>
                            <div class="flex items-center mt-2">
                                <x-base.form-check.input class="mr-2.5 border" type="checkbox"
                                    name="vehicle_irp_apportioned_plate" wire:model="vehicle_irp_apportioned_plate"
                                    id="vehicle_irp_apportioned_plate_third_party" />
                                <label for="vehicle_irp_apportioned_plate_third_party">IRP Apportioned Plate</label>
                            </div>
                            @error('vehicle_irp_apportioned_plate')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block mb-1">Registration State *</label>
                            <select wire:model="vehicle_registration_state"
                                class="form-select w-full rounded-md border border-slate-300/60 bg-white px-3 py-2 shadow-sm">
                                <option value="">Select State</option>
                                @foreach ($usStates as $code => $name)
                                    <option value="{{ $code }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('vehicle_registration_state')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-1">Registration Number *</label>
                            <x-base.form-input type="text" wire:model="vehicle_registration_number"
                                class="w-full px-3 py-2 border rounded" />
                            @error('vehicle_registration_number')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block mb-1">Registration Expiration Date *</label>
                            <input type="text" name="vehicle_registration_expiration_date"
                                wire:model="vehicle_registration_expiration_date"
                                class="driver-datepicker w-full rounded-md border border-slate-300/60 px-3 py-2 shadow-sm" placeholder="MM/DD/YYYY"
                                value="{{ $vehicle_registration_expiration_date }}" />
                            @error('vehicle_registration_expiration_date')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-1">Permanent Tag</label>
                            <div class="flex items-center mt-2">
                                <x-base.form-check.input class="mr-2.5 border" type="checkbox"
                                    name="vehicle_permanent_tag_third_party" wire:model="vehicle_permanent_tag"
                                    id="vehicle_permanent_tag_third_party" />
                                <label for="vehicle_permanent_tag_third_party">Permanent Tag</label>
                            </div>
                            @error('vehicle_permanent_tag')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-1">Location</label>
                        <x-base.form-input type="text" wire:model="vehicle_location"
                            class="w-full px-3 py-2 border rounded" />
                        @error('vehicle_location')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block mb-1">Notes</label>
                        <x-base.form-textarea wire:model="vehicle_notes" class="w-full px-3 py-2 border rounded"
                            rows="3" />
                        @error('vehicle_notes')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mt-5 p-4 bg-amber-50 rounded-lg border border-amber-100 shadow-sm">
                        <p class="mb-3">By checking this box, I agree to the terms and conditions of the contract. I
                            understand that I am responsible for maintaining my vehicle according to company standards
                            and complying with all applicable regulations.</p>
                        <p class="mb-3">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam auctor, nisl
                            eget ultricies tincidunt, nisl nisl aliquam nisl, eget ultricies nisl nisl eget nisl. Nullam
                            auctor, nisl eget ultricies tincidunt, nisl nisl aliquam nisl, eget ultricies nisl nisl eget
                            nisl.</p>
                        <p class="mb-3">Nullam auctor, nisl eget ultricies tincidunt, nisl nisl aliquam nisl, eget
                            ultricies nisl nisl eget nisl. Nullam auctor, nisl eget ultricies tincidunt, nisl nisl
                            aliquam nisl, eget ultricies nisl nisl eget nisl.</p>

                        <div class="flex items-center">
                            <x-base.form-check.input class="mr-2.5 border" type="checkbox" name="contract_agreed"
                                wire:model="contract_agreed" id="contract_agreed" />
                            <label for="contract_agreed" class="cursor-pointer">I Agree to the Terms and Conditions
                                *</label>
                        </div>
                        @error('contract_agreed')
                            <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Third Party Driver Information -->
                <div x-show="$wire.selectedDriverType === 'third_party'" x-transition
                    class="mt-4 p-4 border rounded bg-gray-50">
                    <h3 class="text-lg font-medium mb-4 text-primary border-b border-gray-200 pb-2">Third Party Company
                        Information</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block mb-1">Company Representative Name *</label>
                            <x-base.form-input type="text" wire:model="third_party_name"
                                class="w-full px-3 py-2 border rounded" />
                            @error('third_party_name')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-1">Phone Number *</label>
                            <x-base.form-input type="tel" wire:model="third_party_phone"
                                class="w-full px-3 py-2 border rounded" />
                            @error('third_party_phone')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-1">Email *</label>
                        <x-base.form-input type="email" wire:model="third_party_email"
                            class="w-full px-3 py-2 border rounded" />
                        @error('third_party_email')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block mb-1">DBA (Doing Business As)</label>
                            <x-base.form-input type="text" wire:model="third_party_dba"
                                class="w-full px-3 py-2 border rounded" />
                            @error('third_party_dba')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-1">Contact Person</label>
                            <x-base.form-input type="text" wire:model="third_party_contact"
                                class="w-full px-3 py-2 border rounded" />
                            @error('third_party_contact')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block mb-1">Address</label>
                            <x-base.form-input type="text" wire:model="third_party_address"
                                class="w-full px-3 py-2 border rounded" />
                            @error('third_party_address')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-1">FEIN (Federal Employer Identification Number)</label>
                            <x-base.form-input type="text" wire:model="third_party_fein"
                                class="w-full px-3 py-2 border rounded" />
                            @error('third_party_fein')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Email Status Indicator -->
                    <div
                        class="mb-6 p-4 rounded-lg border-2 {{ $email_sent ? 'bg-green-50 border-success' : 'bg-yellow-50 border-yellow-300' }}">
                        <div class="flex flex-col items-center gap-3 sm:flex-row">
                            @if ($email_sent)
                                <svg class="w-6 h-6 text-green-600 mr-3 flex-shrink-0" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <p class="font-semibold text-success text-center sm:text-start">Email Verification Sent</p>
                                    <p class="font-semibold text-success text-center sm:text-start">The document signing request has been sent to the
                                        third party representative. You can proceed to the next step.</p>
                                </div>
                            @else
                                <svg class="w-6 h-6 text-yellow-600 mr-3 flex-shrink-0" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <p class="font-semibold text-yellow-800">Email Verification Pending</p>
                                    <p class="text-sm text-yellow-700">You must send the document signing request
                                        before proceeding to the next step. Complete all required fields below and click
                                        "Send Document Signing Request".</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    @error('email_verification')
                        <div class="mb-4 p-3 bg-red-50 border-2 border-red-300 rounded-lg">
                            <p class="text-red-800 font-medium">{{ $message }}</p>
                        </div>
                    @enderror

                    <h4 class="font-medium text-lg text-primary mb-3 mt-5 border-b border-gray-200 pb-2">Vehicle
                        Information (will be assigned to carrier)</h4>

                    @include('livewire.driver.steps.partials.vehicle-mode-indicator')

                    @if (count($existingVehicles) > 0)
                        <div class="mb-5 p-4 bg-blue-50 rounded-lg border border-blue-100 shadow-sm">
                            <h5 class="font-medium mb-2">Existing Vehicles</h5>

                            <!-- Desktop Table -->
                            <div class="hidden lg:block overflow-x-auto">
                                <table class="w-full bg-white border border-gray-200 rounded-lg overflow-hidden">
                                    <thead class="bg-gray-50 border-b-2 border-gray-200">
                                        <tr>
                                            <th
                                                class="py-2.5 px-4 border-b text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                Make</th>
                                            <th
                                                class="py-2.5 px-4 border-b text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                Model</th>
                                            <th
                                                class="py-2.5 px-4 border-b text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                Year</th>
                                            <th
                                                class="py-2.5 px-4 border-b text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                VIN</th>
                                            <th
                                                class="py-2.5 px-4 border-b text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                Type</th>
                                            <th
                                                class="py-2.5 px-4 border-b text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach ($existingVehicles as $vehicle)
                                            <tr
                                                class="hover:bg-blue-50 transition-colors duration-150 {{ $selectedVehicleId == $vehicle->id ? 'bg-blue-100' : '' }}">
                                                <td class="py-2 px-3 text-sm text-gray-900 font-medium">
                                                    {{ $vehicle->make }}</td>
                                                <td class="py-2 px-3 text-sm text-gray-900">{{ $vehicle->model }}</td>
                                                <td class="py-2 px-3 text-sm text-gray-600">{{ $vehicle->year }}</td>
                                                <td class="py-2 px-3 text-sm text-gray-600">{{ $vehicle->vin }}</td>
                                                <td class="py-2 px-3 text-sm text-gray-600">
                                                    {{ ucfirst($vehicle->type) }}</td>
                                                <td class="py-2 px-3">
                                                    <button type="button"
                                                        wire:click="selectVehicle({{ $vehicle->id }})"
                                                        class="px-2.5 py-1.5 bg-primary text-white rounded-md text-sm hover:bg-blue-900 transition duration-150 ease-in-out">
                                                        Select
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Mobile Cards -->
                            <div class="lg:hidden space-y-3">
                                @foreach ($existingVehicles as $vehicle)
                                    <div
                                        class="bg-white border border-gray-200 rounded-lg p-4 {{ $selectedVehicleId == $vehicle->id ? 'ring-2 ring-blue-500 bg-blue-50' : '' }}">
                                        <div class="space-y-2">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <p class="font-semibold text-gray-900">{{ $vehicle->make }}
                                                        {{ $vehicle->model }}</p>
                                                    <p class="text-sm text-gray-600">{{ $vehicle->year }}</p>
                                                </div>
                                                <span
                                                    class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded">{{ ucfirst($vehicle->type) }}</span>
                                            </div>
                                            <div class="text-sm text-gray-600">
                                                <p><span class="font-medium">VIN:</span> {{ $vehicle->vin }}</p>
                                            </div>
                                            <button type="button" wire:click="selectVehicle({{ $vehicle->id }})"
                                                class="w-full px-3 py-2 bg-primary text-white rounded-md text-sm hover:bg-blue-900 transition duration-150 ease-in-out">
                                                Select Vehicle
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-3">
                                <button type="button" wire:click="clearVehicleForm"
                                    class="w-full lg:w-auto px-4 py-2 bg-success text-white rounded-md hover:bg-success transition duration-150 ease-in-out flex items-center justify-center shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Register New Vehicle
                                </button>
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block mb-1">Make *</label>
                            <x-base.form-input type="text" wire:model="vehicle_make"
                                class="w-full px-3 py-2 border rounded" />
                            @error('vehicle_make')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-1">Model *</label>
                            <x-base.form-input type="text" wire:model="vehicle_model"
                                class="w-full px-3 py-2 border rounded" />
                            @error('vehicle_model')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block mb-1">Year *</label>
                            <x-base.form-input type="number" wire:model="vehicle_year"
                                class="w-full px-3 py-2 border rounded" />
                            @error('vehicle_year')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-1">VIN *</label>
                            <x-base.form-input type="text" wire:model="vehicle_vin"
                                class="w-full px-3 py-2 border rounded" />
                            @error('vehicle_vin')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block mb-1">Company Unit Number</label>
                            <x-base.form-input type="text" wire:model="vehicle_company_unit_number"
                                class="w-full px-3 py-2 border rounded" />
                            @error('vehicle_company_unit_number')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-1">Type *</label>
                            <select wire:model="vehicle_type"
                                class="form-select w-full rounded-md border border-slate-300/60 bg-white px-3 py-2 shadow-sm">
                                <option value="">Select Type</option>
                                <option value="truck">Truck</option>
                                <option value="trailer">Trailer</option>
                                <option value="van">Van</option>
                                <option value="pickup">Pickup</option>
                                <option value="other">Other</option>
                            </select>
                            @error('vehicle_type')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block mb-1">GVWR</label>
                            <x-base.form-input type="text" wire:model="vehicle_gvwr"
                                class="w-full px-3 py-2 border rounded" />
                            @error('vehicle_gvwr')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-1">Tire Size</label>
                            <x-base.form-input type="text" wire:model="vehicle_tire_size"
                                class="w-full px-3 py-2 border rounded" />
                            @error('vehicle_tire_size')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block mb-1">Fuel Type *</label>
                            <select wire:model="vehicle_fuel_type"
                                class="form-select w-full rounded-md border border-slate-300/60 bg-white px-3 py-2 shadow-sm">
                                <option value="">Select Fuel Type</option>
                                <option value="diesel">Diesel</option>
                                <option value="gasoline">Gasoline</option>
                                <option value="electric">Electric</option>
                                <option value="hybrid">Hybrid</option>
                                <option value="other">Other</option>
                            </select>
                            @error('vehicle_fuel_type')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-1">IRP Apportioned Plate</label>
                            <div class="flex items-center mt-2">
                                <x-base.form-check.input class="mr-2.5 border" type="checkbox"
                                    name="vehicle_irp_apportioned_plate" wire:model="vehicle_irp_apportioned_plate"
                                    id="vehicle_irp_apportioned_plate_third_party" />
                                <label for="vehicle_irp_apportioned_plate_third_party">IRP Apportioned Plate</label>
                            </div>
                            @error('vehicle_irp_apportioned_plate')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block mb-1">Registration State *</label>
                            <select wire:model="vehicle_registration_state"
                                class="form-select w-full rounded-md border border-slate-300/60 bg-white px-3 py-2 shadow-sm">
                                <option value="">Select State</option>
                                @foreach ($usStates as $code => $name)
                                    <option value="{{ $code }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('vehicle_registration_state')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-1">Registration Number *</label>
                            <x-base.form-input type="text" wire:model="vehicle_registration_number"
                                class="w-full px-3 py-2 border rounded" />
                            @error('vehicle_registration_number')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block mb-1">Registration Expiration Date *</label>
                            <input type="text" name="vehicle_registration_expiration_date"
                                wire:model="vehicle_registration_expiration_date"
                                class="driver-datepicker form-control w-full rounded-md border border-slate-300/60 px-3 py-2 shadow-sm" placeholder="MM/DD/YYYY"
                                value="{{ $vehicle_registration_expiration_date }}" />
                            @error('vehicle_registration_expiration_date')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-1">Permanent Tag</label>
                            <div class="flex items-center mt-2">
                                <x-base.form-check.input class="mr-2.5 border" type="checkbox"
                                    name="vehicle_permanent_tag_third_party" wire:model="vehicle_permanent_tag"
                                    id="vehicle_permanent_tag_third_party" />
                                <label for="vehicle_permanent_tag_third_party">Permanent Tag</label>
                            </div>
                            @error('vehicle_permanent_tag')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-1">Location</label>
                        <x-base.form-input type="text" wire:model="vehicle_location"
                            class="w-full px-3 py-2 border rounded" />
                        @error('vehicle_location')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block mb-1">Notes</label>
                        <x-base.form-textarea wire:model="vehicle_notes" class="w-full px-3 py-2 border rounded"
                            rows="3" />
                        @error('vehicle_notes')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mt-4">
                        <!-- Validation Errors Display - Shows all errors simultaneously -->
                        @if ($errors->has('validation'))
                            <div id="validation-errors"
                                class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg shadow-sm animate-fade-in">
                                <div class="flex items-start">
                                    <svg class="w-6 h-6 text-red-500 mr-3 flex-shrink-0 mt-0.5" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <div class="flex-1">
                                        <p class="font-semibold text-red-800 mb-2">Please complete the following
                                            required fields:</p>
                                        <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
                                            @foreach ($errors->get('validation') as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($errors->has('email_verification'))
                            <div id="email-verification-error"
                                class="mb-4 p-4 bg-yellow-50 border-l-4 border-yellow-500 rounded-r-lg shadow-sm animate-fade-in">
                                <div class="flex items-start">
                                    <svg class="w-6 h-6 text-yellow-600 mr-3 flex-shrink-0 mt-0.5" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <div class="flex-1">
                                        <p class="font-semibold text-yellow-800">Action Required</p>
                                        <p class="text-sm text-yellow-700 mt-1">
                                            {{ $errors->first('email_verification') }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="button_action_email space-x-4">
                            <button type="button" wire:click="sendThirdPartyVerificationEmail"
                                class="px-4 py-4 sm:py-2 bg-primary text-white rounded hover:bg-blue-900 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm"
                                {{ $email_sent ? 'disabled' : '' }}>
                                @if ($email_sent)
                                    <span class="flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Email Sent Successfully
                                    </span>
                                @else
                                    Send Document Signing Request
                                @endif
                            </button>

                            @if ($email_sent)
                                <span class="button-success_application text-success font-medium flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Verification email delivered
                                </span>

                                <button type="button" wire:click="resendVerificationEmail"
                                    class="resend_button_application px-4 py-2 sm:py-4 bg-warning text-white rounded hover:bg-yellow-600 transition-colors shadow-sm">
                                    Resend Email
                                </button>
                            @else
                                <button type="button" wire:click="resendVerificationEmail"
                                    class="resend_button_application px-4 py-2 sm:py-4 ml-0 bg-gray-300 text-gray-500 rounded cursor-not-allowed"
                                    disabled>
                                    Resend Email
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Company Driver Fields -->
                <div x-show="$wire.selectedDriverType === 'company_driver'" x-transition
                    class="mt-4 p-4 border rounded bg-gray-50">
                    <h3 class="text-lg font-medium mb-4 text-primary border-b border-gray-200 pb-2">Company Driver
                        Information</h3>

                    <!-- Company Driver Notes -->
                    <div class="mb-6">
                        <label class="block mb-1 font-medium text-gray-700">Company Driver Information <span class="text-gray-400 text-sm font-normal">(Optional)</span></label>
                        <textarea wire:model="company_driver_notes" class="w-full px-3 py-2 border rounded" rows="6"
                            placeholder="Please provide any relevant information about your company driver application, including experience level, schedule preferences, preferred routes, additional certifications, or any other details that would be helpful for your application..."></textarea>
                        @error('company_driver_notes')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Location Preference -->
            <div class="mb-6 bg-gray-50 py-4 rounded-lg">
                <label class="block mb-2 font-medium text-gray-700">Location Preference <span
                        class="text-red-500">*</span></label>
                <select wire:model="applying_location"
                    class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                    <option value="">Select State</option>
                    @foreach ($usStates as $code => $name)
                        <option value="{{ $code }}">{{ $name }}</option>
                    @endforeach
                </select>

                @error('applying_location')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <!-- Eligibility Information -->
            <div class="p-5 border rounded-lg bg-blue-50 border-blue-100 shadow-sm mb-6">
                <h3 class="text-lg font-medium mb-4 text-primary border-b border-blue-100 pb-2">Eligibility
                    Information</h3>

                <div class="flex items-center mt-4 mb-4">
                    <x-base.form-check.input class="mr-2.5 border" type="checkbox" name="eligible_to_work"
                        wire:model="eligible_to_work" />
                    <span class="cursor-pointer ">
                        Eligible to work in the United States *
                    </span>
                    @error('eligible_to_work')
                        <span class="text-red-500 text-sm block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex items-center mt-4 mb-4">
                    <x-base.form-check.input class="mr-2.5 border" type="checkbox" name="can_speak_english"
                        wire:model="can_speak_english" />
                    <span class="cursor-pointer ">
                        Can speak and understand English
                    </span>
                    @error('can_speak_english')
                        <span class="text-red-500 text-sm block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="flex items-center">
                        <x-base.form-check.input class="mr-2.5 border" type="checkbox" name="has_twic_card"
                            wire:model="has_twic_card" />
                        <span>I have a TWIC Card</span>
                    </label>

                    <div x-show="$wire.has_twic_card" x-transition class="mt-2">
                        <label class="block mb-1 font-medium text-gray-700">TWIC Card Expiration Date *</label>
                        <input type="text" name="twic_expiration_date" wire:model="twic_expiration_date"
                            class="driver-datepicker w-full rounded-md border border-slate-300/60 px-3 py-2 shadow-sm" placeholder="MM/DD/YYYY"
                            value="{{ $twic_expiration_date }}" />
                        @error('twic_expiration_date')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Expected Pay Rate -->
            <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                <label class="block mb-2 font-medium text-gray-700">Expected Pay Rate</label>
                <input type="input" wire:model="expected_pay" class="w-full px-3 py-2 border rounded"
                    placeholder="e.g. $25/hour">
            </div>

            <!-- Referral Source -->
            <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                <label class="block mb-2 font-medium text-gray-700">Referral Source <span
                        class="text-red-500">*</span></label>
                <select wire:model="how_did_hear"
                    class="form-select w-full rounded-md border border-slate-300/60 bg-white px-3 py-2 shadow-sm">
                    <option value="">Select Source</option>
                    @foreach ($referralSources as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('how_did_hear')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror

                <div x-show="$wire.how_did_hear === 'employee_referral'" x-transition class="mt-2">
                    <label class="block mb-1">Employee Name *</label>
                    <x-base.form-input type="text" wire:model="referral_employee_name"
                        class="w-full px-3 py-2 border rounded" />
                    @error('referral_employee_name')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div x-show="$wire.how_did_hear === 'other'" x-transition class="mt-2">
                    <label class="block mb-1">Specify Other Source *</label>
                    <x-base.form-input type="text" wire:model="how_did_hear_other"
                        class="w-full px-3 py-2 border rounded" />
                    @error('how_did_hear_other')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="mt-8 px-5 py-5 border-t border-slate-200/60 dark:border-darkmode-400">
                <div class="flex flex-col sm:flex-row justify-between gap-4">
                    <div class="w-full sm:w-auto">
                        <x-base.button type="button" wire:click="previous" class="w-full sm:w-44"
                            variant="secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z"
                                    clip-rule="evenodd" />
                            </svg> Previous
                        </x-base.button>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
                        <x-base.button type="button" wire:click="saveAndExit" class="w-full sm:w-44 text-white"
                            variant="warning">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V8a2 2 0 00-2-2h-5L9 4H4z" />
                            </svg>
                            Save & Exit
                        </x-base.button>
                        <x-base.button type="button" wire:click="next" class="w-full sm:w-44" variant="primary">
                            Next
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </x-base.button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <style>
        /* Error field highlighting */
        .field-error {
            border-color: #ef4444 !important;
            border-width: 2px !important;
            background-color: #fef2f2 !important;
        }

        .field-error:focus {
            border-color: #dc2626 !important;
            ring-color: #fca5a5 !important;
        }

        /* Fade-in animation for messages */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }

        /* Pulse animation for error indicators */
        @keyframes pulse-error {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        .animate-pulse-error {
            animation: pulse-error 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Highlight fields with errors
            function highlightErrorFields() {
                // Remove existing error highlighting
                document.querySelectorAll('.field-error').forEach(el => {
                    el.classList.remove('field-error');
                });

                // Add error highlighting to fields with errors
                document.querySelectorAll('.text-red-500').forEach(errorSpan => {
                    const parentDiv = errorSpan.closest('div');
                    if (parentDiv) {
                        const input = parentDiv.querySelector('input, select, textarea');
                        if (input) {
                            input.classList.add('field-error');
                        }
                    }
                });
            }

            // Scroll to first error (only if errors exist)
            function scrollToFirstError() {
                // Check for validation errors first
                const validationErrors = document.getElementById('validation-errors');
                if (validationErrors) {
                    validationErrors.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    return true;
                }

                // Check for email verification error
                const emailError = document.getElementById('email-verification-error');
                if (emailError) {
                    emailError.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    return true;
                }

                // Otherwise scroll to first field with error
                const firstError = document.querySelector('.text-red-500');
                if (firstError) {
                    const parentDiv = firstError.closest('div');
                    if (parentDiv) {
                        parentDiv.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });

                        // Focus on the input field if available
                        const input = parentDiv.querySelector('input, select, textarea');
                        if (input) {
                            setTimeout(() => input.focus(), 500);
                        }
                    }
                    return true;
                }

                return false; // No errors found
            }

            // Run on page load
            highlightErrorFields();
            scrollToFirstError();

            // Listen for Livewire updates
            Livewire.hook('message.processed', (message, component) => {
                highlightErrorFields();
                // Only scroll if there are actual errors
                const hasErrors = document.querySelector('.text-red-500') ||
                    document.getElementById('validation-errors') ||
                    document.getElementById('email-verification-error');
                if (hasErrors) {
                    scrollToFirstError();
                }
            });

            // Auto-dismiss success messages after 5 seconds
            const successMessage = document.getElementById('success-message');
            if (successMessage) {
                setTimeout(() => {
                    successMessage.style.transition = 'opacity 0.5s ease-out';
                    successMessage.style.opacity = '0';
                    setTimeout(() => successMessage.remove(), 500);
                }, 5000);
            }
        });

        // Listen for custom notify events from Livewire
        window.addEventListener('notify', event => {
            const {
                type,
                message
            } = event.detail[0] || event.detail;

            // Create notification element
            const notification = document.createElement('div');
            notification.className = `mb-6 p-4 border-l-4 rounded-r-lg shadow-sm animate-fade-in ${
            type === 'success' ? 'bg-green-50 border-green-500' : 
            type === 'error' ? 'bg-red-50 border-red-500' : 
            'bg-yellow-50 border-yellow-500'
        }`;

            const iconColor = type === 'success' ? 'text-green-500' :
                type === 'error' ? 'text-red-500' :
                'text-yellow-600';

            const textColor = type === 'success' ? 'text-green-800' :
                type === 'error' ? 'text-red-800' :
                'text-yellow-800';

            const iconPath = type === 'success' ?
                'M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z' :
                type === 'error' ?
                'M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z' :
                'M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z';

            notification.innerHTML = `
            <div class="flex items-start">
                <svg class="w-6 h-6 ${iconColor} mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="${iconPath}" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <p class="font-semibold ${textColor}">${type === 'success' ? 'Success!' : type === 'error' ? 'Error' : 'Warning'}</p>
                    <p class="text-sm ${textColor.replace('800', '700')} mt-1">${message}</p>
                </div>
                <button type="button" onclick="this.parentElement.parentElement.remove()" class="${iconColor} hover:${iconColor.replace('500', '700')} ml-4">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        `;

            // Insert at the top of the form
            const formContainer = document.querySelector('.p-5');
            if (formContainer) {
                formContainer.insertBefore(notification, formContainer.firstChild);

                // Only scroll to notification if it's an error (not for success/info messages)
                if (type === 'error') {
                    notification.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }

                // Auto-dismiss after 8 seconds
                if (type === 'success') {
                    setTimeout(() => {
                        notification.style.transition = 'opacity 0.5s ease-out';
                        notification.style.opacity = '0';
                        setTimeout(() => notification.remove(), 500);
                    }, 8000);
                }
            }
        });
    </script>
@endpush
