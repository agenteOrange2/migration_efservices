<div class="box box--stacked flex flex-col p-5">
    <div class="mb-6 border-b border-dashed border-slate-300/70 pb-5 text-[0.94rem] font-medium">
        Security Settings
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-5 flex items-center">
            <x-base.lucide class="mr-2 h-4 w-4" icon="CheckCircle" />
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mb-5">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Change Password -->
    <div class="mb-8">
        <div class="text-base font-medium mb-2">Change Password</div>
        <div class="text-slate-500 text-sm mb-5">
            Update your password to keep your account secure. Use a strong password with at least 8 characters.
        </div>

        <form action="{{ route('admin.settings.update-password') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="flex flex-col gap-5">
                <!-- Current Password -->
                <div>
                    <x-base.form-label for="current_password">
                        Current Password
                    </x-base.form-label>
                    <x-base.form-input
                        id="current_password"
                        name="current_password"
                        type="password"
                        placeholder="Enter your current password"
                        required
                    />
                    @error('current_password')
                        <div class="mt-2 text-sm text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <!-- New Password -->
                <div>
                    <x-base.form-label for="password">
                        New Password
                    </x-base.form-label>
                    <x-base.form-input
                        id="password"
                        name="password"
                        type="password"
                        placeholder="Enter your new password"
                        required
                    />
                    <div class="mt-1.5 text-xs text-slate-500">
                        Password must be at least 8 characters long
                    </div>
                    @error('password')
                        <div class="mt-2 text-sm text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div>
                    <x-base.form-label for="password_confirmation">
                        Confirm New Password
                    </x-base.form-label>
                    <x-base.form-input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        placeholder="Confirm your new password"
                        required
                    />
                </div>

                <!-- Submit Button -->
                <div class="mt-3 flex gap-3">
                    <x-base.button type="submit" variant="primary">
                        <x-base.lucide class="mr-2 h-4 w-4" icon="Lock" />
                        Update Password
                    </x-base.button>
                    <x-base.button type="button" variant="outline-secondary" onclick="this.form.reset()">
                        Cancel
                    </x-base.button>
                </div>
            </div>
        </form>
    </div>

    <!-- Security Information -->
    <div class="mt-8 border-t border-dashed border-slate-300/70 pt-8">
        <div class="text-base font-medium mb-4">Security Information</div>
        
        <div class="flex flex-col gap-4">
            <!-- Last Password Change -->
            <div class="flex items-start gap-3 rounded-lg border border-slate-200 p-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                    <x-base.lucide class="h-5 w-5 text-primary" icon="Clock" />
                </div>
                <div class="flex-1">
                    <div class="font-medium">Last Password Change</div>
                    <div class="mt-1 text-sm text-slate-500">
                        {{ $user->updated_at->diffForHumans() }}
                    </div>
                </div>
            </div>

            <!-- Account Created -->
            <div class="flex items-start gap-3 rounded-lg border border-slate-200 p-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-success/10">
                    <x-base.lucide class="h-5 w-5 text-success" icon="CheckCircle" />
                </div>
                <div class="flex-1">
                    <div class="font-medium">Account Created</div>
                    <div class="mt-1 text-sm text-slate-500">
                        {{ $user->created_at->format('F d, Y') }}
                    </div>
                </div>
            </div>

            <!-- Security Tips -->
            <div class="flex items-start gap-3 rounded-lg border border-warning/30 bg-warning/5 p-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-warning/10">
                    <x-base.lucide class="h-5 w-5 text-warning" icon="Shield" />
                </div>
                <div class="flex-1">
                    <div class="font-medium">Security Tips</div>
                    <ul class="mt-2 list-disc list-inside text-sm text-slate-600 space-y-1">
                        <li>Use a unique password for this account</li>
                        <li>Change your password regularly</li>
                        <li>Never share your password with anyone</li>
                        <li>Use a password manager for better security</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
