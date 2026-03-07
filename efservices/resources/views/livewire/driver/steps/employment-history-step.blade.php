<div class="bg-white p-4 rounded-lg shadow">
    <h3 class="text-lg font-semibold mb-4 border-b pb-2">Employment History</h3>

    <!-- Validation Error Summary (Requirement 7.3) -->
    <x-validation-error-summary :errors="$errors" />

    <!-- Optional Fields Notice (Requirement 3.2) -->
    @if (isset($hasIncompleteOptionalFields) && $hasIncompleteOptionalFields)
        <x-optional-fields-notice :fields="$incompleteOptionalFields ?? []" />
    @endif

    <div class="bg-amber-50 p-4 mb-6 rounded-lg border border-amber-200">
        <p class="text-sm text-gray-700">
            <strong>All driver applicants must provide the following information on all work references during the
                preceding <span class="font-bold">three (3) years</span></strong> from the date application is submitted.
            Those drivers applying to operate a
            <strong>commercial motor vehicle</strong> as defined in §383.5 (requiring a CDL) shall provide
            <strong>ten (10) years</strong> of employment history.
        </p>
        <p class="text-sm text-gray-700 mt-2">
            <strong>NOTE: Please list companies in reverse order starting with the most recent and leave no gaps in
                employment history.</strong>
        </p>
    </div>

    <!-- Unemployment Periods -->
    <div class="mb-6 pb-4">
        <div x-data="{ hasUnemploymentPeriods: @entangle('has_unemployment_periods') }">
            <div class="flex items-center mb-4">
                <input type="checkbox" id="has_unemployment_periods" wire:model.live="has_unemployment_periods"
                    x-model="hasUnemploymentPeriods"
                    class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded mr-2">
                <label for="has_unemployment_periods" class="text-sm font-medium">
                    Have you been unemployed at any time within the last 10 years?
                </label>
            </div>

            <div x-show="hasUnemploymentPeriods" x-transition class="mt-2">
                <!-- Botón para agregar un nuevo período de desempleo -->
                <button type="button" wire:click="addUnemploymentPeriod"
                    class="mb-4 bg-primary text-white py-1.5 px-3 rounded-md text-sm hover:bg-blue-800 transition">
                    <i class="fas fa-plus mr-1"></i> Add Unemployment Period
                </button>

                <!-- Tabla de períodos de desempleo existentes -->
                @if (count($unemployment_periods) > 0)
                    <div class="overflow-x-auto">
                        <x-base.table>
                            <x-base.table.thead>
                                <x-base.table.tr>
                                    <x-base.table.th class="whitespace-nowrap">
                                        Start Date
                                    </x-base.table.th>
                                    <x-base.table.th class="whitespace-nowrap">
                                        End Date
                                    </x-base.table.th>
                                    <x-base.table.th class="whitespace-nowrap">
                                        Comment
                                    </x-base.table.th>
                                    <x-base.table.th class="whitespace-nowrap">
                                        Actions
                                    </x-base.table.th>
                                </x-base.table.tr>
                            </x-base.table.thead>
                            <x-base.table.tbody>
                                @foreach ($unemployment_periods as $index => $period)
                                    <x-base.table.tr>
                                        <x-base.table.td>{{ !empty($period['start_date']) ? \Carbon\Carbon::parse($period['start_date'])->format('m/d/Y') : '-' }}</x-base.table.td>
                                        <x-base.table.td>{{ !empty($period['end_date']) ? \Carbon\Carbon::parse($period['end_date'])->format('m/d/Y') : '-' }}</x-base.table.td>
                                        <x-base.table.td>{{ $period['comments'] ?? '-' }}</x-base.table.td>
                                        <x-base.table.td>
                                            <div class="flex space-x-2">
                                                <x-base.button type="button" variant="outline-primary" wire:click="editUnemploymentPeriod({{ $index }})"
                                                    class="text-blue-500 hover:text-blue-700">
                                                    <i class="fas fa-edit"></i> Edit
                                                </x-base.button>
                                                <x-base.button type="button" variant="primary" wire:click="confirmDeleteUnemploymentPeriod({{ $index }})"
                                                    class="text-red-500 hover:text-red-700">
                                                    <i class="fas fa-trash"></i> Delete
                                                </x-base.button>
                                            </div>
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                @endforeach
                            </x-base.table.tbody>
                        </x-base.table>
                    </div>
                @else
                    <p class="text-gray-500 italic text-sm">No unemployment periods added yet.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Other Driving-Related Employment -->
    <div class="mb-6 border-b pb-4">
        <h4 class="font-medium text-md mb-3">Other employment</h4>
        <p class="text-sm text-gray-700 mb-4">
            Include any other job positions (e.g., Cook, Warehouseman, Carpenter, Clerk) that are not part of your
            previous regular employment history. These positions also count toward the 10-year work history requirement.
        </p>

        <!-- Button to add new related employment -->
        <button type="button" wire:click="addRelatedEmployment"
            class="mb-4 bg-primary text-white py-1.5 px-3 rounded-md text-sm hover:bg-blue-800 transition">
            <i class="fas fa-plus mr-1"></i> Add another job position
        </button>

        <!-- Table of related employments -->
        @if (count($related_employments) > 0)
            <div class="overflow-x-auto">
                <x-base.table bordered hover>
                    <x-base.table.thead>
                        <x-base.table.tr>

                            <x-base.table.th class="whitespace-nowrap">
                                Start Date
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                End Date
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                Position
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                Comment
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                Actions
                            </x-base.table.th>
                        </x-base.table.tr>
                    </x-base.table.thead>
                    <x-base.table.tbody>
                        @foreach ($related_employments as $index => $employment)
                            <x-base.table.tr>
                                <x-base.table.td>{{ !empty($employment['start_date']) ? \Carbon\Carbon::parse($employment['start_date'])->format('m/d/Y') : '-' }}</x-base.table.td>
                                <x-base.table.td>{{ !empty($employment['end_date']) ? \Carbon\Carbon::parse($employment['end_date'])->format('m/d/Y') : '-' }}</x-base.table.td>
                                <x-base.table.td>{{ $employment['position'] ?? '-' }}</x-base.table.td>
                                <x-base.table.td>{{ $employment['comments'] ?? '-' }}</x-base.table.td>
                                <x-base.table.td>
                                    <div class="flex space-x-2">
                                        <button type="button" wire:click="editRelatedEmployment({{ $index }})"
                                            class="text-blue-500 hover:text-blue-700">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button type="button" wire:click="confirmDeleteRelatedEmployment({{ $index }})"
                                            class="text-red-500 hover:text-red-700">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </x-base.table.td>
                            </x-base.table.tr>
                        @endforeach
                    </x-base.table.tbody>
                </x-base.table>
            </div>
        @else
            <p class="text-gray-500 italic text-sm">No driving-related positions added yet.</p>
        @endif
    </div>
    <!-- Employment History Summary -->
    <div class="mb-6">
        <h4 class="font-medium text-lg mb-3">Employment History Summary</h4>
        
        <!-- Bulk Email Send Button -->
        @php
            $unsentEmailCount = collect($employment_companies)->filter(function($company) {
                return !empty($company['email']) && !($company['email_sent'] ?? false);
            })->count();
        @endphp
        
        @if ($unsentEmailCount > 0)
            <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="h-5 w-5 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span class="text-sm text-blue-800">
                        <strong>{{ $unsentEmailCount }}</strong> verification {{ $unsentEmailCount === 1 ? 'email' : 'emails' }} ready to send
                    </span>
                </div>
                <x-base.button 
                    type="button" 
                    wire:click="confirmBulkEmailSend" 
                    variant="primary"
                    class="text-sm"
                    wire:loading.attr="disabled"
                    wire:target="sendBulkVerificationEmails">
                    <span wire:loading.remove wire:target="sendBulkVerificationEmails">
                        <i class="fas fa-paper-plane mr-1"></i> Send All Verification Emails
                    </span>
                    <span wire:loading wire:target="sendBulkVerificationEmails">
                        <i class="fas fa-spinner fa-spin mr-1"></i> Sending...
                    </span>
                </x-base.button>
            </div>
        @endif

        <div class="overflow-x-auto">
            <x-base.table bordered hover>
                <x-base.table.thead>
                    <x-base.table.tr>
                        <x-base.table.th class="whitespace-nowrap">
                            Status
                        </x-base.table.th>
                        <x-base.table.th class="whitespace-nowrap">
                            Note
                        </x-base.table.th>
                        <x-base.table.th class="whitespace-nowrap">
                            Start Date
                        </x-base.table.th>
                        <x-base.table.th class="whitespace-nowrap">
                            End Date
                        </x-base.table.th>
                        <x-base.table.th class="whitespace-nowrap">
                            Email Status
                        </x-base.table.th>
                        <x-base.table.th class="whitespace-nowrap">
                            Actions
                        </x-base.table.th>
                    </x-base.table.tr>
                </x-base.table.thead>
                <x-base.table.tbody>
                    @forelse ($combinedEmploymentHistory as $item)
                        <x-base.table.tr>
                            <x-base.table.td>
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                  {{ $item['type'] == 'employed' ? 'bg-green-100 text-green-800' : ($item['type'] == 'related' ? 'bg-purple-100 text-purple-800' : 'bg-yellow-100 text-yellow-800') }}">
                                    {{ $item['status'] }}
                                </span>
                            </x-base.table.td>
                            <x-base.table.td>{{ $item['note'] }}</x-base.table.td>
                            <x-base.table.td>{{ \Carbon\Carbon::parse($item['from_date'])->format('m/d/Y') }}</x-base.table.td>
                            <x-base.table.td>{{ \Carbon\Carbon::parse($item['to_date'])->format('m/d/Y') }}</x-base.table.td>
                            <x-base.table.td>
                                @if ($item['type'] == 'employed')
                                    @php
                                        $company = $employment_companies[$item['original_index']] ?? null;
                                        $hasEmail = !empty($company['email']);
                                        $emailSent = $company['email_sent'] ?? false;
                                    @endphp
                                    
                                    @if (!$hasEmail)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-600">
                                            <i class="fas fa-envelope-open-text mr-1"></i> No Email
                                        </span>
                                    @elseif ($emailSent)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i> Sent
                                        </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-clock mr-1"></i> Not Sent
                                        </span>
                                    @endif
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-600">
                                        N/A
                                    </span>
                                @endif
                            </x-base.table.td>
                            <x-base.table.td>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button"
                                        variant="primary"
                                        wire:click="@if ($item['type'] == 'employed') editEmploymentCompany({{ $item['original_index'] }}) @elseif($item['type'] == 'related') editRelatedEmployment({{ $item['original_index'] }}) @else editUnemploymentPeriod({{ $item['original_index'] }}) @endif"
                                        class="text-primary hover:text-blue-700 text-sm p-2 border border-primary rounded-md flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg"  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-square-pen-icon lucide-square-pen w-4 h-4"><path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z"/></svg>
                                        <span>
                                            Edit
                                        </span>
                                    </button>
                                    <button type="button"
                                        variant="outline-danger"
                                        wire:click="@if ($item['type'] == 'employed') confirmDeleteEmploymentCompany({{ $item['original_index'] }}) @elseif($item['type'] == 'related') confirmDeleteRelatedEmployment({{ $item['original_index'] }}) @else confirmDeleteUnemploymentPeriod({{ $item['original_index'] }}) @endif"
                                        class="text-danger hover:text-red-700 text-sm p-2 border border-danger rounded-md flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg"  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash2-icon lucide-trash-2 w-4 h-4"><path d="M10 11v6"/><path d="M14 11v6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        <span>
                                            Delete
                                        </span>
                                    </button>
                                    
                                    @if ($item['type'] == 'employed')
                                        @php
                                            $company = $employment_companies[$item['original_index']] ?? null;
                                            $hasEmail = !empty($company['email']);
                                            $emailSent = $company['email_sent'] ?? false;
                                            $companyId = $company['id'] ?? null;
                                        @endphp
                                        
                                        @if ($hasEmail && $companyId)
                                            @if ($emailSent)
                                                <x-base.button type="button" variant="outline-success"
                                                    wire:click="resendVerificationEmail({{ $companyId }})"
                                                    class="text-green-600 hover:text-green-800 text-sm flex items-center gap-1"
                                                    wire:loading.attr="disabled"
                                                    wire:target="resendVerificationEmail({{ $companyId }})">
                                                    <svg xmlns="http://www.w3.org/2000/svg"  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail-minus-icon lucide-mail-minus w-4 h-4"><path d="M22 15V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12c0 1.1.9 2 2 2h8"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/><path d="M16 19h6"/></svg>
                                                    <span wire:loading.remove wire:target="resendVerificationEmail({{ $companyId }})">                                                        
                                                        Resend
                                                    </span>
                                                    <span wire:loading wire:target="resendVerificationEmail({{ $companyId }})">
                                                        <i class="fas fa-spinner fa-spin"></i>
                                                    </span>
                                                </x-base.button>
                                            @else
                                                <x-base.button type="button" variant="outline-success"
                                                    wire:click="sendVerificationEmail({{ $companyId }})"
                                                    class="text-purple-600 hover:text-purple-800 text-sm flex items-center gap-1"
                                                    wire:loading.attr="disabled"
                                                    wire:target="sendVerificationEmail({{ $companyId }})">
                                                    <svg xmlns="http://www.w3.org/2000/svg"  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail-minus-icon lucide-mail-minus w-4 h-4"><path d="M22 15V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12c0 1.1.9 2 2 2h8"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/><path d="M16 19h6"/></svg>
                                                    <span wire:loading.remove wire:target="sendVerificationEmail({{ $companyId }})">                                                        
                                                        Send Email
                                                    </span>
                                                    <span wire:loading wire:target="sendVerificationEmail({{ $companyId }})">
                                                        <i class="fas fa-spinner fa-spin"></i>
                                                    </span>
                                                </x-base.button>
                                            @endif
                                        @endif
                                    @endif
                                </div>
                            </x-base.table.td>
                        </x-base.table.tr>
                    @empty
                        <x-base.table.tr>
                            <x-base.table.td colspan="6" class="text-center">No employment records found. Please add
                                your employment history below.</x-base.table.td>
                        </x-base.table.tr>
                    @endforelse
                </x-base.table.tbody>
            </x-base.table>

        </div>
        <!-- Employment History Coverage Indicator -->
        <div class="mt-4 mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="flex justify-between items-center mb-2">
                <h4 class="text-sm font-semibold text-gray-700">Employment History Coverage</h4>
                <span class="text-sm font-medium {{ $this->employmentCoverage['is_complete'] ? 'text-success' : 'text-danger' }}">
                    {{ $this->employmentCoverage['total_years'] }} / {{ $this->employmentCoverage['required_years'] }} years
                    ({{ $this->employmentCoverage['coverage_percentage'] }}%)
                </span>
            </div>
            
            <!-- Progress Bar -->
            <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
                <div class="h-3 rounded-full transition-all duration-300 {{ $this->employmentCoverage['is_complete'] ? 'bg-success' : 'bg-danger' }}" 
                     style="width: {{ min(100, $this->employmentCoverage['coverage_percentage']) }}%">
                </div>
            </div>
            
            <!-- Coverage Breakdown -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-xs text-gray-600 mt-2">
                <div>
                    <span class="font-medium">Employment:</span> {{ $this->employmentCoverage['employment_years'] }} yrs
                </div>
                <div>
                    <span class="font-medium">Unemployment:</span> {{ $this->employmentCoverage['unemployment_years'] }} yrs
                </div>
                <div>
                    <span class="font-medium">Related:</span> {{ $this->employmentCoverage['related_employment_years'] }} yrs
                </div>
            </div>
            
            @if ($this->employmentCoverage['gap_count'] > 0)
                <div class="mt-2 p-2 bg-amber-50 rounded border border-amber-200">
                    <p class="text-xs text-amber-700">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong>{{ $this->employmentCoverage['gap_count'] }} gap(s) detected</strong> in your employment history. 
                        Please add unemployment periods or additional employment to fill these gaps.
                    </p>
                </div>
            @endif
            
            @if (!$this->employmentCoverage['is_complete'])
                <div class="mt-2 p-2 bg-amber-50 rounded border border-amber-200">
                    <p class="text-xs text-amber-700">
                        <i class="fas fa-info-circle mr-1"></i>
                        You need {{ round($this->employmentCoverage['required_years'] - $this->employmentCoverage['total_years'], 1) }} more years 
                        to meet the minimum requirement. Add more employment companies, unemployment periods, or related employment.
                    </p>
                </div>
            @endif
        </div>

        <div class="flex justify-between items-center mt-4">
            <div class="flex space-x-2">
                <x-base.button class="" variant="outline-success" wire:click="openSearchCompanyModal">
                    <i class="fas fa-search mr-1"></i> Search Company
                </x-base.button>
                <button type="button" wire:click="addEmploymentCompany"
                    class="bg-primary text-white py-1.5 px-3 rounded text-sm hover:bg-blue-800 transition">
                    <i class="fas fa-plus mr-1"></i> Add New Employment
                </button>

            </div>
        </div>
    </div>

    <!-- Modal para Unemployment Periods -->
    @if ($showUnemploymentForm)
        <div class="modal group bg-gradient-to-b from-theme-1/50 via-theme-2/50 to-black/50 transition-[visibility,opacity] w-screen h-screen fixed left-0 top-0 [&:not(.show)]:duration-[0s,0.2s] [&:not(.show)]:delay-[0.2s,0s] [&:not(.show)]:invisible [&:not(.show)]:opacity-0 [&.show]:visible [&.show]:opacity-100 [&.show]:duration-[0s,0.4s] overflow-y-auto show">            
            <div class="w-[90%] mx-auto bg-white relative rounded-md shadow-md transition-[margin-top,transform] duration-[0.4s,0.3s] -mt-4 group-[.show]:mt-40 group-[.modal-static]:scale-[1.05] sm:w-[750px] p-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="mr-auto text-base font-medium">{{ $editing_unemployment_index !== null ? 'Edit' : 'Add' }}
                        Unemployment Period</h3>
                    <button wire:click="closeUnemploymentForm" class="text-gray-500 hover:text-gray-700">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <x-base.form-label for="unemployment_form.start_date">Start Date*</x-base.form-label>   
                        <input type="text" 
                            value="{{ $unemployment_form['start_date'] ?? '' }}" 
                            onchange="@this.set('unemployment_form.start_date', this.value)" 
                            placeholder="MM/DD/YYYY" 
                            class="driver-datepicker w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3" />
                        @error('unemployment_form.start_date')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <x-base.form-label for="unemployment_form.end_date">End Date*</x-base.form-label>   
                        <input type="text" 
                            value="{{ $unemployment_form['end_date'] ?? '' }}" 
                            onchange="@this.set('unemployment_form.end_date', this.value)" 
                            placeholder="MM/DD/YYYY" 
                            class="driver-datepicker w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3" />
                        @error('unemployment_form.end_date')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <x-base.form-label for="unemployment_form.comments">Comments</x-base.form-label> 
                    <x-base.form-textarea wire:model="unemployment_form.comments" class="w-full px-3 py-2 border rounded"
                    rows="3" placeholder="Add any relevant details about this unemployment period" />                    
                </div>
                <x-base.dialog.footer>
                    <x-base.button class="mr-1 w-20" data-tw-dismiss="modal" type="button" variant="outline-secondary" wire:click="closeUnemploymentForm">
                        Cancel
                    </x-base.button>
                    <x-base.button class="w-20" type="submit" variant="primary" id="submit-service" wire:click="saveUnemploymentPeriod">
                        Save
                    </x-base.button>
                </x-base.dialog.footer>
            </div>
        </div>
    @endif

    <!-- Modal for Related Employment -->
    @if ($showRelatedEmploymentForm)
        <div class="modal group bg-gradient-to-b from-theme-1/50 via-theme-2/50 to-black/50 transition-[visibility,opacity] w-screen h-screen fixed left-0 top-0 [&:not(.show)]:duration-[0s,0.2s] [&:not(.show)]:delay-[0.2s,0s] [&:not(.show)]:invisible [&:not(.show)]:opacity-0 [&.show]:visible [&.show]:opacity-100 [&.show]:duration-[0s,0.4s] overflow-y-auto show">            
            <div class="w-[90%] mx-auto bg-white relative rounded-md shadow-md transition-[margin-top,transform] duration-[0.4s,0.3s] -mt-4 group-[.show]:mt-40 group-[.modal-static]:scale-[1.05] sm:w-[750px] p-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="mr-auto text-base font-medium">{{ $editing_related_employment_index !== null ? 'Edit' : 'Add' }} Related Employment</h3>
                    <button wire:click="closeRelatedEmploymentForm" class="text-gray-500 hover:text-gray-700">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <x-base.form-label for="related_employment_form.start_date">Start Date*</x-base.form-label>   
                        <input type="text" 
                            value="{{ $related_employment_form['start_date'] ?? '' }}" 
                            onchange="@this.set('related_employment_form.start_date', this.value)" 
                            placeholder="MM/DD/YYYY" 
                            class="driver-datepicker w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3" />
                        @error('related_employment_form.start_date')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <x-base.form-label for="related_employment_form.end_date">End Date*</x-base.form-label>   
                        <input type="text" 
                            value="{{ $related_employment_form['end_date'] ?? '' }}" 
                            onchange="@this.set('related_employment_form.end_date', this.value)" 
                            placeholder="MM/DD/YYYY" 
                            class="driver-datepicker w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3" />
                        @error('related_employment_form.end_date')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <x-base.form-label for="related_employment_form.position">Position*</x-base.form-label>
                    <x-base.form-input wire:model="related_employment_form.position" 
                        class="w-full px-3 py-2 border rounded"
                        placeholder="e.g., Cook, Warehouseman, Carpenter, Clerk" />
                    @error('related_employment_form.position')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-4">
                    <x-base.form-label for="related_employment_form.comments">Comments</x-base.form-label> 
                    <x-base.form-textarea wire:model="related_employment_form.comments" 
                        class="w-full px-3 py-2 border rounded"
                        rows="3" 
                        placeholder="Add any relevant details about this position" />                    
                </div>

                <x-base.dialog.footer>
                    <x-base.button class="mr-1 w-20" data-tw-dismiss="modal" type="button" variant="outline-secondary" wire:click="closeRelatedEmploymentForm">
                        Cancel
                    </x-base.button>
                    <x-base.button class="w-20" type="submit" variant="primary" wire:click="saveRelatedEmployment">
                        Save
                    </x-base.button>
                </x-base.dialog.footer>
            </div>
        </div>
    @endif

    <!-- Modal para Employment Companies -->
    @if ($showCompanyForm && !$showSearchCompanyModal)
        <div class="modal group bg-gradient-to-b from-theme-1/50 via-theme-2/50 to-black/50 transition-[visibility,opacity] w-screen h-screen fixed left-0 top-0 [&:not(.show)]:duration-[0s,0.2s] [&:not(.show)]:delay-[0.2s,0s] [&:not(.show)]:invisible [&:not(.show)]:opacity-0 [&.show]:visible [&.show]:opacity-100 [&.show]:duration-[0s,0.4s] overflow-y-auto show z-50">
            <div class="w-[90%] mx-auto bg-white relative rounded-md shadow-md transition-[margin-top,transform] duration-[0.4s,0.3s] -mt-4 group-[.show]:mt-4 group-[.modal-static]:scale-[1.05] sm:w-[750px] p-6 max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-6 pb-4 border-b">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900">
                            {{ $editing_company_index !== null ? 'Edit' : 'Add' }} Employment Information
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">Fill in the employment details below</p>
                    </div>
                    <button wire:click="closeCompanyForm" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Company Information Section -->
                <div class="border border-gray-200 p-5 rounded-lg bg-gray-50 mb-6">
                    <div class="flex items-center mb-4">
                        <svg class="h-5 w-5 text-gray-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <h4 class="text-md font-semibold text-gray-800">Company Information</h4>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                Company Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model.blur="company_form.company_name"
                                class="w-full text-sm border-slate-300 shadow-sm rounded-md py-2.5 px-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 {{ isset($company_form['is_from_master']) && $company_form['is_from_master'] ? 'bg-gray-100' : '' }} {{ $errors->has('company_form.company_name') ? 'border-red-500' : '' }}"
                                {{ isset($company_form['is_from_master']) && $company_form['is_from_master'] ? 'readonly' : '' }}
                                placeholder="Enter company name">
                            @error('company_form.company_name')
                                <p class="mt-1 text-xs text-red-600 flex items-center">
                                    <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone</label>
                            <input type="text" wire:model.blur="company_form.phone"
                                class="w-full text-sm border-slate-300 shadow-sm rounded-md py-2.5 px-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 {{ isset($company_form['is_from_master']) && $company_form['is_from_master'] ? 'bg-gray-100' : '' }}"
                                {{ isset($company_form['is_from_master']) && $company_form['is_from_master'] ? 'readonly' : '' }}
                                placeholder="(555) 123-4567">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Email <span class="text-gray-500 text-xs">(for verification)</span>
                        </label>
                        <input type="email" wire:model.blur="company_form.email"
                            class="w-full text-sm border-slate-300 shadow-sm rounded-md py-2.5 px-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 {{ isset($company_form['is_from_master']) && $company_form['is_from_master'] ? 'bg-gray-100' : '' }} {{ $errors->has('company_form.email') ? 'border-red-500' : '' }}"
                            {{ isset($company_form['is_from_master']) && $company_form['is_from_master'] ? 'readonly' : '' }}
                            placeholder="company@example.com">
                        @error('company_form.email')
                            <p class="mt-1 text-xs text-red-600 flex items-center">
                                <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Address</label>
                        <input type="text" wire:model.blur="company_form.address"
                            class="w-full text-sm border-slate-300 shadow-sm rounded-md py-2.5 px-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 {{ isset($company_form['is_from_master']) && $company_form['is_from_master'] ? 'bg-gray-100' : '' }}"
                            {{ isset($company_form['is_from_master']) && $company_form['is_from_master'] ? 'readonly' : '' }}
                            placeholder="123 Main Street">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">City</label>
                            <input type="text" wire:model.blur="company_form.city"
                                class="w-full text-sm border-slate-300 shadow-sm rounded-md py-2.5 px-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 {{ isset($company_form['is_from_master']) && $company_form['is_from_master'] ? 'bg-gray-100' : '' }}"
                                {{ isset($company_form['is_from_master']) && $company_form['is_from_master'] ? 'readonly' : '' }}
                                placeholder="City">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">State</label>
                            <select wire:model.blur="company_form.state"
                                class="w-full text-sm border-slate-300 shadow-sm rounded-md py-2.5 px-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 {{ isset($company_form['is_from_master']) && $company_form['is_from_master'] ? 'bg-gray-100' : '' }}"
                                {{ isset($company_form['is_from_master']) && $company_form['is_from_master'] ? 'disabled' : '' }}>
                                <option value="">Select State</option>
                                @foreach ($usStates as $code => $name)
                                    <option value="{{ $code }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">ZIP</label>
                            <input type="text" wire:model.blur="company_form.zip"
                                class="w-full text-sm border-slate-300 shadow-sm rounded-md py-2.5 px-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 {{ isset($company_form['is_from_master']) && $company_form['is_from_master'] ? 'bg-gray-100' : '' }}"
                                {{ isset($company_form['is_from_master']) && $company_form['is_from_master'] ? 'readonly' : '' }}
                                placeholder="12345">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Contact Person</label>
                            <input type="text" wire:model.blur="company_form.contact"
                                class="w-full text-sm border-slate-300 shadow-sm rounded-md py-2.5 px-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 {{ isset($company_form['is_from_master']) && $company_form['is_from_master'] ? 'bg-gray-100' : '' }}"
                                {{ isset($company_form['is_from_master']) && $company_form['is_from_master'] ? 'readonly' : '' }}
                                placeholder="Contact name">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Fax</label>
                            <input type="text" wire:model.blur="company_form.fax"
                                class="w-full text-sm border-slate-300 shadow-sm rounded-md py-2.5 px-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 {{ isset($company_form['is_from_master']) && $company_form['is_from_master'] ? 'bg-gray-100' : '' }}"
                                {{ isset($company_form['is_from_master']) && $company_form['is_from_master'] ? 'readonly' : '' }}
                                placeholder="Fax number">
                        </div>
                    </div>
                </div>

                <!-- Employment Details Section -->
                <div class="border border-gray-200 p-5 rounded-lg bg-white mb-6">
                    <div class="flex items-center mb-4">
                        <svg class="h-5 w-5 text-gray-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <h4 class="text-md font-semibold text-gray-800">Employment Details</h4>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                Employed From <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                value="{{ $company_form['employed_from'] ?? '' }}" 
                                onchange="@this.set('company_form.employed_from', this.value)" 
                                placeholder="MM/DD/YYYY" 
                                class="driver-datepicker w-full text-sm border-slate-300 shadow-sm rounded-md py-2.5 px-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 {{ $errors->has('company_form.employed_from') ? 'border-red-500' : '' }}" />
                            @error('company_form.employed_from')
                                <p class="mt-1 text-xs text-red-600 flex items-center">
                                    <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                Employed To <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                value="{{ $company_form['employed_to'] ?? '' }}" 
                                onchange="@this.set('company_form.employed_to', this.value)" 
                                placeholder="MM/DD/YYYY" 
                                class="driver-datepicker w-full text-sm border-slate-300 shadow-sm rounded-md py-2.5 px-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 {{ $errors->has('company_form.employed_to') ? 'border-red-500' : '' }}" />
                            @error('company_form.employed_to')
                                <p class="mt-1 text-xs text-red-600 flex items-center">
                                    <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Position(s) Held <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model.blur="company_form.positions_held"
                            class="w-full text-sm border-slate-300 shadow-sm rounded-md py-2.5 px-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 {{ $errors->has('company_form.positions_held') ? 'border-red-500' : '' }}"
                            placeholder="e.g., Truck Driver, Dispatcher">
                        @error('company_form.positions_held')
                            <p class="mt-1 text-xs text-red-600 flex items-center">
                                <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                        <div class="flex items-start mb-3">
                            <input type="checkbox" id="subject_to_fmcsr" wire:model="company_form.subject_to_fmcsr"
                                class="form-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded mt-0.5 mr-3">
                            <label for="subject_to_fmcsr" class="text-sm text-gray-700">
                                Were you subject to the Federal Motor Carrier Safety Regulations while employed by this employer?
                            </label>
                        </div>
                        <div class="flex items-start">
                            <input type="checkbox" id="safety_sensitive_function"
                                wire:model="company_form.safety_sensitive_function"
                                class="form-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded mt-0.5 mr-3">
                            <label for="safety_sensitive_function" class="text-sm text-gray-700">
                                Was this job designated as a safety sensitive function in any D.O.T. regulated mode subject to alcohol and controlled substance testing requirements as required by 49 CFR Part 40?
                            </label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Reason for Leaving <span class="text-red-500">*</span>
                        </label>
                        <select wire:model.live="company_form.reason_for_leaving"
                            class="w-full text-sm border-slate-300 shadow-sm rounded-md py-2.5 px-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 {{ $errors->has('company_form.reason_for_leaving') ? 'border-red-500' : '' }}">
                            <option value="">Select Reason</option>
                            <option value="resignation">Resignation</option>
                            <option value="termination">Termination</option>
                            <option value="layoff">Layoff</option>
                            <option value="retirement">Retirement</option>
                            <option value="other">Other</option>
                        </select>
                        @error('company_form.reason_for_leaving')
                            <p class="mt-1 text-xs text-red-600 flex items-center">
                                <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    @if ($company_form['reason_for_leaving'] === 'other')
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                If other, please describe <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model.blur="company_form.other_reason_description"
                                class="w-full text-sm border-slate-300 shadow-sm rounded-md py-2.5 px-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 {{ $errors->has('company_form.other_reason_description') ? 'border-red-500' : '' }}"
                                placeholder="Describe reason for leaving">
                            @error('company_form.other_reason_description')
                                <p class="mt-1 text-xs text-red-600 flex items-center">
                                    <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    @endif

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Additional Explanation</label>
                        <textarea wire:model.blur="company_form.explanation" rows="3"
                            class="w-full text-sm border-slate-300 shadow-sm rounded-md py-2.5 px-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" 
                            placeholder="Any additional information about this employment..."></textarea>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-3 pt-4 border-t">
                    <x-base.button 
                        type="button" 
                        wire:click="closeCompanyForm" 
                        variant="outline-secondary"
                        class="px-6">
                        Cancel
                    </x-base.button>
                    <x-base.button 
                        type="button" 
                        wire:click="saveCompany" 
                        variant="primary"
                        class="px-6"
                        wire:loading.attr="disabled"
                        wire:target="saveCompany">
                        <span wire:loading.remove wire:target="saveCompany">
                            <i class="fas fa-save mr-1"></i> Save Employment
                        </span>
                        <span wire:loading wire:target="saveCompany">
                            <i class="fas fa-spinner fa-spin mr-1"></i> Saving...
                        </span>
                    </x-base.button>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal para Búsqueda de Empresas -->
    @if ($showSearchCompanyModal && !$showCompanyForm)
        <div class="modal group bg-gradient-to-b from-theme-1/50 via-theme-2/50 to-black/50 transition-[visibility,opacity] w-screen h-screen fixed left-0 top-0 [&:not(.show)]:duration-[0s,0.2s] [&:not(.show)]:delay-[0.2s,0s] [&:not(.show)]:invisible [&:not(.show)]:opacity-0 [&.show]:visible [&.show]:opacity-100 [&.show]:duration-[0s,0.4s] overflow-y-auto show z-50">
            <div class="w-[90%] mx-auto bg-white relative rounded-md shadow-md transition-[margin-top,transform] duration-[0.4s,0.3s] -mt-4 group-[.show]:mt-20 group-[.modal-static]:scale-[1.05] sm:w-[800px] p-6">
                <div class="flex justify-between items-center mb-6 pb-4 border-b">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900">Search Previous Employer</h3>
                        <p class="text-sm text-gray-500 mt-1">Find and select a company from our database</p>
                    </div>
                    <button wire:click="closeSearchCompanyModal" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Search Box -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Company Name</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" 
                            wire:model.live.debounce.300ms="companySearchTerm"
                            placeholder="Type to search by company name..."
                            class="w-full pl-10 pr-4 py-3 text-sm border-slate-300 shadow-sm rounded-md focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            autofocus>
                        <div wire:loading wire:target="companySearchTerm" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg class="animate-spin h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                    @if ($companySearchTerm)
                        <p class="mt-2 text-xs text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Showing results for "{{ $companySearchTerm }}"
                        </p>
                    @endif
                </div>

                <!-- Search Results -->
                <div class="mb-6">
                    <div class="max-h-96 overflow-y-auto border border-gray-200 rounded-lg">
                        @if (count($searchResults) > 0)
                            <div class="divide-y divide-gray-200">
                                @foreach ($searchResults as $company)
                                    <div class="p-4 hover:bg-gray-50 transition cursor-pointer" wire:click="selectCompany({{ $company['id'] }})">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <h4 class="text-sm font-semibold text-gray-900">{{ $company['company_name'] }}</h4>
                                                <div class="mt-1 flex items-center text-xs text-gray-500 space-x-4">
                                                    @if (!empty($company['city']) || !empty($company['state']))
                                                        <span class="flex items-center">
                                                            <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            </svg>
                                                            {{ $company['city'] }}{{ !empty($company['city']) && !empty($company['state']) ? ', ' : '' }}{{ $company['state'] }}
                                                        </span>
                                                    @endif
                                                    @if (!empty($company['phone']))
                                                        <span class="flex items-center">
                                                            <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                            </svg>
                                                            {{ $company['phone'] }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                            <x-base.button 
                                                type="button" 
                                                variant="primary" 
                                                size="sm"
                                                wire:click.stop="selectCompany({{ $company['id'] }})">
                                                <i class="fas fa-check mr-1"></i> Select
                                            </x-base.button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="p-8 text-center">
                                @if ($companySearchTerm)
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">No companies found</h3>
                                    <p class="mt-1 text-sm text-gray-500">
                                        No companies match "{{ $companySearchTerm }}"
                                    </p>
                                    <div class="mt-6">
                                        <x-base.button 
                                            type="button" 
                                            variant="outline-primary"
                                            wire:click="addEmploymentCompany">
                                            <i class="fas fa-plus mr-1"></i> Add New Company
                                        </x-base.button>
                                    </div>
                                @else
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">Start searching</h3>
                                    <p class="mt-1 text-sm text-gray-500">
                                        Type a company name above to search our database
                                    </p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between items-center pt-4 border-t">
                    <x-base.button 
                        type="button" 
                        variant="outline-secondary"
                        wire:click="addEmploymentCompany">
                        <i class="fas fa-plus mr-1"></i> Add New Company
                    </x-base.button>
                    <x-base.button 
                        type="button" 
                        variant="outline-secondary" 
                        wire:click="closeSearchCompanyModal">
                        Close
                    </x-base.button>
                </div>
            </div>
        </div>
    @endif
    
    <!-- Modal for Related Employment Form -->
    @if ($showRelatedEmploymentForm)
        <div class="modal group bg-gradient-to-b from-theme-1/50 via-theme-2/50 to-black/50 transition-[visibility,opacity] w-screen h-screen fixed left-0 top-0 [&:not(.show)]:duration-[0s,0.2s] [&:not(.show)]:delay-[0.2s,0s] [&:not(.show)]:invisible [&:not(.show)]:opacity-0 [&.show]:visible [&.show]:opacity-100 [&.show]:duration-[0s,0.4s] overflow-y-auto show">
            <div class="w-[90%] mx-auto bg-white relative rounded-sm shadow-md transition-[margin-top,transform] duration-[0.4s,0.3s] -mt-4 group-[.show]:mt-40 group-[.modal-static]:scale-[1.05] sm:w-[750px] p-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="mr-auto text-base font-medium">{{ $editing_related_employment_index !== null ? 'Edit' : 'Add' }}
                        Other Job Position</h3>
                    <button wire:click="closeRelatedEmploymentForm" class="text-gray-500 hover:text-gray-700">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="mt-3 w-full flex-1 xl:mt-0">
                        <x-base.form-label for="related_employment_form.start_date">Start Date*</x-base.form-label>                        
                        <input type="text" 
                            value="{{ $related_employment_form['start_date'] ?? '' }}" 
                            onchange="@this.set('related_employment_form.start_date', this.value)" 
                            placeholder="MM/DD/YYYY" 
                            class="driver-datepicker form-control w-full rounded-md border border-slate-300/60 px-3 py-2 shadow-sm" />
                        @error('related_employment_form.start_date')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <x-base.form-label for="related_employment_form.end_date">End Date*</x-base.form-label>
                        <input type="text" 
                            value="{{ $related_employment_form['end_date'] ?? '' }}" 
                            onchange="@this.set('related_employment_form.end_date', this.value)" 
                            placeholder="MM/DD/YYYY" 
                            class="driver-datepicker form-control w-full rounded-md border border-slate-300/60 px-3 py-2 shadow-sm" />
                        @error('related_employment_form.end_date')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="mb-4">                    
                    <x-base.form-label for="related_employment_form.position">Position*</x-base.form-label>
                    {{-- <input type="text" wire:model="related_employment_form.position"
                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3"
                        placeholder="E.g. Taxi Driver, Forklift Operator"> --}}
                    <x-base.form-input type="text" wire:model="related_employment_form.position"
                        class="w-full px-3 py-2 border rounded" placeholder="E.g. Taxi Driver, Forklift Operator" />
                    @error('related_employment_form.position')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
                <div class="mb-4">
                    <x-base.form-label for="related_employment_form.comments">Comments</x-base.form-label>                    
                    {{-- <textarea wire:model="related_employment_form.comments" rows="3"
                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3"
                        placeholder="Add any relevant details about this position"></textarea> --}}
                        <x-base.form-textarea wire:model="related_employment_form.comments" class="w-full px-3 py-2 border rounded"
                        rows="3" placeholder="Add any relevant details about this position" />
                </div>
                <x-base.dialog.footer>
                    <x-base.button class="mr-1 w-20" data-tw-dismiss="modal" type="button" variant="outline-secondary" wire:click="closeRelatedEmploymentForm">
                        Cancel
                    </x-base.button>
                    <x-base.button class="w-20" type="submit" variant="primary" id="submit-service" wire:click="saveRelatedEmployment">
                        Save
                    </x-base.button>
                </x-base.dialog.footer>
            </div>
        </div>
    @endif

    <!-- Employment history validation -->
    <div class="flex items-center mb-6">
        <input type="checkbox" id="has_completed_employment_history" wire:model="has_completed_employment_history"
            class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded mr-2">
        <label for="has_completed_employment_history" class="text-sm font-medium text-gray-700">
            <span class="text-red-500">*</span> Is the information above correct and contains no missing information?
        </label>
    </div>

    @if (!$this->employmentCoverage['is_complete'])
        <div class="mt-2 p-3 bg-amber-50 rounded-md border border-amber-200">
            <p class="text-amber-700 text-sm">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                <strong>Warning:</strong> Your employment history covers {{ $this->employmentCoverage['total_years'] }} years, 
                which is less than the required {{ $this->employmentCoverage['required_years'] }} years. 
                You can still proceed, but please ensure you have provided complete information including all unemployment periods and related employment.
            </p>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if ($showDeleteConfirmationModal)
        <div class="modal group bg-gradient-to-b from-theme-1/50 via-theme-2/50 to-black/50 transition-[visibility,opacity] w-screen h-screen fixed left-0 top-0 [&:not(.show)]:duration-[0s,0.2s] [&:not(.show)]:delay-[0.2s,0s] [&:not(.show)]:invisible [&:not(.show)]:opacity-0 [&.show]:visible [&.show]:opacity-100 [&.show]:duration-[0s,0.4s] overflow-y-auto show z-50">
            <div class="w-[90%] mx-auto bg-white relative rounded-md shadow-md transition-[margin-top,transform] duration-[0.4s,0.3s] -mt-4 group-[.show]:mt-40 group-[.modal-static]:scale-[1.05] sm:w-[500px] p-6">
                <div class="flex items-center mb-4">
                    <svg class="h-10 w-10 text-red-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900">Confirm Deletion</h3>
                </div>

                <p class="text-sm text-gray-600 mb-6">
                    Are you sure you want to delete this 
                    @if ($deleteType === 'employment')
                        employment record
                    @elseif ($deleteType === 'unemployment')
                        unemployment period
                    @elseif ($deleteType === 'related_employment')
                        related employment record
                    @endif
                    ? This action cannot be undone.
                </p>

                <div class="flex justify-end space-x-3">
                    <x-base.button type="button" wire:click="cancelDelete" variant="outline-secondary">
                        Cancel
                    </x-base.button>
                    <x-base.button type="button" wire:click="confirmDelete" variant="danger">
                        Delete
                    </x-base.button>
                </div>
            </div>
        </div>
    @endif

    <!-- Bulk Email Send Confirmation Modal -->
    @if ($showBulkEmailConfirmationModal)
        <div class="modal group bg-gradient-to-b from-theme-1/50 via-theme-2/50 to-black/50 transition-[visibility,opacity] w-screen h-screen fixed left-0 top-0 [&:not(.show)]:duration-[0s,0.2s] [&:not(.show)]:delay-[0.2s,0s] [&:not(.show)]:invisible [&:not(.show)]:opacity-0 [&.show]:visible [&.show]:opacity-100 [&.show]:duration-[0s,0.4s] overflow-y-auto show z-50">
            <div class="w-[90%] mx-auto bg-white relative rounded-md shadow-md transition-[margin-top,transform] duration-[0.4s,0.3s] -mt-4 group-[.show]:mt-40 group-[.modal-static]:scale-[1.05] sm:w-[500px] p-6">
                <div class="flex items-center mb-4">
                    <svg class="h-10 w-10 text-blue-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900">Send Verification Emails</h3>
                </div>

                @php
                    $unsentEmailCount = collect($employment_companies)->filter(function($company) {
                        return !empty($company['email']) && !($company['email_sent'] ?? false);
                    })->count();
                @endphp

                <p class="text-sm text-gray-600 mb-6">
                    You are about to send verification emails to <strong>{{ $unsentEmailCount }}</strong> 
                    {{ $unsentEmailCount === 1 ? 'company' : 'companies' }}. 
                    This will request employment verification from your previous employers.
                </p>

                <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-6">
                    <p class="text-xs text-blue-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>Note:</strong> Emails will be sent sequentially. You'll receive a summary when the process completes.
                    </p>
                </div>

                <div class="flex justify-end space-x-3">
                    <x-base.button type="button" wire:click="cancelBulkEmailSend" variant="outline-secondary">
                        Cancel
                    </x-base.button>
                    <x-base.button 
                        type="button" 
                        wire:click="sendBulkVerificationEmails" 
                        variant="primary"
                        wire:loading.attr="disabled"
                        wire:target="sendBulkVerificationEmails">
                        <span wire:loading.remove wire:target="sendBulkVerificationEmails">
                            <i class="fas fa-paper-plane mr-1"></i> Send Emails
                        </span>
                        <span wire:loading wire:target="sendBulkVerificationEmails">
                            <i class="fas fa-spinner fa-spin mr-1"></i> Sending...
                        </span>
                    </x-base.button>
                </div>
            </div>
        </div>
    @endif

    <!-- Navigation Buttons -->
    <div class="mt-8 px-5 py-5 border-t border-slate-200/60 dark:border-darkmode-400">
        <div class="flex flex-col sm:flex-row justify-between gap-4">
            <div class="w-full sm:w-auto">
                <x-base.button type="button" wire:click="previous" class="w-full sm:w-44" variant="secondary">
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
