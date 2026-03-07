{{-- Trainings Tab Content --}}
<div class="space-y-6">
    <h3 class="text-lg font-semibold text-slate-800 mb-4">Training Records</h3>
    
    @php
        $hasTrainings = ($driver->trainingSchools && $driver->trainingSchools->count() > 0) || 
                        ($driver->courses && $driver->courses->count() > 0) || 
                        ($driver->driverTrainings && $driver->driverTrainings->count() > 0);
    @endphp
    
    @if($hasTrainings)
        <!-- Training Schools -->
        @if($driver->trainingSchools && $driver->trainingSchools->count() > 0)
            <div>
                <h4 class="font-semibold text-slate-700 mb-3">Training Schools</h4>
                <div class="space-y-3">
                    @foreach($driver->trainingSchools as $school)
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <div class="flex items-center gap-3 mb-2">
                                <x-base.lucide class="w-5 h-5 text-success" icon="GraduationCap" />
                                <h5 class="font-semibold text-slate-800">{{ $school->school_name ?? 'Training School' }}</h5>
                                @if($school->graduated)
                                    <x-base.badge variant="success">Graduated</x-base.badge>
                                @endif
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">Location</label>
                                    <p class="text-sm text-slate-800">{{ $school->city ?? '' }}{{ $school->city && $school->state ? ', ' : '' }}{{ $school->state ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">Start Date</label>
                                    <p class="text-sm text-slate-800">{{ $school->date_start ? $school->date_start->format('M d, Y') : 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">End Date</label>
                                    <p class="text-sm text-slate-800">{{ $school->date_end ? $school->date_end->format('M d, Y') : 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Courses -->
        @if($driver->courses && $driver->courses->count() > 0)
            <div>
                <h4 class="font-semibold text-slate-700 mb-3">Courses & Certifications</h4>
                <div class="space-y-3">
                    @foreach($driver->courses as $course)
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <div class="flex items-center gap-3 mb-2">
                                <x-base.lucide class="w-5 h-5 text-info" icon="Award" />
                                <h5 class="font-semibold text-slate-800">{{ $course->organization_name ?? 'Course' }}</h5>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">Location</label>
                                    <p class="text-sm text-slate-800">{{ $course->city ?? '' }}{{ $course->city && $course->state ? ', ' : '' }}{{ $course->state ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">Certification Date</label>
                                    <p class="text-sm text-slate-800">{{ $course->certification_date ? $course->certification_date->format('M d, Y') : 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">Experience</label>
                                    <p class="text-sm text-slate-800">{{ $course->years_experience ?? 0 }} years</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Assigned Trainings -->
        @if($driver->driverTrainings && $driver->driverTrainings->count() > 0)
            <div>
                <h4 class="font-semibold text-slate-700 mb-3">Assigned Trainings</h4>
                <div class="space-y-3">
                    @foreach($driver->driverTrainings as $training)
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <div class="flex items-center gap-3 mb-2">
                                <x-base.lucide class="w-5 h-5 text-primary" icon="BookOpen" />
                                <h5 class="font-semibold text-slate-800">{{ $training->training->name ?? 'Training' }}</h5>
                                @if($training->status == 'completed')
                                    <x-base.badge variant="success">Completed</x-base.badge>
                                @elseif($training->status == 'in_progress')
                                    <x-base.badge variant="info">In Progress</x-base.badge>
                                @elseif($training->status == 'overdue')
                                    <x-base.badge variant="danger">Overdue</x-base.badge>
                                @else
                                    <x-base.badge variant="warning">{{ ucfirst($training->status ?? 'Assigned') }}</x-base.badge>
                                @endif
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">Assigned Date</label>
                                    <p class="text-sm text-slate-800">{{ $training->assigned_date ? $training->assigned_date->format('M d, Y') : 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">Due Date</label>
                                    <p class="text-sm text-slate-800">{{ $training->due_date ? $training->due_date->format('M d, Y') : 'N/A' }}</p>
                                </div>
                                @if($training->completed_date)
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">Completed Date</label>
                                    <p class="text-sm text-slate-800">{{ $training->completed_date->format('M d, Y') }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @else
        <div class="text-center py-12">
            <x-base.lucide class="w-16 h-16 text-slate-300 mx-auto mb-4" icon="GraduationCap" />
            <h4 class="text-lg font-semibold text-slate-700 mb-2">No Training Records</h4>
            <p class="text-slate-500">You don't have any training records on file.</p>
        </div>
    @endif
</div>
