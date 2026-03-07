@extends('../themes/' . $activeTheme)
@section('title', 'Medical Record Details')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Medical Records', 'url' => route('admin.medical-records.index')],
        ['label' => 'Medical Record Details', 'active' => true],
    ];
@endphp

@section('subcontent')
    <div>
        <!-- Flash Messages -->
        @if (session('success'))
            <x-base.alert variant="success" dismissible>
                {{ session('success') }}
            </x-base.alert>
        @endif

        @if (session('error'))
            <x-base.alert variant="danger" dismissible>
                {{ session('error') }}
            </x-base.alert>
        @endif

        <!-- Professional Header -->
        <div class="box box--stacked p-8 mb-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <x-base.lucide class="w-8 h-8 text-primary" icon="Heart" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-2">Medical Record Details</h1>
                        <p class="text-slate-600">Medical Record: {{ $medicalRecord->social_security_number }}</p>
                    </div>
                </div>
                <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                    <x-base.button as="a" href="{{ route('admin.medical-records.index') }}"
                        variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4" icon="arrow-left" />
                        Back
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('admin.medical-records.edit', $medicalRecord->id) }}"
                        variant="primary">
                        <x-base.lucide class="mr-2 h-4 w-4" icon="edit" />
                        Edit
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('admin.medical-records.docs.show', $medicalRecord->id) }}"
                        variant="outline-primary">
                        <x-base.lucide class="mr-2 h-4 w-4" icon="file-text" />
                        Documents ({{ $totalDocuments }})
                    </x-base.button>
                </div>
            </div>
        </div>

        <div class="mt-3.5 grid grid-cols-12 gap-y-10 gap-x-6">
            <!-- Información Básica -->
            <!-- Social Security Information -->
            <div class="col-span-12 xl:col-span-6">
                <div class="box box--stacked p-5 h-full">
                    <div class="flex items-center border-b border-dashed border-slate-300/70 pb-5 mb-5">
                        <x-base.lucide class="mr-2 h-6 w-6 text-primary" icon="credit-card" />
                        <h3 class="text-base font-medium">Social Security Information</h3>
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 mb-4">
                        <div class="flex flex-col">
                            <div class="text-xs uppercase tracking-widest text-slate-500">Driver</div>
                            <div class="mt-1 text-base font-medium">
                                @if ($medicalRecord->driverDetail && $medicalRecord->driverDetail->user)
                                    {{ $medicalRecord->driverDetail->user->name }} {{ $medicalRecord->driverDetail->middle_name ?? '' }} {{ $medicalRecord->driverDetail->last_name ?? '' }}
                                @else
                                    <span class="text-slate-500">N/A</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <div class="text-xs uppercase tracking-widest text-slate-500">Social Security Number</div>
                            <div class="mt-1 text-base font-medium">
                                {{ $medicalRecord->social_security_number ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <div class="text-xs uppercase tracking-widest text-slate-500">Hire Date</div>
                            <div class="mt-1 text-base font-medium">
                                @if ($medicalRecord->hire_date)
                                    {{ \Carbon\Carbon::parse($medicalRecord->hire_date)->format('M d, Y') }}
                                @else
                                    <span class="text-slate-500">N/A</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <div class="text-xs uppercase tracking-widest text-slate-500">Location</div>
                            <div class="mt-1 text-base font-medium">
                                {{ $medicalRecord->location ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <div class="text-xs uppercase tracking-widest text-slate-500">Status</div>
                            <div class="mt-1">
                                @if ($medicalRecord->is_suspended)
                                    <div class="inline-flex items-center rounded-full bg-danger/10 px-2 py-1 text-xs font-medium text-danger">
                                        Suspended
                                    </div>
                                @elseif($medicalRecord->is_terminated)
                                    <div class="inline-flex items-center rounded-full bg-warning/10 px-2 py-1 text-xs font-medium text-warning">
                                        Terminated
                                    </div>
                                @else
                                    <div class="inline-flex items-center rounded-full bg-success/10 px-2 py-1 text-xs font-medium text-success">
                                        Active
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Social Security Card Document -->
                    <div class="mt-4 pt-4 border-t border-dashed border-slate-300/70">
                        <div class="text-xs uppercase tracking-widest text-slate-500 mb-3">Social Security Card Document</div>
                        @php
                            $socialSecurityCard = $medicalRecord->getFirstMedia('social_security_card');
                        @endphp
                        @if($socialSecurityCard)
                            @php
                                $isSSImage = in_array($socialSecurityCard->mime_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
                            @endphp
                            @if($isSSImage)
                                <div class="mb-3 rounded-lg overflow-hidden border border-slate-200">
                                    <a href="{{ $socialSecurityCard->getUrl() }}" target="_blank">
                                        <img src="{{ $socialSecurityCard->getUrl() }}" alt="Social Security Card" 
                                            class="w-full h-auto max-h-48 object-contain bg-white">
                                    </a>
                                </div>
                            @endif
                            <div class="flex items-center justify-between p-2 bg-slate-50 rounded border">
                                <div class="flex items-center gap-2">
                                    <x-base.lucide class="h-4 w-4 text-success" icon="file-check" />
                                    <span class="text-sm truncate max-w-[200px]">{{ $socialSecurityCard->file_name }}</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <a href="{{ $socialSecurityCard->getUrl() }}" target="_blank" 
                                        class="p-1 text-primary hover:bg-primary/10 rounded" title="View">
                                        <x-base.lucide class="h-4 w-4" icon="eye" />
                                    </a>
                                    <a href="{{ $socialSecurityCard->getUrl() }}" download 
                                        class="p-1 text-slate-500 hover:bg-slate-100 rounded" title="Download">
                                        <x-base.lucide class="h-4 w-4" icon="download" />
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="flex items-center gap-2 p-3 bg-slate-50 rounded border text-slate-400">
                                <x-base.lucide class="h-4 w-4" icon="file-x" />
                                <span class="text-sm">No Social Security Card uploaded</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Medical Certification Information -->
            <div class="col-span-12 xl:col-span-6">
                <div class="box box--stacked p-5 h-full">
                    <div class="flex items-center border-b border-dashed border-slate-300/70 pb-5 mb-5">
                        <x-base.lucide class="mr-2 h-6 w-6 text-primary" icon="heart-pulse" />
                        <h3 class="text-base font-medium">Medical Certification Information</h3>
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 mb-4">
                        <div class="flex flex-col">
                            <div class="text-xs uppercase tracking-widest text-slate-500">Medical Examiner Name</div>
                            <div class="mt-1 text-base font-medium">
                                {{ $medicalRecord->medical_examiner_name ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <div class="text-xs uppercase tracking-widest text-slate-500">Registry Number</div>
                            <div class="mt-1 text-base font-medium">
                                {{ $medicalRecord->medical_examiner_registry_number ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <div class="text-xs uppercase tracking-widest text-slate-500">Medical Card Expiration</div>
                            <div class="mt-1 flex items-center gap-2">
                                @if ($medicalRecord->medical_card_expiration_date)
                                    <span class="text-base font-medium">
                                        {{ \Carbon\Carbon::parse($medicalRecord->medical_card_expiration_date)->format('M d, Y') }}
                                    </span>
                                    @php
                                        $daysUntilExpiration = now()->diffInDays($medicalRecord->medical_card_expiration_date, false);
                                    @endphp
                                    @if ($daysUntilExpiration < 0)
                                        <div class="inline-flex items-center rounded-full bg-danger/10 px-2 py-1 text-xs font-medium text-danger">
                                            Expired
                                        </div>
                                    @elseif($daysUntilExpiration <= 30)
                                        <div class="inline-flex items-center rounded-full bg-warning/10 px-2 py-1 text-xs font-medium text-warning">
                                            Expires Soon
                                        </div>
                                    @else
                                        <div class="inline-flex items-center rounded-full bg-success/10 px-2 py-1 text-xs font-medium text-success">
                                            Valid
                                        </div>
                                    @endif
                                @else
                                    <span class="text-slate-500">N/A</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Medical Card Document -->
                    <div class="mt-4 pt-4 border-t border-dashed border-slate-300/70">
                        <div class="text-xs uppercase tracking-widest text-slate-500 mb-3">Medical Card Document</div>
                        @php
                            $medicalCard = $medicalRecord->getFirstMedia('medical_card');
                        @endphp
                        @if($medicalCard)
                            @php
                                $isMCImage = in_array($medicalCard->mime_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
                            @endphp
                            @if($isMCImage)
                                <div class="mb-3 rounded-lg overflow-hidden border border-slate-200">
                                    <a href="{{ $medicalCard->getUrl() }}" target="_blank">
                                        <img src="{{ $medicalCard->getUrl() }}" alt="Medical Card" 
                                            class="w-full h-auto max-h-48 object-contain bg-white">
                                    </a>
                                </div>
                            @endif
                            <div class="flex items-center justify-between p-2 bg-slate-50 rounded border">
                                <div class="flex items-center gap-2">
                                    <x-base.lucide class="h-4 w-4 text-success" icon="file-check" />
                                    <span class="text-sm truncate max-w-[200px]">{{ $medicalCard->file_name }}</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <a href="{{ $medicalCard->getUrl() }}" target="_blank" 
                                        class="p-1 text-primary hover:bg-primary/10 rounded" title="View">
                                        <x-base.lucide class="h-4 w-4" icon="eye" />
                                    </a>
                                    <a href="{{ $medicalCard->getUrl() }}" download 
                                        class="p-1 text-slate-500 hover:bg-slate-100 rounded" title="Download">
                                        <x-base.lucide class="h-4 w-4" icon="download" />
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="flex items-center gap-2 p-3 bg-slate-50 rounded border text-slate-400">
                                <x-base.lucide class="h-4 w-4" icon="file-x" />
                                <span class="text-sm">No Medical Card uploaded</span>
                            </div>
                        @endif
                    </div>

                    @if ($medicalRecord->notes)
                        <div class="mt-4 pt-4 border-t border-dashed border-slate-300/70">
                            <div class="flex flex-col">
                                <div class="text-xs uppercase tracking-widest text-slate-500">Notes</div>
                                <div class="mt-2 text-sm text-slate-600 bg-slate-50 p-3 rounded-md">
                                    {{ $medicalRecord->notes }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Additional Documents -->
        <div class="col-span-12 mt-5">
            <div class="box box--stacked p-5">
                <div class="flex items-center justify-between border-b border-dashed border-slate-300/70 pb-5 mb-5">
                    <div class="flex items-center">
                        <x-base.lucide class="mr-2 h-6 w-6 text-primary" icon="paperclip" />
                        <h3 class="text-base font-medium">Additional Documents</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-base.button type="button"
                            onclick="document.getElementById('uploadForm').style.display = document.getElementById('uploadForm').style.display === 'none' ? 'block' : 'none'"
                            variant="primary" size="sm">
                            <x-base.lucide class="mr-1 h-4 w-4" icon="upload" />
                            Upload Documents
                        </x-base.button>
                        <x-base.button as="a"
                            href="{{ route('admin.medical-records.docs.show', $medicalRecord->id) }}"
                            variant="outline-primary" size="sm">
                            View All Documents
                        </x-base.button>
                    </div>
                </div>

                <!-- Upload Form -->
                <div id="uploadForm" style="display: none;"
                    class="mb-5 p-4 bg-slate-50 rounded-lg border border-slate-200">
                    <form action="{{ route('admin.medical-records.upload.documents', $medicalRecord->id) }}"
                        method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Select Documents</label>
                            <input type="file" name="documents[]" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary/90">
                            <p class="mt-1 text-xs text-slate-500">Supported formats: PDF, JPG, PNG, DOC, DOCX. Max size:
                                10MB per file.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-base.button type="submit" variant="primary" size="sm">
                                <x-base.lucide class="mr-1 h-4 w-4" icon="upload" />
                                Upload
                            </x-base.button>
                            <x-base.button type="button"
                                onclick="document.getElementById('uploadForm').style.display = 'none'"
                                variant="outline-secondary" size="sm">
                                Cancel
                            </x-base.button>
                        </div>
                    </form>
                </div>
                @if ($medicalRecord->getMedia('medical_documents')->count() > 0)
                    <div class="overflow-auto xl:overflow-visible">
                        <x-base.table class="border-spacing-y-[10px] border-separate -mt-2">
                            <x-base.table.thead>
                                <x-base.table.tr>
                                    <x-base.table.th class="border-b-0 whitespace-nowrap">
                                        Document Name
                                    </x-base.table.th>
                                    <x-base.table.th class="border-b-0 whitespace-nowrap">
                                        Type
                                    </x-base.table.th>
                                    <x-base.table.th class="border-b-0 whitespace-nowrap">
                                        Size
                                    </x-base.table.th>
                                    <x-base.table.th class="border-b-0 whitespace-nowrap">
                                        Upload Date
                                    </x-base.table.th>
                                    <x-base.table.th class="border-b-0 whitespace-nowrap">
                                        Actions
                                    </x-base.table.th>
                                </x-base.table.tr>
                            </x-base.table.thead>
                            <x-base.table.tbody>
                                @foreach ($medicalRecord->getMedia('medical_documents')->take(5) as $document)
                                    <x-base.table.tr class="intro-x">
                                        <x-base.table.td
                                            class="first:rounded-l-md last:rounded-r-md bg-white border-b-0 dark:bg-darkmode-600 shadow-[20px_3px_20px_#0000000b]">
                                            <div class="flex items-center">
                                                <x-base.lucide class="mr-2 h-4 w-4 text-slate-500" icon="file-text" />
                                                <span class="font-medium">{{ $document->name }}</span>
                                            </div>
                                        </x-base.table.td>
                                        <x-base.table.td
                                            class="first:rounded-l-md last:rounded-r-md bg-white border-b-0 dark:bg-darkmode-600 shadow-[20px_3px_20px_#0000000b]">
                                            <div
                                                class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">
                                                {{ strtoupper($document->mime_type) }}
                                            </div>
                                        </x-base.table.td>
                                        <x-base.table.td
                                            class="first:rounded-l-md last:rounded-r-md bg-white border-b-0 dark:bg-darkmode-600 shadow-[20px_3px_20px_#0000000b]">
                                            <span class="text-slate-500">{{ $document->human_readable_size }}</span>
                                        </x-base.table.td>
                                        <x-base.table.td
                                            class="first:rounded-l-md last:rounded-r-md bg-white border-b-0 dark:bg-darkmode-600 shadow-[20px_3px_20px_#0000000b]">
                                            <span
                                                class="text-slate-500">{{ $document->created_at->format('M d, Y H:i') }}</span>
                                        </x-base.table.td>
                                        <x-base.table.td
                                            class="first:rounded-l-md last:rounded-r-md bg-white border-b-0 dark:bg-darkmode-600 shadow-[20px_3px_20px_#0000000b]">
                                            <div class="flex items-center gap-2">
                                                <x-base.button as="a"
                                                    href="{{ route('admin.medical-records.doc.preview', $document->id) }}"
                                                    target="_blank" variant="outline-primary" size="sm"
                                                    title="View document">
                                                    <x-base.lucide class="h-4 w-4" icon="eye" />
                                                </x-base.button>
                                                <x-base.button type="button"
                                                    onclick="confirmDelete({{ $document->id }})" variant="outline-danger"
                                                    size="sm" title="Delete document">
                                                    <x-base.lucide class="h-4 w-4" icon="trash-2" />
                                                </x-base.button>
                                            </div>
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                @endforeach
                            </x-base.table.tbody>
                        </x-base.table>
                    </div>
                    @if ($medicalRecord->getMedia('medical_documents')->count() > 5)
                        <div class="mt-4 text-center">
                            <x-base.button as="a"
                                href="{{ route('admin.medical-records.docs.show', $medicalRecord->id) }}"
                                variant="outline-primary" size="sm">
                                <x-base.lucide class="mr-1 h-4 w-4" icon="eye" />
                                View All {{ $medicalRecord->getMedia('medical_documents')->count() }} Documents
                            </x-base.button>
                        </div>
                    @endif
                @else
                    <div class="flex items-center justify-center py-8">
                        <div class="text-center">
                            <x-base.lucide class="mx-auto h-12 w-12 text-slate-400" icon="file-x" />
                            <p class="mt-2 text-sm text-slate-500">No documents uploaded yet.</p>
                            <x-base.button type="button"
                                onclick="document.getElementById('uploadForm').style.display = 'block'"
                                variant="outline-primary" size="sm" class="mt-3">
                                <x-base.lucide class="mr-1 h-4 w-4" icon="upload" />
                                Upload Documents
                            </x-base.button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- JavaScript for delete confirmation -->
        <script>
            function confirmDelete(documentId) {
                if (confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
                    // Create a form to submit the delete request
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/admin/medical-records/documents/${documentId}`;

                    // Add CSRF token
                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';
                    form.appendChild(csrfToken);

                    // Add method override for DELETE
                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'DELETE';
                    form.appendChild(methodInput);

                    document.body.appendChild(form);
                    form.submit();
                }
            }
        </script>
    </div>
@endsection
