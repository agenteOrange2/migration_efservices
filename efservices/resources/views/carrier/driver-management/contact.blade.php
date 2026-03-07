@extends('../themes/' . $activeTheme)
@section('title', 'Contact Driver')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
        ['label' => 'Driver & Vehicle Management', 'url' => route('carrier.driver-vehicle-management.index')],
        ['label' => 'Driver Details', 'url' => route('carrier.driver-vehicle-management.show', $driver->id)],
        ['label' => 'Contact Driver', 'active' => true],
    ];
@endphp

@section('subcontent')
<div class="grid grid-cols-12 gap-x-6 gap-y-10">
    <div class="col-span-12">
        <!-- Header Section -->
        <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
            <div class="text-base font-medium group-[.mode--light]:text-white">
                Contact Driver
            </div>
            <div class="flex gap-x-2 md:ml-auto">
                <x-base.button
                    href="{{ route('carrier.driver-vehicle-management.show', $driver->id) }}"
                    variant="outline-secondary">
                    <x-base.lucide class="mr-2 h-4 w-4" icon="ArrowLeft" />
                    Back to Driver Details
                </x-base.button>
            </div>
        </div>

        <!-- Error Messages -->
        @if(session('error'))
            <div class="alert-danger alert mt-3">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert-danger alert mt-3">
                <ul class="mb-0 list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Driver Info Card -->
        <div class="mt-3.5">
            <div class="box box--stacked flex flex-col p-5">
                <div class="flex items-center gap-4">
                    <div class="h-16 w-16 overflow-hidden rounded-lg border-2 border-slate-200">
                        <img src="{{ $driver->profile_photo_url }}" 
                             alt="{{ $driver->full_name }}"
                             class="h-full w-full object-cover">
                    </div>
                    <div>
                        <h3 class="text-lg font-medium">
                            {{ $driver->user->name ?? 'N/A' }} {{ $driver->last_name ?? '' }}
                        </h3>
                        <div class="text-sm text-slate-500">
                            {{ $driver->user->email ?? 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="mt-5">
            <div class="box box--stacked flex flex-col">
                <div class="flex items-center border-b border-slate-200/60 p-5">
                    <h3 class="text-lg font-medium">Send Message</h3>
                </div>

                <form action="{{ route('carrier.driver-vehicle-management.send-contact', $driver->id) }}" 
                      method="POST" 
                      class="p-5">
                    @csrf

                    <!-- Subject Field -->
                    <div class="mb-5">
                        <x-base.form-label for="subject">
                            Subject <span class="text-danger">*</span>
                        </x-base.form-label>
                        <x-base.form-input
                            id="subject"
                            name="subject"
                            type="text"
                            placeholder="Enter message subject"
                            value="{{ old('subject') }}"
                            required
                            class="@error('subject') border-danger @enderror"
                        />
                        @error('subject')
                            <div class="mt-2 text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Priority Field -->
                    <div class="mb-5">
                        <x-base.form-label for="priority">
                            Priority <span class="text-danger">*</span>
                        </x-base.form-label>
                        <x-base.tom-select
                            id="priority"
                            name="priority"
                            class="@error('priority') border-danger @enderror"
                            required>
                            <option value="">Select priority</option>
                            <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                            <option value="normal" {{ old('priority') == 'normal' ? 'selected' : '' }}>Normal</option>
                            <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                        </x-base.tom-select>
                        @error('priority')
                            <div class="mt-2 text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Message Field -->
                    <div class="mb-5">
                        <x-base.form-label for="message">
                            Message <span class="text-danger">*</span>
                        </x-base.form-label>
                        <x-base.form-textarea
                            id="message"
                            name="message"
                            rows="8"
                            placeholder="Enter your message to the driver"
                            required
                            class="@error('message') border-danger @enderror"
                        >{{ old('message') }}</x-base.form-textarea>
                        @error('message')
                            <div class="mt-2 text-danger">{{ $message }}</div>
                        @enderror
                        <div class="mt-2 text-xs text-slate-500">
                            Maximum 5000 characters
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-2">
                        <x-base.button
                            type="submit"
                            variant="primary">
                            <x-base.lucide class="mr-2 h-4 w-4" icon="Send" />
                            Send Message
                        </x-base.button>
                        <x-base.button
                            type="button"
                            variant="outline-secondary"
                            onclick="window.location='{{ route('carrier.driver-vehicle-management.show', $driver->id) }}'">
                            Cancel
                        </x-base.button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Information Box -->
        <div class="mt-5">
            <div class="box box--stacked flex flex-col p-5">
                <div class="flex items-start gap-3">
                    <x-base.lucide class="h-5 w-5 text-primary mt-0.5" icon="Info" />
                    <div class="flex-1">
                        <h4 class="font-medium text-slate-700">Message Information</h4>
                        <ul class="mt-2 list-disc pl-5 text-sm text-slate-600 space-y-1">
                            <li>The driver will receive this message via email at <strong>{{ $driver->user->email }}</strong></li>
                            <li>A copy of this message will be stored in the system for record keeping</li>
                            <li>Please ensure your message is professional and relevant to driver operations</li>
                            <li>High priority messages are marked as urgent in the email notification</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
