{{-- Driver-Specific HOS Document Generation Modals --}}

{{-- Daily Log Modal --}}
<x-base.dialog id="driver-daily-log-modal" size="md">
    <x-base.dialog.panel>
        <div class="p-5">
            <div class="text-center mb-5">
                <x-base.lucide class="mx-auto mt-3 h-16 w-16 text-success" icon="Calendar" />
                <div class="mt-5 text-2xl font-semibold text-slate-800">Generate Daily Log</div>
                <div class="mt-2 text-slate-500">
                    Generate a daily HOS log for {{ $driver->user->name ?? 'this driver' }}
                </div>
            </div>

            <form action="{{ route('admin.hos.documents.daily-log') }}" method="POST">
                @csrf
                <input type="hidden" name="driver_id" value="{{ $driver->id }}">
                
                <div class="mb-5">
                    <x-base.form-label for="driver_daily_date">Select Date</x-base.form-label>
                    <x-base.form-input 
                        type="date" 
                        id="driver_daily_date" 
                        name="date" 
                        value="{{ now()->format('Y-m-d') }}"
                        max="{{ now()->format('Y-m-d') }}"
                        required />
                </div>
                <div class="flex gap-3">
                    <x-base.button data-tw-dismiss="modal" type="button" variant="outline-secondary" class="flex-1">
                        Cancel
                    </x-base.button>
                    <x-base.button type="submit" variant="success" class="flex-1 gap-2">
                        <x-base.lucide class="w-4 h-4" icon="FileText" />
                        Generate
                    </x-base.button>
                </div>
            </form>
        </div>
    </x-base.dialog.panel>
</x-base.dialog>

{{-- Monthly Summary Modal --}}
<x-base.dialog id="driver-monthly-summary-modal" size="md">
    <x-base.dialog.panel>
        <div class="p-5">
            <div class="text-center mb-5">
                <x-base.lucide class="mx-auto mt-3 h-16 w-16 text-info" icon="BarChart" />
                <div class="mt-5 text-2xl font-semibold text-slate-800">Generate Monthly Summary</div>
                <div class="mt-2 text-slate-500">
                    Generate a monthly HOS summary for {{ $driver->user->name ?? 'this driver' }}
                </div>
            </div>

            <form action="{{ route('admin.hos.documents.monthly-summary') }}" method="POST">
                @csrf
                <input type="hidden" name="driver_id" value="{{ $driver->id }}">
                
                <div class="grid grid-cols-2 gap-3 mb-5">
                    <div>
                        <x-base.form-label for="driver_month">Month</x-base.form-label>
                        <x-base.form-select id="driver_month" name="month" required>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $m == now()->month ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                </option>
                            @endfor
                        </x-base.form-select>
                    </div>
                    <div>
                        <x-base.form-label for="driver_year">Year</x-base.form-label>
                        <x-base.form-select id="driver_year" name="year" required>
                            @for($y = now()->year; $y >= 2020; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </x-base.form-select>
                    </div>
                </div>
                <div class="flex gap-3">
                    <x-base.button data-tw-dismiss="modal" type="button" variant="outline-secondary" class="flex-1">
                        Cancel
                    </x-base.button>
                    <x-base.button type="submit" variant="info" class="flex-1 gap-2">
                        <x-base.lucide class="w-4 h-4" icon="FileText" />
                        Generate
                    </x-base.button>
                </div>
            </form>
        </div>
    </x-base.dialog.panel>
</x-base.dialog>

{{-- FMCSA Monthly Modal --}}
<x-base.dialog id="driver-fmcsa-monthly-modal" size="md">
    <x-base.dialog.panel>
        <div class="p-5">
            <div class="text-center mb-5">
                <x-base.lucide class="mx-auto mt-3 h-16 w-16 text-amber-500" icon="FileText" />
                <div class="mt-5 text-2xl font-semibold text-slate-800">FMCSA Monthly Document</div>
                <div class="mt-2 text-slate-500">
                    FMCSA format for {{ $driver->user->name ?? 'this driver' }} (100/150 air-mile radius)
                </div>
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4 text-sm text-amber-800">
                <strong>Includes:</strong> Date, Start Time, End Time, Total Hours, Driving Hours, Truck Number, Headquarters
            </div>

            <form action="{{ route('admin.hos.documents.document-monthly') }}" method="POST">
                @csrf
                <input type="hidden" name="driver_id" value="{{ $driver->id }}">
                
                <div class="grid grid-cols-2 gap-3 mb-5">
                    <div>
                        <x-base.form-label for="fmcsa_month">Month</x-base.form-label>
                        <x-base.form-select id="fmcsa_month" name="month" required>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $m == now()->month ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                </option>
                            @endfor
                        </x-base.form-select>
                    </div>
                    <div>
                        <x-base.form-label for="fmcsa_year">Year</x-base.form-label>
                        <x-base.form-select id="fmcsa_year" name="year" required>
                            @for($y = now()->year; $y >= 2020; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </x-base.form-select>
                    </div>
                </div>
                <div class="flex gap-3">
                    <x-base.button data-tw-dismiss="modal" type="button" variant="outline-secondary" class="flex-1">
                        Cancel
                    </x-base.button>
                    <x-base.button type="submit" variant="warning" class="flex-1 gap-2">
                        <x-base.lucide class="w-4 h-4" icon="FileText" />
                        Generate
                    </x-base.button>
                </div>
            </form>
        </div>
    </x-base.dialog.panel>
</x-base.dialog>
