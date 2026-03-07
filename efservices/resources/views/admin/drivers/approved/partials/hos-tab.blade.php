{{-- HOS Tab Content --}}
<div class="space-y-6">
    {{-- HOS Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Driving Hours Card --}}
        <div class="box box--stacked p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="p-2 bg-primary/10 rounded-lg">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="Truck" />
                </div>
                @if(($hosData['statistics']['remaining_driving_minutes'] ?? 0) <= 60 && ($hosData['statistics']['remaining_driving_minutes'] ?? 0) > 0)
                    <x-base.badge variant="warning" class="text-xs">
                        <x-base.lucide class="w-3 h-3" icon="AlertTriangle" />
                    </x-base.badge>
                @endif
            </div>
            <div class="text-2xl font-bold text-slate-800 mb-1">
                {{ floor(($hosData['statistics']['current_day_driving_minutes'] ?? 0) / 60) }}h 
                {{ ($hosData['statistics']['current_day_driving_minutes'] ?? 0) % 60 }}m
            </div>
            <div class="text-sm text-slate-600">Driving Hours Today</div>
            <div class="text-xs text-slate-500 mt-2">
                Remaining: {{ floor(($hosData['statistics']['remaining_driving_minutes'] ?? 0) / 60) }}h 
                {{ ($hosData['statistics']['remaining_driving_minutes'] ?? 0) % 60 }}m
            </div>
        </div>

        {{-- On-Duty Hours Card --}}
        <div class="box box--stacked p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="p-2 bg-success/10 rounded-lg">
                    <x-base.lucide class="w-5 h-5 text-success" icon="Clock" />
                </div>
                @if(($hosData['statistics']['remaining_on_duty_minutes'] ?? 0) <= 60 && ($hosData['statistics']['remaining_on_duty_minutes'] ?? 0) > 0)
                    <x-base.badge variant="warning" class="text-xs">
                        <x-base.lucide class="w-3 h-3" icon="AlertTriangle" />
                    </x-base.badge>
                @endif
            </div>
            <div class="text-2xl font-bold text-slate-800 mb-1">
                {{ floor(($hosData['statistics']['current_day_on_duty_minutes'] ?? 0) / 60) }}h 
                {{ ($hosData['statistics']['current_day_on_duty_minutes'] ?? 0) % 60 }}m
            </div>
            <div class="text-sm text-slate-600">On-Duty Hours Today</div>
            <div class="text-xs text-slate-500 mt-2">
                Remaining: {{ floor(($hosData['statistics']['remaining_on_duty_minutes'] ?? 0) / 60) }}h 
                {{ ($hosData['statistics']['remaining_on_duty_minutes'] ?? 0) % 60 }}m
            </div>
        </div>

        {{-- Active Violations Card --}}
        <div class="box box--stacked p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="p-2 {{ ($hosData['statistics']['active_violations_count'] ?? 0) > 0 ? 'bg-danger/10' : 'bg-slate-100' }} rounded-lg">
                    <x-base.lucide class="w-5 h-5 {{ ($hosData['statistics']['active_violations_count'] ?? 0) > 0 ? 'text-danger' : 'text-slate-400' }}" icon="AlertCircle" />
                </div>
            </div>
            <div class="text-2xl font-bold {{ ($hosData['statistics']['active_violations_count'] ?? 0) > 0 ? 'text-danger' : 'text-slate-800' }} mb-1">
                {{ $hosData['statistics']['active_violations_count'] ?? 0 }}
            </div>
            <div class="text-sm text-slate-600">Active Violations</div>
            <div class="text-xs text-slate-500 mt-2">
                Forgiven: {{ $hosData['statistics']['forgiven_violations_count'] ?? 0 }}
            </div>
        </div>

        {{-- Documents Card --}}
        <div class="box box--stacked p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="p-2 bg-info/10 rounded-lg">
                    <x-base.lucide class="w-5 h-5 text-info" icon="FileText" />
                </div>
            </div>
            <div class="text-2xl font-bold text-slate-800 mb-1">
                {{ $hosData['documents']->count() ?? 0 }}
            </div>
            <div class="text-sm text-slate-600">Recent Documents</div>
            <div class="text-xs text-slate-500 mt-2">
                Last 10 documents
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="box box--stacked p-5">
        <div class="flex flex-wrap gap-3">
            <x-base.button 
                type="button" 
                variant="success" 
                class="gap-2"
                data-tw-toggle="modal"
                data-tw-target="#driver-daily-log-modal">
                <x-base.lucide class="w-4 h-4" icon="Calendar" />
                Generate Daily Log
            </x-base.button>
            <x-base.button 
                type="button" 
                variant="info" 
                class="gap-2"
                data-tw-toggle="modal"
                data-tw-target="#driver-monthly-summary-modal">
                <x-base.lucide class="w-4 h-4" icon="BarChart" />
                Monthly Summary
            </x-base.button>
            <x-base.button 
                type="button" 
                variant="warning" 
                class="gap-2"
                data-tw-toggle="modal"
                data-tw-target="#driver-fmcsa-monthly-modal">
                <x-base.lucide class="w-4 h-4" icon="FileText" />
                FMCSA Monthly
            </x-base.button>
            <x-base.button 
                as="a"
                href="{{ route('admin.hos.documents.index', ['driver_id' => $driver->id]) }}"
                variant="primary" 
                class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="FileText" />
                View All HOS Documents
            </x-base.button>
        </div>
    </div>

    {{-- Active Violations Section --}}
    @if(($hosData['violations']->count() ?? 0) > 0)
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-3 mb-4">
                <x-base.lucide class="w-5 h-5 text-danger" icon="AlertCircle" />
                <h3 class="text-lg font-semibold text-slate-800">Active Violations</h3>
                <x-base.badge variant="danger">{{ $hosData['violations']->count() }}</x-base.badge>
            </div>

            <div class="space-y-3">
                @foreach($hosData['violations'] as $violation)
                    <div class="border border-{{ $violation->violation_severity === 'critical' ? 'danger' : ($violation->violation_severity === 'moderate' ? 'warning' : 'slate-200') }} rounded-lg p-4 bg-{{ $violation->violation_severity === 'critical' ? 'danger' : ($violation->violation_severity === 'moderate' ? 'warning' : 'slate-50') }}/5">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <x-base.badge variant="{{ $violation->violation_severity === 'critical' ? 'danger' : ($violation->violation_severity === 'moderate' ? 'warning' : 'secondary') }}" class="text-xs">
                                        {{ ucfirst($violation->violation_severity ?? 'minor') }}
                                    </x-base.badge>
                                    <span class="font-semibold text-slate-800">{{ $violation->violation_type }}</span>
                                </div>
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <span class="text-slate-500">Date:</span>
                                        <span class="text-slate-700 font-medium">{{ \Carbon\Carbon::parse($violation->violation_date)->format('M d, Y') }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-500">Hours Exceeded:</span>
                                        <span class="text-slate-700 font-medium">{{ $violation->hours_exceeded ?? 'N/A' }}</span>
                                    </div>
                                    @if($violation->fmcsa_rule_reference)
                                        <div class="col-span-2">
                                            <span class="text-slate-500">FMCSA Rule:</span>
                                            <span class="text-slate-700 font-medium">{{ $violation->fmcsa_rule_reference }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <x-base.button 
                                    as="a"
                                    href="{{ route('admin.hos.violations.show', $violation->id) }}"
                                    variant="outline-primary" 
                                    size="sm"
                                    class="gap-1.5">
                                    <x-base.lucide class="w-3.5 h-3.5" icon="Eye" />
                                    Details
                                </x-base.button>
                                @if(!$violation->is_forgiven)
                                    <x-base.button 
                                        as="a"
                                        href="{{ route('admin.hos.violations.forgive', $violation->id) }}"
                                        variant="outline-success" 
                                        size="sm"
                                        class="gap-1.5">
                                        <x-base.lucide class="w-3.5 h-3.5" icon="Check" />
                                        Forgive
                                    </x-base.button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Recent HOS Entries --}}
    <div class="box box--stacked p-6">
        <div class="flex items-center gap-3 mb-4">
            <x-base.lucide class="w-5 h-5 text-primary" icon="Clock" />
            <h3 class="text-lg font-semibold text-slate-800">Recent HOS Entries</h3>
        </div>

        @if(($hosData['entries']->count() ?? 0) > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="text-left py-3 px-4 font-semibold text-slate-700 uppercase text-xs tracking-wide">Status</th>
                            <th class="text-left py-3 px-4 font-semibold text-slate-700 uppercase text-xs tracking-wide">Start Time</th>
                            <th class="text-left py-3 px-4 font-semibold text-slate-700 uppercase text-xs tracking-wide">End Time</th>
                            <th class="text-left py-3 px-4 font-semibold text-slate-700 uppercase text-xs tracking-wide">Duration</th>
                            <th class="text-left py-3 px-4 font-semibold text-slate-700 uppercase text-xs tracking-wide">Location</th>
                            <th class="text-left py-3 px-4 font-semibold text-slate-700 uppercase text-xs tracking-wide">Flags</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hosData['entries'] as $entry)
                            <tr class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors">
                                <td class="py-3 px-4">
                                    @php
                                        $statusConfig = [
                                            'on_duty_driving' => ['icon' => 'Truck', 'color' => 'primary', 'label' => 'Driving'],
                                            'on_duty_not_driving' => ['icon' => 'Clock', 'color' => 'warning', 'label' => 'On Duty'],
                                            'off_duty' => ['icon' => 'Moon', 'color' => 'secondary', 'label' => 'Off Duty'],
                                            'sleeper_berth' => ['icon' => 'Bed', 'color' => 'info', 'label' => 'Sleeper'],
                                        ];
                                        $config = $statusConfig[$entry->status] ?? ['icon' => 'Circle', 'color' => 'secondary', 'label' => ucfirst($entry->status)];
                                    @endphp
                                    <x-base.badge variant="{{ $config['color'] }}" class="gap-1.5 text-xs">
                                        <x-base.lucide class="w-3 h-3" icon="{{ $config['icon'] }}" />
                                        {{ $config['label'] }}
                                    </x-base.badge>
                                </td>
                                <td class="py-3 px-4 text-sm text-slate-700">
                                    {{ \Carbon\Carbon::parse($entry->start_time)->format('M d, Y H:i') }}
                                </td>
                                <td class="py-3 px-4 text-sm text-slate-700">
                                    {{ $entry->end_time ? \Carbon\Carbon::parse($entry->end_time)->format('M d, Y H:i') : 'Ongoing' }}
                                </td>
                                <td class="py-3 px-4 text-sm text-slate-700">
                                    @if($entry->end_time)
                                        @php
                                            $duration = \Carbon\Carbon::parse($entry->start_time)->diffInMinutes(\Carbon\Carbon::parse($entry->end_time));
                                            $hours = floor($duration / 60);
                                            $minutes = $duration % 60;
                                        @endphp
                                        {{ $hours }}h {{ $minutes }}m
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-sm text-slate-600">
                                    {{ $entry->location ?? 'N/A' }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex gap-1">
                                        @if($entry->is_manual_entry)
                                            <x-base.badge variant="soft-warning" class="text-xs">Manual</x-base.badge>
                                        @endif
                                        @if($entry->is_ghost_log)
                                            <x-base.badge variant="soft-danger" class="text-xs">Ghost</x-base.badge>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <div class="p-3 bg-slate-100 rounded-full w-16 h-16 mx-auto mb-3 flex items-center justify-center">
                    <x-base.lucide class="w-8 h-8 text-slate-400" icon="Clock" />
                </div>
                <p class="text-slate-500">No HOS entries found for this driver.</p>
            </div>
        @endif
    </div>

    {{-- Recent Documents --}}
    <div class="box box--stacked p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
                <h3 class="text-lg font-semibold text-slate-800">Recent Documents</h3>
            </div>

            @if(($hosData['documents']->count() ?? 0) > 0)
                <div id="bulk-actions-container" class="hidden">
                    <form id="bulk-delete-form" action="{{ route('admin.hos.documents.bulk-destroy') }}" method="POST" onsubmit="return confirmBulkDelete(event)">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="document_ids" id="selected-document-ids" value="">
                        <x-base.button
                            type="submit"
                            variant="danger"
                            size="sm"
                            class="gap-1.5">
                            <x-base.lucide class="w-3.5 h-3.5" icon="Trash2" />
                            Delete Selected (<span id="selected-count">0</span>)
                        </x-base.button>
                    </form>
                </div>
            @endif
        </div>

        @if(($hosData['documents']->count() ?? 0) > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="text-left py-3 px-4 font-semibold text-slate-700 uppercase text-xs tracking-wide w-10">
                                <input
                                    type="checkbox"
                                    id="select-all-documents"
                                    class="rounded border-slate-300 text-primary focus:ring-primary"
                                    onchange="toggleAllDocuments(this)">
                            </th>
                            <th class="text-left py-3 px-4 font-semibold text-slate-700 uppercase text-xs tracking-wide">Type</th>
                            <th class="text-left py-3 px-4 font-semibold text-slate-700 uppercase text-xs tracking-wide">Date</th>
                            <th class="text-left py-3 px-4 font-semibold text-slate-700 uppercase text-xs tracking-wide">Size</th>
                            <th class="text-left py-3 px-4 font-semibold text-slate-700 uppercase text-xs tracking-wide">Status</th>
                            <th class="text-right py-3 px-4 font-semibold text-slate-700 uppercase text-xs tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hosData['documents'] as $document)
                            <tr class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors">
                                <td class="py-3 px-4">
                                    <input
                                        type="checkbox"
                                        class="document-checkbox rounded border-slate-300 text-primary focus:ring-primary"
                                        value="{{ $document->id }}"
                                        onchange="updateBulkActions()">
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2">
                                        @if($document->collection_name === 'daily_logs')
                                            <x-base.lucide class="w-4 h-4 text-success" icon="Calendar" />
                                            <span class="font-medium text-sm">Daily Log</span>
                                        @elseif($document->getCustomProperty('document_type') === 'monthly_summary')
                                            <x-base.lucide class="w-4 h-4 text-amber-500" icon="FileText" />
                                            <span class="font-medium text-sm">FMCSA Monthly</span>
                                        @else
                                            <x-base.lucide class="w-4 h-4 text-info" icon="BarChart" />
                                            <span class="font-medium text-sm">Monthly Summary</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-sm text-slate-700">
                                    {{ \Carbon\Carbon::parse($document->getCustomProperty('document_date') ?? $document->created_at)->format('M d, Y') }}
                                </td>
                                <td class="py-3 px-4 text-sm text-slate-600">
                                    {{ number_format($document->size / 1024, 2) }} KB
                                </td>
                                <td class="py-3 px-4">
                                    @if($document->getCustomProperty('signed_at'))
                                        <x-base.badge variant="success" class="gap-1.5 text-xs">
                                            <x-base.lucide class="w-3 h-3" icon="CheckCircle" />
                                            Signed
                                        </x-base.badge>
                                    @else
                                        <x-base.badge variant="secondary" class="gap-1.5 text-xs">
                                            <x-base.lucide class="w-3 h-3" icon="FileText" />
                                            Unsigned
                                        </x-base.badge>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-base.button
                                            as="a"
                                            href="{{ $document->getUrl() }}"
                                            target="_blank"
                                            variant="outline-primary"
                                            size="sm"
                                            class="gap-1.5">
                                            <x-base.lucide class="w-3.5 h-3.5" icon="Eye" />
                                            View
                                        </x-base.button>
                                        <x-base.button
                                            as="a"
                                            href="{{ route('admin.hos.documents.download', $document->id) }}"
                                            variant="primary"
                                            size="sm"
                                            class="gap-1.5">
                                            <x-base.lucide class="w-3.5 h-3.5" icon="Download" />
                                            Download
                                        </x-base.button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <div class="p-3 bg-slate-100 rounded-full w-16 h-16 mx-auto mb-3 flex items-center justify-center">
                    <x-base.lucide class="w-8 h-8 text-slate-400" icon="FileText" />
                </div>
                <p class="text-slate-500">No HOS documents found for this driver.</p>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    /**
     * Toggle all document checkboxes
     */
    function toggleAllDocuments(selectAllCheckbox) {
        const checkboxes = document.querySelectorAll('.document-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });
        updateBulkActions();
    }

    /**
     * Update bulk actions visibility and selected count
     */
    function updateBulkActions() {
        const checkboxes = document.querySelectorAll('.document-checkbox:checked');
        const bulkActionsContainer = document.getElementById('bulk-actions-container');
        const selectedCount = document.getElementById('selected-count');
        const selectAllCheckbox = document.getElementById('select-all-documents');

        if (checkboxes.length > 0) {
            bulkActionsContainer.classList.remove('hidden');
            selectedCount.textContent = checkboxes.length;

            // Update selected document IDs
            const selectedIds = Array.from(checkboxes).map(cb => cb.value);
            document.getElementById('selected-document-ids').value = JSON.stringify(selectedIds);
        } else {
            bulkActionsContainer.classList.add('hidden');
            selectedCount.textContent = '0';
        }

        // Update select all checkbox state
        const allCheckboxes = document.querySelectorAll('.document-checkbox');
        const allChecked = allCheckboxes.length > 0 && allCheckboxes.length === checkboxes.length;
        selectAllCheckbox.checked = allChecked;
        selectAllCheckbox.indeterminate = checkboxes.length > 0 && !allChecked;
    }

    /**
     * Confirm bulk delete action
     */
    function confirmBulkDelete(event) {
        const checkboxes = document.querySelectorAll('.document-checkbox:checked');
        const count = checkboxes.length;

        if (count === 0) {
            event.preventDefault();
            alert('Please select at least one document to delete.');
            return false;
        }

        const confirmed = confirm(`Are you sure you want to delete ${count} document(s)? This action cannot be undone.`);

        if (!confirmed) {
            event.preventDefault();
            return false;
        }

        // Convert selected IDs to array format for Laravel
        const selectedIds = Array.from(checkboxes).map(cb => cb.value);

        // Clear the JSON input and create individual hidden inputs for each ID
        const form = document.getElementById('bulk-delete-form');
        const existingInputs = form.querySelectorAll('input[name="document_ids[]"]');
        existingInputs.forEach(input => input.remove());

        selectedIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'document_ids[]';
            input.value = id;
            form.appendChild(input);
        });

        return true;
    }
</script>
@endpush
