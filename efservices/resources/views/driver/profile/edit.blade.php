@extends('../themes/' . $activeTheme)
@section('title', 'Edit Profile - EF Services')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'My Profile', 'url' => route('driver.profile')],
        ['label' => 'Edit Profile', 'active' => true],
    ];
@endphp

@section('subcontent')

<!-- Breadcrumbs -->
<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

<!-- Page Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-slate-800">Edit Profile</h2>
        <p class="text-slate-500 mt-1">Update your personal information and profile photo</p>
    </div>
    <x-base.button as="a" href="{{ route('driver.profile') }}" variant="outline-secondary" class="gap-2">
        <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
        Back to Profile
    </x-base.button>
</div>

<!-- Success/Error Messages -->
@if(session('success'))
<div class="box box--stacked p-4 mb-6 bg-success/10 border border-success/20 rounded-lg">
    <div class="flex items-start gap-3">
        <x-base.lucide class="w-5 h-5 text-success mt-0.5" icon="CheckCircle" />
        <div class="flex-1">
            <h4 class="font-semibold text-success">Success!</h4>
            <p class="text-sm text-success/80 mt-1">{{ session('success') }}</p>
        </div>
    </div>
</div>
@endif

@if(session('error'))
<div class="box box--stacked p-4 mb-6 bg-danger/10 border border-danger/20 rounded-lg">
    <div class="flex items-start gap-3">
        <x-base.lucide class="w-5 h-5 text-danger mt-0.5" icon="XCircle" />
        <div class="flex-1">
            <h4 class="font-semibold text-danger">Error!</h4>
            <p class="text-sm text-danger/80 mt-1">{{ session('error') }}</p>
        </div>
    </div>
</div>
@endif

<!-- Profile Photo Section -->
<div class="box box--stacked p-6 mb-6">
    <h3 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
        <x-base.lucide class="w-5 h-5 text-primary" icon="Camera" />
        Profile Photo
    </h3>
    
    <div class="flex flex-col md:flex-row items-center gap-6">
        <div class="relative">
            <img id="profile-preview" 
                 class="w-32 h-32 rounded-full object-cover border-4 border-slate-200 shadow-lg"
                 src="{{ $driver->profile_photo_url }}"
                 alt="{{ auth()->user()->name }}">
            <div id="upload-spinner" class="hidden absolute inset-0 flex items-center justify-center bg-black/50 rounded-full">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-white"></div>
            </div>
        </div>
        
        <div class="flex-1">
            <p class="text-sm text-slate-600 mb-4">
                Upload a new profile photo. Recommended size: 400x400px. Max file size: 2MB.
                Supported formats: JPEG, PNG, WebP.
            </p>
            <div class="flex flex-wrap gap-3">
                <label for="photo-upload" class="cursor-pointer">
                    <x-base.button type="button" variant="primary" class="gap-2" onclick="document.getElementById('photo-upload').click();">
                        <x-base.lucide class="w-4 h-4" icon="Upload" />
                        Upload New Photo
                    </x-base.button>
                </label>
                <input type="file" id="photo-upload" accept="image/jpeg,image/jpg,image/png,image/webp" class="hidden">
                
                @if($driver->profile_photo_url !== asset('build/default_profile.png'))
                <x-base.button type="button" variant="outline-danger" class="gap-2" id="delete-photo-btn">
                    <x-base.lucide class="w-4 h-4" icon="Trash2" />
                    Remove Photo
                </x-base.button>
                @endif
            </div>
            <div id="photo-error" class="hidden mt-2 text-sm text-danger"></div>
            <div id="photo-success" class="hidden mt-2 text-sm text-success"></div>
        </div>
    </div>
</div>

<!-- Personal Information Form -->
<div class="box box--stacked p-6 mb-6">
    <h3 class="text-lg font-semibold text-slate-800 mb-6 flex items-center gap-2">
        <x-base.lucide class="w-5 h-5 text-primary" icon="User" />
        Personal Information
    </h3>
    
    <form action="{{ route('driver.profile.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- First Name -->
            <div>
                <x-base.form-label for="name">First Name <span class="text-danger">*</span></x-base.form-label>
                <x-base.form-input 
                    id="name" 
                    name="name" 
                    type="text" 
                    value="{{ old('name', $user->name) }}" 
                    required
                    placeholder="Enter your first name"
                />
                @error('name')
                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                @enderror
            </div>
            
            <!-- Middle Name -->
            <div>
                <x-base.form-label for="middle_name">Middle Name</x-base.form-label>
                <x-base.form-input 
                    id="middle_name" 
                    name="middle_name" 
                    type="text" 
                    value="{{ old('middle_name', $driver->middle_name) }}"
                    placeholder="Enter your middle name"
                />
                @error('middle_name')
                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                @enderror
            </div>
            
            <!-- Last Name -->
            <div>
                <x-base.form-label for="last_name">Last Name <span class="text-danger">*</span></x-base.form-label>
                <x-base.form-input 
                    id="last_name" 
                    name="last_name" 
                    type="text" 
                    value="{{ old('last_name', $driver->last_name) }}" 
                    required
                    placeholder="Enter your last name"
                />
                @error('last_name')
                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                @enderror
            </div>
            
            <!-- Email -->
            <div>
                <x-base.form-label for="email">Email <span class="text-danger">*</span></x-base.form-label>
                <x-base.form-input 
                    id="email" 
                    name="email" 
                    type="email" 
                    value="{{ old('email', $user->email) }}" 
                    required
                    placeholder="Enter your email"
                />
                @error('email')
                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                @enderror
            </div>
            
            <!-- Phone -->
            <div>
                <x-base.form-label for="phone">Phone <span class="text-danger">*</span></x-base.form-label>
                <x-base.form-input 
                    id="phone" 
                    name="phone" 
                    type="tel" 
                    value="{{ old('phone', $driver->phone) }}" 
                    required
                    placeholder="Enter your phone number"
                />
                @error('phone')
                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                @enderror
            </div>
            
            <!-- Date of Birth -->
            <div>
                <x-base.form-label for="date_of_birth">Date of Birth <span class="text-danger">*</span></x-base.form-label>
                <x-base.form-input 
                    id="date_of_birth" 
                    name="date_of_birth" 
                    type="date" 
                    value="{{ old('date_of_birth', $driver->date_of_birth?->format('Y-m-d')) }}" 
                    required
                />
                @error('date_of_birth')
                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
        
        <!-- Carrier Information (Read-only) -->
        <div class="p-4 bg-slate-50 rounded-lg border border-slate-200">
            <h4 class="text-sm font-semibold text-slate-700 mb-2">Carrier Information</h4>
            <div class="flex items-center gap-2 text-sm text-slate-600">
                <x-base.lucide class="w-4 h-4 text-slate-400" icon="Building2" />
                <span class="font-medium">{{ $driver->carrier->name ?? 'No carrier assigned' }}</span>
            </div>
            <p class="text-xs text-slate-500 mt-2">Contact your carrier administrator to update carrier information.</p>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
            <x-base.button as="a" href="{{ route('driver.profile') }}" variant="outline-secondary">
                Cancel
            </x-base.button>
            <x-base.button type="submit" variant="primary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="Save" />
                Save Changes
            </x-base.button>
        </div>
    </form>
</div>

<!-- Change Password Section -->
<div class="box box--stacked p-6">
    <h3 class="text-lg font-semibold text-slate-800 mb-6 flex items-center gap-2">
        <x-base.lucide class="w-5 h-5 text-primary" icon="Lock" />
        Change Password
    </h3>
    
    <form action="{{ route('driver.profile.update-password') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Current Password -->
            <div class="md:col-span-2">
                <x-base.form-label for="current_password">Current Password <span class="text-danger">*</span></x-base.form-label>
                <x-base.form-input 
                    id="current_password" 
                    name="current_password" 
                    type="password" 
                    required
                    placeholder="Enter your current password"
                />
                @error('current_password')
                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                @enderror
            </div>
            
            <!-- New Password -->
            <div>
                <x-base.form-label for="password">New Password <span class="text-danger">*</span></x-base.form-label>
                <x-base.form-input 
                    id="password" 
                    name="password" 
                    type="password" 
                    required
                    placeholder="Enter new password"
                />
                @error('password')
                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                @enderror
                <p class="text-xs text-slate-500 mt-1">Minimum 8 characters</p>
            </div>
            
            <!-- Confirm New Password -->
            <div>
                <x-base.form-label for="password_confirmation">Confirm New Password <span class="text-danger">*</span></x-base.form-label>
                <x-base.form-input 
                    id="password_confirmation" 
                    name="password_confirmation" 
                    type="password" 
                    required
                    placeholder="Confirm new password"
                />
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex justify-end pt-4 border-t border-slate-200">
            <x-base.button type="submit" variant="primary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="Key" />
                Update Password
            </x-base.button>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const photoUpload = document.getElementById('photo-upload');
    const profilePreview = document.getElementById('profile-preview');
    const uploadSpinner = document.getElementById('upload-spinner');
    const photoError = document.getElementById('photo-error');
    const photoSuccess = document.getElementById('photo-success');
    const deletePhotoBtn = document.getElementById('delete-photo-btn');
    
    // Handle photo upload
    if (photoUpload) {
        photoUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (!file) return;
            
            // Validate file type
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                showError('Please select a valid image file (JPEG, PNG, or WebP)');
                return;
            }
            
            // Validate file size (2MB)
            if (file.size > 2 * 1024 * 1024) {
                showError('File size must be less than 2MB');
                return;
            }
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                profilePreview.src = e.target.result;
            };
            reader.readAsDataURL(file);
            
            // Upload photo
            uploadPhoto(file);
        });
    }
    
    // Handle photo deletion
    if (deletePhotoBtn) {
        deletePhotoBtn.addEventListener('click', function() {
            if (!confirm('Are you sure you want to remove your profile photo?')) {
                return;
            }
            
            deletePhoto();
        });
    }
    
    function uploadPhoto(file) {
        const formData = new FormData();
        formData.append('profile_photo', file);
        formData.append('_token', '{{ csrf_token() }}');
        
        uploadSpinner.classList.remove('hidden');
        hideMessages();
        
        fetch('{{ route("driver.profile.update-photo") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            uploadSpinner.classList.add('hidden');
            
            if (data.success) {
                showSuccess('Profile photo updated successfully!');
                profilePreview.src = data.photo_url + '?t=' + new Date().getTime();
                
                // Update photo in menu if exists
                const menuPhoto = document.querySelector('.menu img[alt="{{ auth()->user()->name }}"]');
                if (menuPhoto) {
                    menuPhoto.src = data.photo_url + '?t=' + new Date().getTime();
                }
                
                // Reload page after 1 second to show delete button
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showError(data.message || 'An error occurred while uploading the photo');
            }
        })
        .catch(error => {
            uploadSpinner.classList.add('hidden');
            showError('An error occurred while uploading the photo');
            console.error('Upload error:', error);
        });
    }
    
    function deletePhoto() {
        uploadSpinner.classList.remove('hidden');
        hideMessages();
        
        fetch('{{ route("driver.profile.delete-photo") }}', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            uploadSpinner.classList.add('hidden');
            
            if (data.success) {
                showSuccess('Profile photo removed successfully!');
                profilePreview.src = data.photo_url;
                
                // Update photo in menu if exists
                const menuPhoto = document.querySelector('.menu img[alt="{{ auth()->user()->name }}"]');
                if (menuPhoto) {
                    menuPhoto.parentElement.innerHTML = `
                        <div class="flex h-full w-full items-center justify-center bg-slate-200">
                            <svg class="h-5 w-5 text-slate-500" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                    `;
                }
                
                // Reload page after 1 second to hide delete button
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showError(data.message || 'An error occurred while deleting the photo');
            }
        })
        .catch(error => {
            uploadSpinner.classList.add('hidden');
            showError('An error occurred while deleting the photo');
            console.error('Delete error:', error);
        });
    }
    
    function showError(message) {
        photoError.textContent = message;
        photoError.classList.remove('hidden');
        photoSuccess.classList.add('hidden');
    }
    
    function showSuccess(message) {
        photoSuccess.textContent = message;
        photoSuccess.classList.remove('hidden');
        photoError.classList.add('hidden');
    }
    
    function hideMessages() {
        photoError.classList.add('hidden');
        photoSuccess.classList.add('hidden');
    }
});
</script>
@endpush

