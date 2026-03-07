@extends('../themes/' . $activeTheme)
@section('title', 'Default Company Policy Document')

@php
$breadcrumbLinks = [
    ['label' => 'App', 'url' => route('admin.dashboard')],
    ['label' => 'Document Types', 'url' => route('admin.document-types.index')],
    ['label' => 'Default Company Policy', 'active' => true],
];
@endphp

@pushOnce('styles')
@vite('resources/css/vendors/toastify.css')
@endPushOnce

@section('subcontent')
<x-base.notificationtoast.notification-toast :notification="session('notification')" />

<!-- Header -->
<div class="box box--stacked p-8 mb-8">
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
            <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                <x-base.lucide class="w-8 h-8 text-primary" icon="FileText" />
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-800 mb-2">Default Company Policy Document</h1>
                <p class="text-slate-600">Upload the default policy PDF that drivers will see in the Company Policy step during registration.</p>
            </div>
        </div>
        <div class="flex flex-col text-center sm:justify-end sm:flex-row gap-3 w-full md:w-[300px]">
            <x-base.button as="a" href="{{ route('admin.document-types.index') }}" variant="outline-secondary">
                <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="ArrowLeft" />
                Back to Document Types
            </x-base.button>
        </div>
    </div>
</div>

<div class="grid grid-cols-12 gap-x-6 gap-y-10">
    <div class="col-span-12 lg:col-span-8">
        <div class="box box--stacked flex flex-col">
            <!-- Current Document -->
            <div class="p-6 border-b border-slate-200/60">
                <h2 class="text-lg font-semibold text-slate-800 mb-1">Current Policy Document</h2>
                <p class="text-sm text-slate-500">This file is shown when drivers click "View Policy Document" in the Company Policy step.</p>
            </div>

            <div class="p-6">
                @if($policyMedia)
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-5 bg-green-50 border border-green-200 rounded-xl mb-6">
                        <div class="flex items-center gap-4 mb-3 sm:mb-0">
                            <div class="p-3 bg-green-100 rounded-lg">
                                <x-base.lucide class="w-6 h-6 text-green-600" icon="FileCheck" />
                            </div>
                            <div>
                                <p class="font-semibold text-green-800 text-lg">{{ $policyMedia->file_name }}</p>
                                <p class="text-sm text-green-600">
                                    {{ number_format($policyMedia->size / 1024, 1) }} KB &middot;
                                    Uploaded {{ $policyMedia->created_at->format('M d, Y h:i A') }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ $policyMedia->getUrl() }}" target="_blank"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="Eye" />
                                View
                            </a>
                            <a href="{{ $policyMedia->getUrl() }}" download
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100 transition">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="Download" />
                                Download
                            </a>
                            <form action="{{ route('admin.document-types.delete-default-policy') }}" method="POST"
                                onsubmit="return confirm('Are you sure you want to delete this policy document?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="Trash2" />
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="flex items-center p-5 bg-amber-50 border border-amber-200 rounded-xl mb-6">
                        <x-base.lucide class="w-6 h-6 text-amber-600 mr-3 flex-shrink-0" icon="AlertTriangle" />
                        <div>
                            <p class="font-medium text-amber-800">No policy document uploaded</p>
                            <p class="text-sm text-amber-700 mt-1">Drivers will see a broken link in the Company Policy step. Please upload a PDF below.</p>
                        </div>
                    </div>
                @endif

                <!-- Upload Form -->
                <div class="p-5 bg-slate-50 border border-slate-200 rounded-xl">
                    <h3 class="font-medium text-slate-800 mb-3">
                        {{ $policyMedia ? 'Replace Policy Document' : 'Upload Policy Document' }}
                    </h3>
                    <form action="{{ route('admin.document-types.upload-default-policy') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Select PDF file</label>
                            <input type="file" name="policy_file" accept=".pdf" required
                                class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-5 file:rounded-lg file:border file:border-slate-300 file:text-sm file:font-medium file:bg-white file:text-slate-700 hover:file:bg-slate-50 transition cursor-pointer" />
                            <p class="text-xs text-slate-500 mt-2">Only PDF files are accepted. Maximum size: 10 MB.</p>
                            @error('policy_file')
                                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                        <x-base.button type="submit" variant="primary">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="Upload" />
                            {{ $policyMedia ? 'Replace Document' : 'Upload Document' }}
                        </x-base.button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Sidebar -->
    <div class="col-span-12 lg:col-span-4">
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-2 mb-4">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Info" />
                <h3 class="font-semibold text-slate-800">How it works</h3>
            </div>
            <div class="space-y-3 text-sm text-slate-600">
                <p>The uploaded PDF will be displayed as the <strong>"View Policy Document"</strong> link in the driver registration flow.</p>
                <p>This applies to:</p>
                <ul class="list-disc list-inside space-y-1 ml-2">
                    <li>Admin driver registration</li>
                    <li>Carrier driver registration</li>
                    <li>Driver self-registration</li>
                </ul>
                <p class="pt-2">If a carrier uploads their own custom policy document, it will take priority over this default one for their drivers.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@pushOnce('scripts')
@vite('resources/js/app.js')
@vite('resources/js/pages/notification.js')
@endPushOnce
