@extends('../themes/' . $activeTheme)
@section('title', 'Documents for ' . $carrier->name)
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Carriers', 'url' => route('admin.carrier.index')],
        ['label' => 'Documents for ' . $carrier->name, 'active' => true],
    ];
@endphp

@pushOnce('styles')
    @vite('resources/css/vendors/toastify.css')
@endPushOnce

@section('subcontent')
    <!-- Success Notification Content -->
    <div id="success-notification-content" class="hidden">
        <div class="flex items-center gap-3 p-3 rounded-lg bg-green-100 border border-green-400 text-green-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"></path>
            </svg>
            <span>File uploaded successfully!</span>
        </div>
    </div>

    <!-- Error Notification Content -->
    <div id="error-notification-content" class="hidden">
        <div class="flex items-center gap-3 p-3 rounded-lg bg-red-100 border border-red-400 text-red-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 8v4m0 4h.01M12 2a10 10 0 11-10 10A10 10 0 0112 2z"></path>
            </svg>
            <span>Error uploading the file. Please try again.</span>
        </div>
    </div>

    <h1 class="text-xl font-semibold">
        Documents for {{ $carrier->name }}</h1>

    <div class="col-span-12">
        <div class="mt-2 overflow-auto lg:overflow-visible">
            <x-base.table class="border-separate border-spacing-y-[10px]">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <ul class="flex flex-wrap text-sm font-medium text-center bg-white text-gray-500 dark:text-gray-400">
                        <!-- Tab Carrier -->
                        <li class="flex-grow">
                            <a href="{{ route('admin.carrier.edit', $carrier->slug) }}"
                                class="inline-flex items-center justify-center w-full p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300 group
                        {{ request()->routeIs('admin.carrier.edit') ? 'text-primary border-primary dark:text-primary dark:border-primary' : '' }}">

                                <svg class="w-6 h-6 me-2 text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-300 {{ request()->routeIs('admin.carrier.edit') ? 'text-primary dark:text-primary' : '' }}"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M18 20a6 6 0 0 0-12 0" />
                                    <circle cx="12" cy="10" r="4" />
                                    <circle cx="12" cy="12" r="10" />
                                </svg>
                                Profile Carrier
                            </a>
                        </li>
                        <!-- Tab Users -->
                        <li class="flex-grow">
                            <a href="{{ route('admin.carrier.user_carriers.index', $carrier->slug) }}"
                                class="inline-flex items-center justify-center w-full p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300 group
                        {{ request()->routeIs('admin.carrier.user_carriers.*') ? 'text-primary border-blue-600 dark:text-primary dark:border-primary' : '' }}">
                                <svg class="w-6 h-6 me-2 text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-300 {{ request()->routeIs('admin.carrier.user_carriers.*') ? 'text-primary border-primary dark:text-primary' : '' }}"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                                Users
                            </a>
                        </li>
                        <!-- Tab Drivers -->
                        <li class="flex-grow">

                            <a href="{{ route('admin.carrier.user_drivers.index', $carrier->slug) }}"
                                class="inline-flex items-center justify-center w-full p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300 group {{ request()->routeIs('admin.carrier.user_drivers.*') ? 'text-primary border-primary ' : '' }}">
                                <svg class="w-6 h-6 me-2 text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-300 {{ request()->routeIs('admin.carrier.user_drivers.*') ? 'text-primary dark:text-primary' : '' }}"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <rect width="16" height="16" x="4" y="4" rx="2" />
                                    <path d="M12 3v18" />
                                    <path d="M3 12h18" />
                                    <path d="m13 8-2-2-2 2" />
                                    <path d="m13 16-2 2-2-2" />
                                    <path d="m8 13-2-2 2-2" />
                                    <path d="m16 13 2-2-2-2" />
                                </svg>
                                Drivers
                            </a>
                        </li>
                        <!-- Tab Documents -->
                        {{-- Uncomment if needed --}}
                        <li class="flex-grow">
                            <a href="{{ route('admin.carrier.documents', $carrier->slug) }}"
                                class="inline-flex items-center justify-center w-full p-4 border-b-2  rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300 group
                        {{ request()->routeIs('admin.carrier.documents') ? 'text-primary border-primary dark:text-primary dark:border-primary' : '' }}">
                                <svg class="w-6 h-6 me-2 text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-300 {{ request()->routeIs('admin.carrier.documents') ? 'text-primary border-primary dark:text-primary' : '' }}"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M4 22h14a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v4" />
                                    <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                                    <path d="m3 15 2 2 4-4" />
                                </svg>
                                Documents
                            </a>
                        </li>
                    </ul>
                </div>
                <x-base.table.tbody>
                    @foreach ($documents as $document)
                        <x-base.table.tr>
                            <x-base.table.td
                                class="box rounded-l-none rounded-r-none border-x-0 shadow-[5px_3px_5px_#00000005] first:rounded-l-[0.6rem] first:border-l last:rounded-r-[0.6rem] last:border-r">
                                <div class="flex items-center">
                                    <x-base.lucide class="h-6 w-6 fill-primary/10 stroke-[0.8] text-theme-1"
                                        icon="FileText" />
                                    <div class="ml-3.5">
                                        <a class="font-medium whitespace-nowrap" href="">
                                            {{ $document->documentType->name }}
                                        </a>
                                        <div class="mt-1 text-xs whitespace-nowrap">
                                            @if ($document->documentType->requirement)
                                                <span class="text-red-500 flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                        stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                    Obligatory
                                                </span>
                                            @else
                                                <span class="text-blue-500 flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                        stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M9 12h6m2 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    Optional
                                                </span>
                                            @endif
                                        </div>

                                    </div>
                                </div>
                            </x-base.table.td>
                            <x-base.table.td
                                class="box w-60 rounded-l-none rounded-r-none border-x-0 shadow-[5px_3px_5px_#00000005] first:rounded-l-[0.6rem] first:border-l last:rounded-r-[0.6rem] last:border-r">
                                <div class="mb-1 text-xs whitespace-nowrap text-slate-500">
                                    File
                                </div>
                                <div class="flex items-center text-primary">
                                    <div class="ml-1.5 whitespace-nowrap">
                                        {{-- <div class="flex items-center text-primary"> --}}
                                        @if ($document->getFirstMediaUrl('carrier_documents'))
                                            <div class="flex items-center text-primary">
                                                <x-base.lucide class="h-3.5 w-3.5 stroke-[1.7]" icon="ExternalLink" />
                                                <a href="{{ $document->getFirstMediaUrl('carrier_documents') }}"
                                                    target="_blank" class="ml-3 text-primary underline ">
                                                    View File
                                                </a>
                                            </div>
                                        @elseif ($document->documentType->getFirstMediaUrl('default_documents'))
                                            <!-- Archivo predeterminado -->
                                            <div class="flex items-center text-primary mb-5">
                                                <x-base.lucide class="h-3.5 w-3.5 stroke-[1.7]" icon="ExternalLink" />
                                                <a href="{{ $document->documentType->getFirstMediaUrl('default_documents') }}"
                                                    target="_blank" class="ml-3 text-primary underline">View Default
                                                    File</a>
                                            </div>
                                            @if ($document->documentType->getFirstMediaUrl('default_documents'))
                                                <div class="flex items-center">
                                                    <input type="checkbox" id="approve-{{ $document->id }}"
                                                        {{ $document->status === \App\Models\CarrierDocument::STATUS_APPROVED ? 'checked' : '' }}
                                                        onchange="toggleApproval('{{ route('admin.carrier.approveDefaultDocument', [$carrier, $document]) }}', this)"
                                                        class="transition-all duration-100 ease-in-out shadow-sm border-slate-200 cursor-pointer rounded focus:ring-4 focus:ring-offset-0 focus:ring-primary focus:ring-opacity-20 [&[type='radio']]:checked:bg-primary [&[type='radio']]:checked:border-primary [&[type='radio']]:checked:border-opacity-10 [&[type='checkbox']]:checked:bg-primary [&[type='checkbox']]:checked:border-primary [&[type='checkbox']]:checked:border-opacity-10 [&:disabled:not(:checked)]:bg-slate-100 [&:disabled:not(:checked)]:cursor-not-allowed [&:disabled:not(:checked)]:dark:bg-darkmode-800/50 [&:disabled:checked]:opacity-70 [&:disabled:checked]:cursor-not-allowed [&:disabled:checked]:dark:bg-darkmode-800/50">

                                                    <div class="flex items-center">
                                                        <label for="approve-{{ $document->id }}" class="ml-3">
                                                            Approve Default
                                                        </label>
                                                        <x-base.tippy variant="primary"
                                                            content="If you have your own {{ $document->documentType->name }} file please upload it otherwise if you agree with the default file, just check the checkbox">
                                                            <div class="col-span-6 sm:col-span-3 lg:col-span-2 ml-4">
                                                                <i data-tw-merge data-lucide="alert-circle"
                                                                    class="stroke-[1] w-5 h-5 mx-auto block mx-auto block"></i>
                                                            </div>
                                                        </x-base.tippy>
                                                    </div>
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-gray-500">No file uploaded</span>
                                        @endif
                                        {{-- </div> --}}
                                    </div>
                            </x-base.table.td>
                            <x-base.table.td
                                class="box w-44 rounded-l-none rounded-r-none border-x-0 shadow-[5px_3px_5px_#00000005] first:rounded-l-[0.6rem] first:border-l last:rounded-r-[0.6rem] last:border-r">
                                <div class="mb-1.5 whitespace-nowrap text-xs text-slate-500">
                                    Notes
                                </div>
                                <div class="relative group">
                                    <div class="flex items-center">
                                        <span class="cursor-pointer text-primary text-decoration-none">View Notes</span>
                                        <x-base.tippy variant="primary"
                                            content=" {{ $document->notes ?? 'No notes available' }}">
                                            <div class="col-span-6 sm:col-span-3 lg:col-span-2 ml-4">
                                                <i data-tw-merge data-lucide="alert-circle"
                                                    class="stroke-[1] w-5 h-5 mx-auto block mx-auto block"></i>
                                            </div>
                                        </x-base.tippy>
                                    </div>
                                </div>
                            </x-base.table.td>
                            <x-base.table.td
                                class="box w-44 rounded-l-none rounded-r-none border-x-0 shadow-[5px_3px_5px_#00000005] first:rounded-l-[0.6rem] first:border-l last:rounded-r-[0.6rem] last:border-r">
                                <div class="mb-1 text-xs whitespace-nowrap text-slate-500">
                                    Status
                                </div>
                                <div
                                    class="flex items-center 
                                    {{ $document->status_name == 'Pending'
                                        ? 'text-orange-500'
                                        : ($document->status_name == 'Approved'
                                            ? 'text-green-500'
                                            : ($document->status_name == 'In Process'
                                                ? 'text-blue-500'
                                                : 'text-red-500')) }}">
                                    <x-base.lucide class="h-3.5 w-3.5 stroke-[1.7]"
                                        icon="{{ $document->status_name == 'Pending'
                                            ? 'AlertCircle'
                                            : ($document->status_name == 'Approved'
                                                ? 'CheckCircle'
                                                : ($document->status_name == 'In Process'
                                                    ? 'RefreshCw'
                                                    : 'XCircle')) }}" />
                                    <div class="ml-1.5 whitespace-nowrap">
                                        {{ $document->status_name }}
                                    </div>
                                </div>

                            </x-base.table.td>
                            <x-base.table.td
                                class="box w-44 rounded-l-none rounded-r-none border-x-0 shadow-[5px_3px_5px_#00000005] first:rounded-l-[0.6rem] first:border-l last:rounded-r-[0.6rem] last:border-r">
                                <div class="mb-1 text-xs whitespace-nowrap text-slate-500">
                                    Date
                                </div>
                                <div class="whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($document->updated_at)->format('d M Y') }}
                                </div>
                            </x-base.table.td>
                            <x-base.table.td
                                class="box relative w-20 rounded-l-none rounded-r-none border-x-0 py-0 shadow-[5px_3px_5px_#00000005] first:rounded-l-[0.6rem] first:border-l last:rounded-r-[0.6rem] last:border-r">
                                <div class="flex items-center justify-center">
                                    <x-base.menu class="h-5">
                                        <x-base.menu.button class="w-5 h-5 text-slate-500">
                                            <x-base.lucide class="w-5 h-5 fill-slate-400/70 stroke-slate-400/70"
                                                icon="MoreVertical" />
                                        </x-base.menu.button>
                                        <x-base.menu.items class="w-40">
                                            <x-base.menu.item
                                                data-tw-toggle="modal"
                                                data-tw-target="#uploadModal-{{ $document->documentType->id }}">
                                                <x-base.lucide class="w-4 h-4 mr-2" icon="FileSignature" />
                                                Upload/Replace
                                            </x-base.menu.item>
                                        </x-base.menu.items>
                                    </x-base.menu>
                                </div>
                            </x-base.table.td>
                        </x-base.table.tr>
                    @endforeach
                </x-base.table.tbody>
            </x-base.table>
        </div>
    </div>

    <!-- Modals -->
    @foreach ($documents as $document)
        <x-base.dialog id="uploadModal-{{ $document->documentType->id }}" size="md">
            <x-base.dialog.panel>
                <form id="upload-form-{{ $document->documentType->id }}"
                    action="{{ route('admin.carrier.admin_documents.upload', [$carrier->slug, $document->documentType->id]) }}"
                    method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <x-base.dialog.title class="border-b border-slate-200 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary/10">
                                <x-base.lucide class="w-5 h-5 text-primary" icon="Upload" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-800">Upload or Replace File</h3>
                                <p class="text-sm text-slate-500">{{ $document->documentType->name }}</p>
                            </div>
                        </div>
                    </x-base.dialog.title>

                    <x-base.dialog.description class="py-5">
                        <div class="w-full">
                            <label for="file-input-{{ $document->documentType->id }}"
                                class="flex flex-col items-center justify-center py-9 w-full border-2 border-slate-200 border-dashed rounded-xl cursor-pointer bg-slate-50 hover:border-primary hover:bg-primary/5 transition-colors">
                                <!-- Icono -->
                                <div class="mb-3 flex items-center justify-center">
                                    <x-base.lucide class="w-10 h-10 text-primary" icon="CloudUpload" />
                                </div>
                                <!-- Texto de ayuda -->
                                <p class="text-center text-slate-400 text-xs font-normal mb-1">
                                    PNG, JPG, PDF - Max 15MB
                                </p>
                                <p class="text-center text-slate-700 text-sm font-medium leading-snug">
                                    Drag and drop your file here or
                                    <span class="text-primary underline">click to upload</span>
                                </p>
                                <!-- Input oculto -->
                                <input id="file-input-{{ $document->documentType->id }}" type="file" name="document"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    class="hidden">
                            </label>
                            <!-- Área para mostrar el nombre del archivo -->
                            <p id="file-name-{{ $document->documentType->id }}"
                                class="mt-3 text-slate-600 text-sm font-medium text-center hidden">
                                <x-base.lucide class="w-4 h-4 inline mr-1" icon="FileText" />
                                <span id="file-name-text-{{ $document->documentType->id }}"></span>
                            </p>
                        </div>
                    </x-base.dialog.description>

                    <x-base.dialog.footer class="border-t border-slate-200 pt-4">
                        <div class="flex gap-3 justify-end w-full">
                            <x-base.button 
                                type="button" 
                                variant="outline-secondary" 
                                data-tw-dismiss="modal">
                                Cancel
                            </x-base.button>
                            <x-base.button 
                                type="submit" 
                                variant="primary" 
                                class="gap-2">
                                <x-base.lucide class="w-4 h-4" icon="Upload" />
                                Upload File
                            </x-base.button>
                        </div>
                    </x-base.dialog.footer>
                </form>
            </x-base.dialog.panel>
        </x-base.dialog>
    @endforeach
@endsection

@pushOnce('scripts')
    @vite('resources/js/app.js') {{-- Este debe ir primero --}}
    @vite('resources/js/pages/notification.js')
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // Mostrar nombre del archivo cuando se selecciona
            const fileInputs = document.querySelectorAll("input[type='file']");
            fileInputs.forEach(input => {
                input.addEventListener("change", (e) => {
                    const file = e.target.files[0];
                    if (file) {
                        const documentTypeId = input.id.split("file-input-")[1];
                        const fileNameDisplay = document.getElementById(`file-name-${documentTypeId}`);
                        const fileNameText = document.getElementById(`file-name-text-${documentTypeId}`);
                        
                        if (fileNameDisplay && fileNameText) {
                            fileNameText.textContent = file.name;
                            fileNameDisplay.classList.remove("hidden");
                        }
                    }
                });
            });

            // Lógica del formulario con Fetch y Toastify
            const forms = document.querySelectorAll("form[id^='upload-form']");
            forms.forEach(form => {
                form.addEventListener("submit", async (e) => {
                    e.preventDefault();

                    const formData = new FormData(form);

                    try {
                        const response = await fetch(form.action, {
                            method: "POST",
                            body: formData,
                            headers: {
                                "X-CSRF-TOKEN": document.querySelector(
                                    'input[name="_token"]').value,
                            },
                        });

                        if (response.ok) {
                            const successContent = document
                                .getElementById("success-notification-content")
                                .cloneNode(true);
                            successContent.classList.remove("hidden");

                            Toastify({
                                node: successContent,
                                duration: 3000,
                                gravity: "top",
                                position: "right",
                                stopOnFocus: true,
                            }).showToast();

                            setTimeout(() => location.reload(), 1500);
                        } else {
                            throw new Error("Upload failed. Please try again.");
                        }
                    } catch (error) {
                        const errorContent = document
                            .getElementById("error-notification-content")
                            .cloneNode(true);
                        errorContent.classList.remove("hidden");

                        Toastify({
                            node: errorContent,
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            stopOnFocus: true,
                        }).showToast();
                    }
                });
            });

            window.toggleApproval = async (url, checkbox) => {
                // Determina si el documento está aprobado o pendiente
                const approved = checkbox.checked ? 1 : 0;
                try {
                    const response = await fetch(url, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                                .content,
                        },
                        body: JSON.stringify({
                            approved
                        }),
                    });

                    if (response.ok) {
                        const result = await response.json();
                        location.reload();
                        console.log(result.message); // Puedes añadir un mensaje de éxito aquí
                    } else {
                        throw new Error("Failed to update document status.");
                    }
                } catch (error) {
                    console.error(error);
                    // Revertir el estado del checkbox si ocurre un error
                    checkbox.checked = !checkbox.checked;
                }
            };
        });
    </script>
@endPushOnce
