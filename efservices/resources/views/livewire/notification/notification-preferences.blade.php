<div>
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Notification Preferences
        </h2>
        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <button wire:click="enableAll" class="btn btn-primary shadow-md">
                <x-base.lucide class="w-4 h-4 mr-2" icon="Bell" />
                Enable All
            </button>
        </div>
    </div>

    <div class="box p-5 mt-5 intro-y">
        <div class="mb-5">
            <p class="text-slate-500">
                Manage your notification preferences. Critical notifications (marked with 
                <span class="text-danger">*</span>) cannot be disabled for safety and compliance reasons.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th class="whitespace-nowrap">Category</th>
                        <th class="whitespace-nowrap text-center w-32">In-App</th>
                        <th class="whitespace-nowrap text-center w-32">Email</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories as $category => $label)
                        <tr wire:key="pref-{{ $category }}">
                            <td>
                                <div class="flex items-center">
                                    <x-base.lucide 
                                        class="w-5 h-5 mr-3 text-slate-500" 
                                        icon="{{ $this->getCategoryIcon($category) }}" 
                                    />
                                    <span class="font-medium">{{ $label }}</span>
                                    @if($preferences[$category]['is_critical'] ?? false)
                                        <span class="text-danger ml-1" title="Critical - Cannot be disabled">*</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">
                                @if($preferences[$category]['is_critical'] ?? false)
                                    <div class="form-check form-switch flex justify-center">
                                        <input 
                                            type="checkbox" 
                                            class="form-check-input" 
                                            checked 
                                            disabled
                                            title="Critical notifications cannot be disabled"
                                        />
                                    </div>
                                @else
                                    <div class="form-check form-switch flex justify-center">
                                        <input 
                                            type="checkbox" 
                                            class="form-check-input cursor-pointer" 
                                            wire:click="toggleInApp('{{ $category }}')"
                                            {{ ($preferences[$category]['in_app_enabled'] ?? true) ? 'checked' : '' }}
                                        />
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($preferences[$category]['is_critical'] ?? false)
                                    <div class="form-check form-switch flex justify-center">
                                        <input 
                                            type="checkbox" 
                                            class="form-check-input" 
                                            checked 
                                            disabled
                                            title="Critical notifications cannot be disabled"
                                        />
                                    </div>
                                @else
                                    <div class="form-check form-switch flex justify-center">
                                        <input 
                                            type="checkbox" 
                                            class="form-check-input cursor-pointer" 
                                            wire:click="toggleEmail('{{ $category }}')"
                                            {{ ($preferences[$category]['email_enabled'] ?? true) ? 'checked' : '' }}
                                        />
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-5 p-4 bg-slate-100 rounded-md">
            <div class="flex items-start">
                <x-base.lucide class="w-5 h-5 mr-2 text-slate-500 mt-0.5" icon="Info" />
                <div class="text-sm text-slate-600">
                    <p class="font-medium mb-1">About Notification Types:</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li><strong>In-App:</strong> Notifications shown in the notification center and bell icon.</li>
                        <li><strong>Email:</strong> Notifications sent to your registered email address.</li>
                        <li><strong>Critical (*)</strong>: Safety and compliance notifications that are always enabled.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
