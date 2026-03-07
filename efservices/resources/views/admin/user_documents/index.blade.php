@extends('../themes/' . $activeTheme)

@section('title', 'Upload Documents for ' . $carrier->name)

@section('subcontent')
    <div class="container mx-auto px-4 py-6">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-800 mb-2">Documents for {{ $carrier->name }}</h1>
            <p class="text-slate-600">Upload and manage carrier documents</p>
        </div>

        <!-- Success Message -->
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center gap-2">
                    <x-base.lucide class="w-5 h-5 text-green-600" icon="CheckCircle" />
                    <p class="text-green-800 font-medium">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        <!-- Documents Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($documents as $item)
                @php
                    $statusConfig = [
                        'Approved' => ['class' => 'bg-green-50 border-green-200', 'badge' => 'bg-green-100 text-green-800', 'icon' => 'CheckCircle'],
                        'In Process' => ['class' => 'bg-blue-50 border-blue-200', 'badge' => 'bg-blue-100 text-blue-800', 'icon' => 'RefreshCw'],
                        'Pending' => ['class' => 'bg-yellow-50 border-yellow-200', 'badge' => 'bg-yellow-100 text-yellow-800', 'icon' => 'Clock'],
                        'Rejected' => ['class' => 'bg-red-50 border-red-200', 'badge' => 'bg-red-100 text-red-800', 'icon' => 'XCircle'],
                        'Not Uploaded' => ['class' => 'bg-slate-50 border-slate-200', 'badge' => 'bg-slate-100 text-slate-800', 'icon' => 'AlertCircle']
                    ];
                    
                    $config = $statusConfig[$item['status_name']] ?? $statusConfig['Not Uploaded'];
                @endphp

                <div class="rounded-lg border {{ $config['class'] }} p-5">
                    <!-- Document Header -->
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h3 class="font-semibold text-slate-800 mb-2">{{ $item['type']->name }}</h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $config['badge'] }}">
                                <x-base.lucide class="w-3 h-3 mr-1" icon="{{ $config['icon'] }}" />
                                {{ $item['status_name'] }}
                            </span>
                        </div>
                    </div>

                    <!-- Document Description -->
                    @if($item['type']->description)
                        <p class="text-xs text-slate-600 mb-3">{{ $item['type']->description }}</p>
                    @endif

                    <!-- Admin Notes -->
                    @if ($item['notes'])
                        <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                            <div class="flex items-start gap-2">
                                <x-base.lucide class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" icon="MessageSquare" />
                                <div class="flex-1">
                                    <p class="text-xs font-medium text-amber-800 mb-1">Admin Notes:</p>
                                    <p class="text-xs text-amber-700">{{ $item['notes'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="mt-4">
                        @if ($item['file_url'])
                            <div class="space-y-3">
                                <a href="{{ $item['file_url'] }}" target="_blank" 
                                   class="w-full inline-flex items-center justify-center px-4 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 bg-white hover:bg-slate-50 transition-colors">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="Eye" />
                                    View File
                                </a>

                                <!-- Status Update Form -->
                                @if($item['document'])
                                    <form action="{{ route('admin.carrier.user_documents.update-status', [$carrier->slug, $item['document']->id]) }}" 
                                          method="POST" class="space-y-2">
                                        @csrf
                                        @method('PUT')
                                        
                                        <select name="status" 
                                                class="block w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-primary focus:border-primary">
                                            <option value="0" {{ $item['document']->status == 0 ? 'selected' : '' }}>Pending</option>
                                            <option value="3" {{ $item['document']->status == 3 ? 'selected' : '' }}>In Process</option>
                                            <option value="1" {{ $item['document']->status == 1 ? 'selected' : '' }}>Approved</option>
                                            <option value="2" {{ $item['document']->status == 2 ? 'selected' : '' }}>Rejected</option>
                                        </select>

                                        <textarea name="notes" 
                                                  placeholder="Add notes (optional)"
                                                  rows="2"
                                                  class="block w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-primary focus:border-primary">{{ $item['document']->notes }}</textarea>

                                        <button type="submit" 
                                                class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-primary hover:bg-primary/90 transition-colors">
                                            <x-base.lucide class="w-4 h-4 mr-2" icon="Save" />
                                            Update Status
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @else
                            <form action="{{ route('admin.carrier.user_documents.upload', [$carrier->slug, $item['type']->id]) }}" 
                                  method="POST" enctype="multipart/form-data" class="space-y-3">
                                @csrf
                                <div class="relative">
                                    <input type="file" name="document" 
                                           class="block w-full text-sm text-slate-500
                                                  file:mr-4 file:py-2 file:px-4
                                                  file:rounded-lg file:border-0
                                                  file:text-sm file:font-semibold
                                                  file:bg-primary file:text-white
                                                  hover:file:bg-primary/90
                                                  cursor-pointer"
                                           required>
                                </div>
                                <button type="submit" 
                                        class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-primary hover:bg-primary/90 transition-colors">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="Upload" />
                                    Upload Document
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
