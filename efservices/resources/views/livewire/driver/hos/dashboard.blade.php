<div wire:poll.{{ $this->pollingInterval }}ms="loadDashboardData">
    @if(!$driver)
        <div class="box box--stacked p-8">
            <div class="text-center">
                <x-base.lucide class="w-16 h-16 mx-auto text-danger/50 mb-4" icon="AlertCircle" />
                <h3 class="text-xl font-semibold text-slate-800 mb-2">Driver Profile Not Found</h3>
                <p class="text-slate-500">Please contact your carrier for assistance.</p>
            </div>
        </div>
    @else
        <!-- Server Time Display (for debugging timezone issues) -->
        <div class="flex justify-end mb-2">
            <span class="text-xs text-slate-400">
                Server Time: {{ $serverTime }} ({{ $serverTimezone }})
            </span>
        </div>

        <!-- Auto-Stop Warning Banner -->
        @if($autoStopInfo && $autoStopInfo['is_critical'])
            <div class="box box--stacked p-4 mb-6 border-2 border-danger bg-danger/10 animate-pulse">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-danger rounded-xl">
                        <x-base.lucide class="w-6 h-6 text-white" icon="AlertTriangle" />
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-danger">Auto-Stop Warning</h3>
                        <p class="text-slate-700">
                            Your trip will be automatically stopped in 
                            <span class="font-bold text-danger">{{ $autoStopInfo['minutes_remaining'] }} minutes</span>
                            due to {{ $autoStopInfo['limit_type'] === 'driving' ? 'driving time limit (12h)' : 'duty period limit (14h)' }}.
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold text-danger">{{ $autoStopInfo['minutes_remaining'] }}m</div>
                        <div class="text-xs text-slate-500">remaining</div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Active Trip Info -->
        @if($activeTrip)
            <div class="box box--stacked p-4 mb-6 bg-primary/5 border border-primary/20">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="Truck" />
                        <div>
                            <span class="font-semibold text-slate-800">Active Trip:</span>
                            <span class="text-slate-600">{{ $activeTrip->trip_number }}</span>
                        </div>
                    </div>
                    <a href="{{ route('driver.trips.show', $activeTrip) }}" class="text-primary hover:text-primary/80 text-sm font-medium">
                        View Trip →
                    </a>
                </div>
            </div>
        @endif

        <!-- Current Status Section -->
        <div class="box box--stacked p-6 mb-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl">
                        <x-base.lucide class="w-6 h-6 text-primary" icon="Activity" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800">Current Status</h2>
                        @if($currentStatus)
                            <p class="text-slate-500 text-sm">Since {{ $currentStatus['start_time'] }} ({{ $currentStatus['duration'] }})</p>
                        @else
                            <p class="text-slate-500 text-sm">Select a status to begin</p>
                        @endif
                    </div>
                </div>
                @if($currentStatus)
                    @if($currentStatus['status'] === 'on_duty_driving')
                        <span class="px-4 py-2 rounded-full text-sm font-semibold bg-success/10 text-success">
                            <x-base.lucide class="w-4 h-4 inline mr-1" icon="Car" />
                            {{ $currentStatus['status_name'] }}
                        </span>
                    @elseif($currentStatus['status'] === 'on_duty_not_driving')
                        <span class="px-4 py-2 rounded-full text-sm font-semibold bg-warning/10 text-warning">
                            <x-base.lucide class="w-4 h-4 inline mr-1" icon="Briefcase" />
                            {{ $currentStatus['status_name'] }}
                        </span>
                    @else
                        <span class="px-4 py-2 rounded-full text-sm font-semibold bg-slate-100 text-slate-600">
                            <x-base.lucide class="w-4 h-4 inline mr-1" icon="Moon" />
                            {{ $currentStatus['status_name'] }}
                        </span>
                    @endif
                @else
                    <span class="px-4 py-2 rounded-full text-sm font-semibold bg-slate-100 text-slate-400">
                        No Active Status
                    </span>
                @endif
            </div>

        </div>

        <!-- Alerts Section -->
        @if(!empty($alerts))
            <div class="mb-6 space-y-3">
                @foreach($alerts as $alert)
                    <div class="box box--stacked p-4 @if($alert['type'] === 'violation') border-l-4 border-danger bg-danger/5 @else border-l-4 border-warning bg-warning/5 @endif">
                        <div class="flex items-center gap-3">
                            @if($alert['type'] === 'violation')
                                <x-base.lucide class="w-6 h-6 text-danger flex-shrink-0" icon="AlertTriangle" />
                            @else
                                <x-base.lucide class="w-6 h-6 text-warning flex-shrink-0" icon="AlertCircle" />
                            @endif
                            <div>
                                <span class="font-semibold @if($alert['type'] === 'violation') text-danger @else text-warning @endif">
                                    {{ ucfirst($alert['type']) }}:
                                </span>
                                <span class="text-slate-700">{{ $alert['message'] }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Time Summary Section -->
        @php
            $drivingLow = ($remaining['remaining_driving_minutes'] ?? 720) < 60;
            $dutyLow = ($remaining['remaining_duty_minutes'] ?? 840) < 60;
        @endphp
        <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-6">
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-success/10 rounded-xl">
                        <x-base.lucide class="w-6 h-6 text-success" icon="Car" />
                    </div>
                    <div>
                        <div class="text-slate-500 text-sm">Driving Today</div>
                        <div class="text-2xl font-bold text-success">{{ $dailyTotals['driving_formatted'] ?? '0h 0m' }}</div>
                    </div>
                </div>
            </div>
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-warning/10 rounded-xl">
                        <x-base.lucide class="w-6 h-6 text-warning" icon="Briefcase" />
                    </div>
                    <div>
                        <div class="text-slate-500 text-sm">On Duty Today</div>
                        <div class="text-2xl font-bold text-warning">{{ $dailyTotals['on_duty_formatted'] ?? '0h 0m' }}</div>
                    </div>
                </div>
            </div>
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-4">
                    <div class="p-3 {{ $drivingLow ? 'bg-danger/10' : 'bg-primary/10' }} rounded-xl">
                        <x-base.lucide class="w-6 h-6 {{ $drivingLow ? 'text-danger' : 'text-primary' }}" icon="Timer" />
                    </div>
                    <div>
                        <div class="text-slate-500 text-sm">Driving Remaining</div>
                        <div class="text-2xl font-bold {{ $drivingLow ? 'text-danger' : 'text-primary' }}">
                            {{ $remaining['remaining_driving_formatted'] ?? '12h 0m' }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-4">
                    <div class="p-3 {{ $dutyLow ? 'bg-danger/10' : 'bg-info/10' }} rounded-xl">
                        <x-base.lucide class="w-6 h-6 {{ $dutyLow ? 'text-danger' : 'text-info' }}" icon="Clock" />
                    </div>
                    <div>
                        <div class="text-slate-500 text-sm">Duty Remaining</div>
                        <div class="text-2xl font-bold {{ $dutyLow ? 'text-danger' : 'text-info' }}">
                            {{ $remaining['remaining_duty_formatted'] ?? '14h 0m' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Entries Section -->
        <div class="box box--stacked">
            <div class="box-header flex flex-col md:flex-row items-start md:items-center justify-between p-5 border-b border-slate-200/60 gap-4">
                <h2 class="text-lg font-semibold text-slate-800">Today's Log</h2>
            </div>
            <div class="box-body p-5">
                @if(empty($todayEntries))
                    <div class="text-center py-10">
                        <x-base.lucide class="w-16 h-16 mx-auto text-slate-300 mb-4" icon="Clock" />
                        <p class="text-slate-500">No entries recorded today</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-slate-500 border-b border-slate-200/60">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Status</th>
                                    <th class="px-4 py-3 font-medium text-center">Start</th>
                                    <th class="px-4 py-3 font-medium text-center">End</th>
                                    <th class="px-4 py-3 font-medium text-center">Duration</th>
                                    <th class="px-4 py-3 font-medium">Location</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($todayEntries as $entry)
                                    <tr class="border-b border-slate-200/60 hover:bg-slate-50">
                                        <td class="px-4 py-4">
                                            @if($entry['status'] === 'on_duty_driving')
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-success/10 text-success">
                                                    <x-base.lucide class="w-3 h-3 inline mr-1" icon="Car" />
                                                    {{ $entry['status_name'] }}
                                                </span>
                                            @elseif($entry['status'] === 'on_duty_not_driving')
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-warning/10 text-warning">
                                                    <x-base.lucide class="w-3 h-3 inline mr-1" icon="Briefcase" />
                                                    {{ $entry['status_name'] }}
                                                </span>
                                            @else
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                                    <x-base.lucide class="w-3 h-3 inline mr-1" icon="Moon" />
                                                    {{ $entry['status_name'] }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-center font-medium text-slate-800">{{ $entry['start_time'] }}</td>
                                        <td class="px-4 py-4 text-center">
                                            @if($entry['end_time'] === 'Current')
                                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-primary/10 text-primary">Current</span>
                                            @else
                                                {{ $entry['end_time'] }}
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-center font-medium text-slate-800">{{ $entry['duration'] }}</td>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center gap-2 text-slate-500 text-xs max-w-xs truncate">
                                                <x-base.lucide class="w-3 h-3 flex-shrink-0" icon="MapPin" />
                                                {{ $entry['location'] ?? 'No location' }}
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

    @endif
</div>

<script>
    document.addEventListener('livewire:initialized', function() {
        // Function to get address from coordinates using Nominatim (free)
        async function getAddressFromCoordinates(lat, lon) {
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json&addressdetails=1`, {
                    headers: {
                        'User-Agent': 'EFServices-HOS/1.0'
                    }
                });
                const data = await response.json();
                return data.display_name || null;
            } catch (error) {
                console.log('Geocoding error:', error.message);
                return null;
            }
        }

        // Request GPS location on page load
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                async function(position) {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    const address = await getAddressFromCoordinates(lat, lon);
                    Livewire.dispatch('locationUpdated', { latitude: lat, longitude: lon, address: address });
                },
                function(error) {
                    console.log('GPS error:', error.message);
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }

        // Update GPS location periodically
        setInterval(function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    async function(position) {
                        const lat = position.coords.latitude;
                        const lon = position.coords.longitude;
                        const address = await getAddressFromCoordinates(lat, lon);
                        Livewire.dispatch('locationUpdated', { latitude: lat, longitude: lon, address: address });
                    },
                    function(error) {
                        console.log('GPS error:', error.message);
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            }
        }, 30000); // Every 30 seconds
    });
</script>
