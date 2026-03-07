<div class="box box--stacked flex flex-col p-5">
    <div class="mb-6 border-b border-dashed border-slate-300/70 pb-5 text-[0.94rem] font-medium">
        Profile Information
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-5 flex items-center">
            <x-base.lucide class="mr-2 h-4 w-4" icon="CheckCircle" />
            {{ session('success') }}
        </div>
    @endif

    <!-- Profile Photo -->
    <div class="mb-6">
        <div class="flex items-start gap-5">
            <div class="relative">
                @if(auth()->user()->profile_photo_url && auth()->user()->profile_photo_url !== asset('build/default_profile.png'))
                    <img src="{{ auth()->user()->profile_photo_url }}"
                         alt="{{ auth()->user()->name }}"
                         class="h-24 w-24 rounded-full object-cover border-4 border-slate-200">
                @else
                    <div class="flex h-24 w-24 items-center justify-center rounded-full bg-slate-200 border-4 border-slate-300">
                        <x-base.lucide class="h-12 w-12 text-slate-500" icon="User" />
                    </div>
                @endif
            </div>
            
            <div class="flex-1">
                <div class="text-base font-medium mb-2">Profile Photo</div>
                <div class="text-slate-500 text-sm mb-3">
                    Upload a new profile photo. JPG, PNG or GIF. Max size 2MB.
                </div>
                
                <form action="{{ route('admin.settings.update-photo') }}" method="POST" enctype="multipart/form-data" class="flex gap-2">
                    @csrf
                    <input type="file" name="photo" accept="image/*" class="form-control w-auto" required>
                    <x-base.button type="submit" variant="primary" size="sm">
                        Upload
                    </x-base.button>
                    @if(auth()->user()->profile_photo_url && auth()->user()->profile_photo_url !== asset('build/default_profile.png'))
                        <x-base.button type="button" variant="outline-danger" size="sm"
                                     onclick="if(confirm('Are you sure you want to delete your profile photo?')) { document.getElementById('delete-photo-form').submit(); }">
                            Delete
                        </x-base.button>
                    @endif
                </form>
                
                @error('photo')
                    <div class="mt-2 text-sm text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <!-- Delete Photo Form (hidden) -->
    <form id="delete-photo-form" action="{{ route('admin.settings.delete-photo') }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    <!-- Profile Form -->
    <form action="{{ route('admin.settings.update-profile') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mt-5 flex flex-col gap-5">
            <!-- Name -->
            <div>
                <x-base.form-label for="name">
                    Full Name
                </x-base.form-label>
                <x-base.form-input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name', $user->name) }}"
                    placeholder="Enter your full name"
                    required
                />
                @error('name')
                    <div class="mt-2 text-sm text-danger">{{ $message }}</div>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <x-base.form-label for="email">
                    Email Address
                </x-base.form-label>
                <x-base.form-input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email', $user->email) }}"
                    placeholder="Enter your email"
                    required
                />
                @error('email')
                    <div class="mt-2 text-sm text-danger">{{ $message }}</div>
                @enderror
            </div>

            <!-- Phone -->
            <div>
                <x-base.form-label for="phone">
                    Phone
                </x-base.form-label>
                <x-base.form-input
                    id="phone"
                    name="phone"
                    type="text"
                    value="{{ old('phone', $driverDetail->phone ?? '') }}"
                    placeholder="Enter your phone"
                />
                @error('phone')
                    <div class="mt-2 text-sm text-danger">{{ $message }}</div>
                @enderror
            </div>

            <!-- Date of Birth -->
            <div>
                <x-base.form-label for="date_of_birth">
                    Date of Birth
                </x-base.form-label>
                <x-base.litepicker
                    id="date_of_birth"
                    name="date_of_birth"
                    value="{{ old('date_of_birth', isset($driverDetail->date_of_birth) ? \Carbon\Carbon::parse($driverDetail->date_of_birth)->format('m/d/Y') : '') }}"
                    data-format="MM-DD-YYYY"
                    placeholder="MM-DD-YYYY"
                />
                @error('date_of_birth')
                    <div class="mt-2 text-sm text-danger">{{ $message }}</div>
                @enderror
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
