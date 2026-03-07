{{-- Testing Tab Content --}}
<div class="space-y-6">
    <h3 class="text-lg font-semibold text-slate-800 mb-4">Drug & Alcohol Testing</h3>
    
    @if($driver->testings && $driver->testings->count() > 0)
        <div class="space-y-4">
            @foreach($driver->testings as $test)
                <div class="bg-slate-50/50 rounded-lg p-5 border border-slate-100">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-3">
                                <x-base.lucide class="w-5 h-5 text-warning" icon="TestTube" />
                                <h4 class="font-semibold text-slate-800">{{ $test->test_type ?? 'Test' }}</h4>
                                @if($test->test_result == 'Negative')
                                    <x-base.badge variant="success">Negative</x-base.badge>
                                @elseif($test->test_result == 'Positive')
                                    <x-base.badge variant="danger">Positive</x-base.badge>
                                @else
                                    <x-base.badge variant="secondary">{{ $test->test_result ?? 'Pending' }}</x-base.badge>
                                @endif
                                @if($test->status)
                                    <x-base.badge variant="info">{{ $test->status }}</x-base.badge>
                                @endif
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">Test Date</label>
                                    <p class="text-sm font-semibold text-slate-800">{{ $test->test_date ? $test->test_date->format('M d, Y') : 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">Location</label>
                                    <p class="text-sm font-semibold text-slate-800">{{ $test->location ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">Administered By</label>
                                    <p class="text-sm font-semibold text-slate-800">{{ $test->administered_by ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">Next Test Due</label>
                                    <p class="text-sm font-semibold text-slate-800">{{ $test->next_test_due ? $test->next_test_due->format('M d, Y') : 'N/A' }}</p>
                                </div>
                            </div>
                            @if($test->testCategories && count($test->testCategories) > 0)
                                <div class="mt-3">
                                    <label class="text-xs font-medium text-slate-500 uppercase">Test Categories</label>
                                    <div class="flex flex-wrap gap-2 mt-1">
                                        @foreach($test->testCategories as $category)
                                            <x-base.badge variant="secondary">{{ $category['label'] }}</x-base.badge>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            @if($test->getFirstMediaUrl('drug_test_pdf'))
                                <a href="{{ $test->getFirstMediaUrl('drug_test_pdf') }}" target="_blank" class="text-sm text-primary hover:underline flex items-center gap-1">
                                    <x-base.lucide class="w-4 h-4" icon="Download" /> PDF
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <x-base.lucide class="w-16 h-16 text-slate-300 mx-auto mb-4" icon="TestTube" />
            <h4 class="text-lg font-semibold text-slate-700 mb-2">No Test Records</h4>
            <p class="text-slate-500">You don't have any drug or alcohol test records on file.</p>
        </div>
    @endif
</div>
