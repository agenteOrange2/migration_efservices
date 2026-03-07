<div class="box box--stacked flex flex-col p-5">
    <div class="mb-6 border-b border-dashed border-slate-300/70 pb-5 text-[0.94rem] font-medium">
        Email Settings
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-5 flex items-center">
            <x-base.lucide class="mr-2 h-4 w-4" icon="CheckCircle" />
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.settings.update-email') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="flex flex-col gap-5">
            <!-- Primary Email -->
            <div>
                <x-base.form-label for="email">
                    Primary Email Address
                </x-base.form-label>
                <x-base.form-input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email', $user->email) }}"
                    placeholder="Enter your email"
                    required
                />
                <div class="mt-1.5 text-xs text-slate-500">
                    This email will be used for account notifications and communications.
                </div>
                @error('email')
                    <div class="mt-2 text-sm text-danger">{{ $message }}</div>
                @enderror
            </div>

            <!-- Email Preferences -->
            <div class="mt-5">
                <div class="text-base font-medium mb-4">Email Preferences</div>
                
                <div class="flex flex-col gap-3">
                    <!-- Notification Emails -->
                    <div class="flex items-center justify-between rounded-lg border border-slate-200 p-4">
                        <div class="flex-1">
                            <div class="font-medium">Notification Emails</div>
                            <div class="mt-1 text-sm text-slate-500">
                                Receive emails about your account activity and important updates
                            </div>
                        </div>
                        <x-base.form-switch class="ml-4">
                            <x-base.form-switch.input
                                type="checkbox"
                                name="notification_email"
                                value="1"
                                checked
                            />
                        </x-base.form-switch>
                    </div>

                    <!-- Marketing Emails -->
                    <div class="flex items-center justify-between rounded-lg border border-slate-200 p-4">
                        <div class="flex-1">
                            <div class="font-medium">Marketing Emails</div>
                            <div class="mt-1 text-sm text-slate-500">
                                Receive emails about new features, tips, and product updates
                            </div>
                        </div>
                        <x-base.form-switch class="ml-4">
                            <x-base.form-switch.input
                                type="checkbox"
                                name="marketing_email"
                                value="1"
                            />
                        </x-base.form-switch>
                    </div>

                    <!-- System Emails -->
                    <div class="flex items-center justify-between rounded-lg border border-slate-200 p-4 bg-slate-50">
                        <div class="flex-1">
                            <div class="font-medium">System Emails</div>
                            <div class="mt-1 text-sm text-slate-500">
                                Critical system notifications (cannot be disabled)
                            </div>
                        </div>
                        <x-base.form-switch class="ml-4">
                            <x-base.form-switch.input
                                type="checkbox"
                                checked
                                disabled
                            />
                        </x-base.form-switch>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-5 flex gap-3">
                <x-base.button type="submit" variant="primary">
                    <x-base.lucide class="mr-2 h-4 w-4" icon="Save" />
                    Save Changes
                </x-base.button>
                <x-base.button type="button" variant="outline-secondary" onclick="window.location.reload()">
                    Cancel
                </x-base.button>
            </div>
        </div>
    </form>
</div>
