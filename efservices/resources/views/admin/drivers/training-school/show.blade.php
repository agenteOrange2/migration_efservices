@extends('../themes/' . $activeTheme)
@section('title', 'Training School Details')

@php
    use Illuminate\Support\Facades\Storage;
    
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Training Schools', 'url' => route('admin.training-schools.index')],
        ['label' => 'Details', 'active' => true],
    ];
@endphp

@section('subcontent')

<!-- Professional Breadcrumbs -->
<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

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
        <div class="flex items-center gap-4">
            <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                <x-base.lucide class="w-8 h-8 text-primary" icon="GraduationCap" />
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-800 mb-2">{{ $school->school_name }}</h1>
                <p class="text-slate-600">Training School Details & Records</p>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <x-base.button as="a" href="{{ route('admin.training-schools.index') }}" variant="secondary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                Back to List
            </x-base.button>
            <x-base.button as="a" href="{{ route('admin.training-schools.edit', $school->id) }}" variant="primary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="Edit" />
                Edit School
            </x-base.button>
        </div>
    </div>
</div>        

<div class="grid grid-cols-12 gap-6">
    <!-- Driver Information -->
    <div class="col-span-12 lg:col-span-6">
        <div class="box box--stacked flex flex-col p-6 h-fit">
            <div class="flex items-center gap-3 mb-6">
                <x-base.lucide class="w-5 h-5 text-primary" icon="User" />
                <h2 class="text-lg font-semibold text-slate-800">Driver Information</h2>
            </div>

            <div class="space-y-3">
                <div class="grid grid-cols-1 gap-3">
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Driver Name</label>
                        <div class="flex items-center gap-2">
                            <x-base.lucide class="w-4 h-4 text-slate-400" icon="User" />
                            <p class="text-sm font-semibold text-slate-800">{{ implode(' ', array_filter([$school->userDriverDetail->user->name, $school->userDriverDetail->middle_name, $school->userDriverDetail->last_name])) ?: 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Driver Phone</label>
                        <div class="flex items-center gap-2">
                            <x-base.lucide class="w-4 h-4 text-slate-400" icon="Phone" />
                            <p class="text-sm font-semibold text-slate-800">{{ $school->userDriverDetail->phone ?: 'Not specified' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- School Information -->
    <div class="col-span-12 lg:col-span-6">
        <div class="box box--stacked flex flex-col p-6 h-fit">
            <div class="flex items-center gap-3 mb-6">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Building" />
                <h2 class="text-lg font-semibold text-slate-800">School Information</h2>
            </div>

            <div class="space-y-3">
                <div class="grid grid-cols-1 gap-3">
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">School Name</label>
                        <div class="flex items-center gap-2">
                            <x-base.lucide class="w-4 h-4 text-slate-400" icon="Building" />
                            <p class="text-sm font-semibold text-slate-800">{{ $school->school_name ?: 'Not specified' }}</p>
                        </div>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Location</label>
                        <div class="flex items-center gap-2">
                            <x-base.lucide class="w-4 h-4 text-slate-400" icon="MapPin" />
                            <p class="text-sm font-semibold text-slate-800">
                                @if($school->city || $school->state)
                                    {{ $school->city ? $school->city . ', ' : '' }}{{ $school->state ?: '' }}
                                @else
                                    Not specified
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Training Dates & Status -->
    <div class="col-span-12 lg:col-span-6">
        <div class="box box--stacked flex flex-col p-6 h-fit">
            <div class="flex items-center gap-3 mb-6">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Calendar" />
                <h2 class="text-lg font-semibold text-slate-800">Training Period</h2>
            </div>

            <div class="space-y-3">
                <div class="grid grid-cols-1 gap-3">
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Start Date</label>
                        <div class="flex items-center gap-2">
                            <x-base.lucide class="w-4 h-4 text-slate-400" icon="Calendar" />
                            <p class="text-sm font-semibold text-slate-800">{{ $school->date_start ? \Carbon\Carbon::parse($school->date_start)->format('M d, Y') : 'Not specified' }}</p>
                        </div>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">End Date</label>
                        <div class="flex items-center gap-2">
                            <x-base.lucide class="w-4 h-4 text-slate-400" icon="CalendarX" />
                            <p class="text-sm font-semibold text-slate-800">{{ $school->date_end ? \Carbon\Carbon::parse($school->date_end)->format('M d, Y') : 'Not specified' }}</p>
                        </div>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Status</label>
                        <div class="mt-1">
                            @if ($school->graduated)
                                <x-base.badge variant="success" class="gap-1.5">
                                    <span class="w-1.5 h-1.5 bg-success rounded-full"></span>
                                    Graduated
                                </x-base.badge>
                            @else
                                <x-base.badge variant="warning" class="gap-1.5">
                                    <span class="w-1.5 h-1.5 bg-warning rounded-full"></span>
                                    In Progress
                                </x-base.badge>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Safety Information -->
    <div class="col-span-12 lg:col-span-6">
        <div class="box box--stacked flex flex-col p-6 h-fit">
            <div class="flex items-center gap-3 mb-6">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Shield" />
                <h2 class="text-lg font-semibold text-slate-800">Safety Information</h2>
            </div>

            <div class="space-y-3">
                <div class="grid grid-cols-1 gap-3">
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Safety Regulations</label>
                        <div class="mt-1">
                            @if ($school->subject_to_safety_regulations)
                                <x-base.badge variant="primary" class="gap-1.5">
                                    <x-base.lucide class="w-4 h-4" icon="ShieldCheck" />
                                    Subject to Regulations
                                </x-base.badge>
                            @else
                                <x-base.badge variant="secondary" class="gap-1.5">
                                    <x-base.lucide class="w-4 h-4" icon="ShieldX" />
                                    Not Subject
                                </x-base.badge>
                            @endif
                        </div>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Safety Functions</label>
                        <div class="mt-1">
                            @if ($school->performed_safety_functions)
                                <x-base.badge variant="info" class="gap-1.5">
                                    <x-base.lucide class="w-4 h-4" icon="CheckCircle2" />
                                    Functions Performed
                                </x-base.badge>
                            @else
                                <x-base.badge variant="danger" class="gap-1.5">
                                    <x-base.lucide class="w-4 h-4" icon="XCircle" />
                                    Not Performed
                                </x-base.badge>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Training Skills -->
    <div class="col-span-12">
        <div class="box box--stacked flex flex-col p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="Award" />
                    <h2 class="text-lg font-semibold text-slate-800">Training Skills</h2>
                    @php
                        $skills = $school->training_skills ?? [];
                        if (is_string($skills)) {
                            $skills = json_decode($skills, true) ?? [];
                        }
                    @endphp
                    @if($skills && count($skills) > 0)
                        <x-base.badge variant="primary" class="px-3 py-1.5">
                            {{ count($skills) }} Skill{{ count($skills) > 1 ? 's' : '' }}
                        </x-base.badge>
                    @endif
                </div>
            </div>
            
            <div class="p-5">
                @if($skills && count($skills) > 0)
                    <div class="flex flex-wrap gap-2">
                        @foreach($skills as $skill)
                            <x-base.badge variant="success" class="gap-1.5">
                                <x-base.lucide class="w-4 h-4" icon="CheckCircle" />
                                {{ $skill }}
                            </x-base.badge>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="flex flex-col items-center justify-center">
                            <div class="bg-slate-100 rounded-full p-4 mb-4 w-16 h-16 flex items-center justify-center">
                                <x-base.lucide class="w-8 h-8 text-slate-400" icon="Award" />
                            </div>
                            <p class="text-slate-600 font-medium mb-1">No training skills specified</p>
                            <p class="text-sm text-slate-500">Skills will appear here once they are added to the training record.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Documents -->
    <div class="col-span-12">
        <div class="box box--stacked">
            <div class="p-6 border-b border-slate-200/60">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
                        <h2 class="text-lg font-semibold text-slate-800">Documents</h2>
                        @php
                            $documents = \Spatie\MediaLibrary\MediaCollections\Models\Media::where('model_type', \App\Models\Admin\Driver\DriverTrainingSchool::class)
                                ->where('model_id', $school->id)
                                ->where('collection_name', 'school_certificates')
                                ->get();
                        @endphp
                        @if (count($documents) > 0)
                            <x-base.badge variant="primary" class="px-3 py-1.5">
                                {{ count($documents) }} Document{{ count($documents) > 1 ? 's' : '' }}
                            </x-base.badge>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                @if (count($documents) > 0)
                    <table class="w-full">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200/60">
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">#</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Size</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Uploaded</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60">
                            @foreach ($documents as $index => $document)
                                <tr id="document-row-{{ $document->id }}" class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-slate-700">{{ $index + 1 }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            @php
                                                $extension = pathinfo($document->file_name, PATHINFO_EXTENSION);
                                                $iconClass = 'file-text';
                                                
                                                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                                                    $iconClass = 'image';
                                                } elseif (in_array($extension, ['pdf'])) {
                                                    $iconClass = 'file-text';
                                                } elseif (in_array($extension, ['doc', 'docx'])) {
                                                    $iconClass = 'file';
                                                } elseif (in_array($extension, ['xls', 'xlsx', 'csv'])) {
                                                    $iconClass = 'file-spreadsheet';
                                                }
                                            @endphp
                                            <x-base.lucide class="w-5 h-5 text-primary" icon="{{ $iconClass }}" />
                                            <span class="text-sm font-medium text-slate-800">{{ $document->file_name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-slate-700">{{ strtoupper(pathinfo($document->file_name, PATHINFO_EXTENSION)) }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-slate-700">{{ $document->human_readable_size }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-slate-700">{{ $document->created_at->format('M d, Y H:i') }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-2">
                                            <x-base.button as="a" 
                                                href="{{ route('admin.training-schools.docs.preview', $document->id) }}" 
                                                target="_blank"
                                                variant="primary" 
                                                size="sm"
                                                class="gap-1.5"
                                                title="View">
                                                <x-base.lucide class="w-4 h-4" icon="Eye" />
                                            </x-base.button>
                                            <x-base.button as="a" 
                                                href="{{ route('admin.training-schools.docs.preview', $document->id) }}?download=true"
                                                variant="success" 
                                                size="sm"
                                                class="gap-1.5 text-white"
                                                title="Download">
                                                <x-base.lucide class="w-4 h-4" icon="Download" />
                                            </x-base.button>
                                            <x-base.button 
                                                type="button" 
                                                data-tw-toggle="modal"
                                                data-tw-target="#delete-document-modal-{{ $document->id }}"
                                                variant="danger" 
                                                size="sm"
                                                class="gap-1.5"
                                                title="Delete">
                                                <x-base.lucide class="w-4 h-4" icon="Trash2" />
                                            </x-base.button>
                                        </div>
                                        
                                        <!-- Delete Document Modal -->
                                        <x-base.dialog id="delete-document-modal-{{ $document->id }}" size="md">
                                            <x-base.dialog.panel>
                                                <div class="p-5 text-center">
                                                    <x-base.lucide class="mx-auto mt-3 h-16 w-16 text-danger" icon="XCircle" />
                                                    <div class="mt-5 text-2xl font-semibold text-slate-800">Are you sure?</div>
                                                    <div class="mt-2 text-slate-500">
                                                        Do you really want to delete this document? <br>
                                                        This process cannot be undone.
                                                    </div>
                                                </div>
                                                <form action="{{ route('admin.training-schools.docs.delete', $document->id) }}" method="POST" class="px-5 pb-8 text-center">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-base.button data-tw-dismiss="modal" type="button" variant="outline-secondary" class="mr-2">
                                                        Cancel
                                                    </x-base.button>
                                                    <x-base.button type="submit" variant="danger">
                                                        Delete
                                                    </x-base.button>
                                                </form>
                                            </x-base.dialog.panel>
                                        </x-base.dialog>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="p-12 text-center">
                        <div class="flex flex-col items-center justify-center py-8">
                            <div class="bg-slate-100 rounded-full p-4 mb-4 w-16 h-16 flex items-center justify-center">
                                <x-base.lucide class="w-8 h-8 text-slate-400" icon="FileText" />
                            </div>
                            <p class="text-slate-600 font-medium mb-1">No documents uploaded</p>
                            <p class="text-sm text-slate-500 mb-5">No documents have been uploaded for this training school yet.</p>
                            <x-base.button as="a" href="{{ route('admin.training-schools.edit', $school->id) }}" variant="primary" class="gap-2">
                                <x-base.lucide class="w-4 h-4" icon="Upload" />
                                Upload Documents
                            </x-base.button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
