@extends('../themes/' . $activeTheme)
@section('title', 'Create Company')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Companies', 'url' => route('admin.companies.index')],
        ['label' => 'Create', 'active' => true],
    ];
@endphp

@section('subcontent')
    <div>
        <!-- Mensajes Flash -->
        @if (session()->has('success'))
            <div class="alert alert-success flex items-center mb-5">
                <x-base.lucide class="w-6 h-6 mr-2" icon="check-circle" />
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert alert-danger flex items-center mb-5">
                <x-base.lucide class="w-6 h-6 mr-2" icon="alert-circle" />
                {{ session('error') }}
            </div>
        @endif



        <!-- Professional Header -->
        <div class="box box--stacked p-8 mb-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <x-base.lucide class="w-8 h-8 text-primary" icon="Plus" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-2">Add New Company</h1>
                        <p class="text-slate-600">Add a new company to the database</p>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 w-full md:w-[300px]">
                    <x-base.button as="a" href="{{ route('admin.companies.index') }}" variant="outline-secondary"
                        class="w-full sm:w-auto">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="arrow-left" />
                        Back to Companies
                    </x-base.button>
                </div>
            </div>
        </div>

        <!-- Formulario -->
        <div class="box box--stacked mt-5 p-3">
            <div class="box-header">
                <h3 class="box-title">Company Information</h3>
            </div>
            <div class="box-body p-5">
                <form action="{{ route('admin.companies.store') }}" method="POST">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Company Name -->
                        <div>
                            <x-base.form-label for="company_name" class="flex items-center ">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="building" />
                                Company Name *
                            </x-base.form-label>
                            <x-base.form-input type="text" name="company_name" id="company_name"
                                value="{{ old('company_name') }}" placeholder="Enter company name"
                                class="{{ $errors->has('company_name') ? 'border-danger' : '' }}" required />
                            @error('company_name')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Contact Person -->
                        <div>
                            <x-base.form-label for="contact" class="flex items-center ">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="user" />
                                Contact Person
                            </x-base.form-label>
                            <x-base.form-input type="text" name="contact" id="contact" value="{{ old('contact') }}"
                                placeholder="Enter contact person name"
                                class="{{ $errors->has('contact') ? 'border-danger' : '' }}" />
                            @error('contact')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <x-base.form-label for="email" class="flex items-center ">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="mail" />
                                Email
                            </x-base.form-label>
                            <x-base.form-input type="email" name="email" id="email" value="{{ old('email') }}"
                                placeholder="Enter email address"
                                class="{{ $errors->has('email') ? 'border-danger' : '' }}" />
                            @error('email')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div>
                            <x-base.form-label for="phone" class="flex items-center ">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="phone" />
                                Phone
                            </x-base.form-label>
                            <x-base.form-input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                                placeholder="Enter phone number"
                                class="{{ $errors->has('phone') ? 'border-danger' : '' }}" />
                            @error('phone')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Address -->
                        <div class="md:col-span-2">
                            <x-base.form-label for="address" class="flex items-center ">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="map-pin" />
                                Address
                            </x-base.form-label>
                            <x-base.form-input type="text" name="address" id="address" value="{{ old('address') }}"
                                placeholder="Enter company address"
                                class="{{ $errors->has('address') ? 'border-danger' : '' }}" />
                            @error('address')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- City -->
                        <div>
                            <x-base.form-label for="city" class="flex items-center ">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="map" />
                                City
                            </x-base.form-label>
                            <x-base.form-input type="text" name="city" id="city" value="{{ old('city') }}"
                                placeholder="Enter city" class="{{ $errors->has('city') ? 'border-danger' : '' }}" />
                            @error('city')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- State -->
                        <div>
                            <x-base.form-label for="state" class="flex items-center ">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="flag" />
                                State
                            </x-base.form-label>
                            <x-base.form-input type="text" name="state" id="state" value="{{ old('state') }}"
                                placeholder="Enter state" class="{{ $errors->has('state') ? 'border-danger' : '' }}" />
                            @error('state')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- ZIP Code -->
                        <div>
                            <x-base.form-label for="zip_code" class="flex items-center ">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="hash" />
                                ZIP Code
                            </x-base.form-label>
                            <x-base.form-input type="text" name="zip_code" id="zip_code"
                                value="{{ old('zip_code') }}" placeholder="Enter ZIP code"
                                class="{{ $errors->has('zip_code') ? 'border-danger' : '' }}" />
                            @error('zip_code')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Country -->
                        <div>
                            <x-base.form-label for="country" class="flex items-center ">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="globe" />
                                Country
                            </x-base.form-label>
                            <x-base.form-input type="text" name="country" id="country"
                                value="{{ old('country', 'United States') }}" placeholder="Enter country"
                                class="{{ $errors->has('country') ? 'border-danger' : '' }}" />
                            @error('country')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex flex-col sm:flex-row gap-3 mt-8 pt-6 border-t border-slate-200/60">
                        <x-base.button type="submit" variant="primary" class="w-full sm:w-auto">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="save" />
                            Create Company
                        </x-base.button>
                        <x-base.button as="a" href="{{ route('admin.companies.index') }}"
                            variant="outline-secondary" class="w-full sm:w-auto">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="x" />
                            Cancel
                        </x-base.button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
