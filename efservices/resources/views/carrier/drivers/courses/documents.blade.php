@extends('../themes/' . $activeTheme)
@section('title', 'Documentos del Curso')
@php
$breadcrumbLinks = [
    ['label' => 'App', 'url' => route('carrier.dashboard')],
    ['label' => 'Cursos Profesionales', 'url' => route('carrier.courses.index')],
    ['label' => 'Documentos', 'active' => true],
];
@endphp

@section('subcontent')
<div>
    <!-- Flash Messages -->
    @if (session('success'))
    <x-base.alert variant="success" dismissible class="flex items-center gap-3 mb-5">
        <x-base.lucide class="w-8 h-8 text-white" icon="check-circle" />
        <span class="text-white">
            {{ session('success') }}
        </span>
        <x-base.alert.dismiss-button class="btn-close">
            <x-base.lucide class="h-4 w-4 text-white" icon="X" />
        </x-base.alert.dismiss-button>
    </x-base.alert>
    @endif

    @if (session('error'))
    <x-base.alert variant="danger" dismissible class="mb-5">
        <span class="text-white">
            {{ session('error') }}
        </span>
        <x-base.alert.dismiss-button class="btn-close">
            <x-base.lucide class="h-4 w-4 text-white" icon="X" />
        </x-base.alert.dismiss-button>
    </x-base.alert>
    @endif

    <!-- Page Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center justify-between mt-8">
        <div>
            <h2 class="text-lg font-medium">
                Documentos del Curso
            </h2>
            <p class="text-sm text-gray-600 mt-1">
                Conductor: {{ $course->driverDetail->user->name }} {{ $course->driverDetail->user->last_name ?? '' }}
            </p>
            <p class="text-sm text-gray-600">
                Organización: {{ $course->organization_name }}
            </p>
        </div>
        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
            <x-base.button as="a" href="{{ route('carrier.courses.edit', $course->id) }}" variant="outline-secondary">
                <x-base.lucide class="w-4 h-4 mr-1" icon="arrow-left" />
                Volver a Editar
            </x-base.button>
            <x-base.button as="a" href="{{ route('carrier.courses.index') }}" variant="outline-secondary">
                <x-base.lucide class="w-4 h-4 mr-1" icon="list" />
                Ver Todos los Cursos
            </x-base.button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="box box--stacked mt-5">
        <div class="box-body p-5">
            @if($certificates->count() > 0)
            <!-- Documents Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($certificates as $certificate)
                <div class="border rounded-lg p-4 bg-white hover:shadow-md transition-shadow" data-certificate-id="{{ $certificate->id }}">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1 min-w-0">
                            <!-- File Icon -->
                            <div class="mb-2">
                                @if(str_contains($certificate->mime_type, 'pdf'))
                                    <x-base.lucide class="w-8 h-8 text-red-500" icon="file-text" />
                                @elseif(str_contains($certificate->mime_type, 'image'))
                                    <x-base.lucide class="w-8 h-8 text-blue-500" icon="image" />
                                @else
                                    <x-base.lucide class="w-8 h-8 text-gray-500" icon="file" />
                                @endif
                            </div>
                            
                            <!-- File Name -->
                            <p class="font-medium text-sm truncate mb-1" title="{{ $certificate->file_name }}">
                                {{ $certificate->file_name }}
                            </p>
                            
                            <!-- File Type -->
                            <p class="text-xs text-gray-500 mb-1">
                                Tipo: {{ strtoupper(pathinfo($certificate->file_name, PATHINFO_EXTENSION)) }}
                            </p>
                            
                            <!-- File Size -->
                            <p class="text-xs text-gray-500 mb-1">
                                Tamaño: {{ number_format($certificate->size / 1024, 2) }} KB
                            </p>
                            
                            <!-- Upload Date -->
                            <p class="text-xs text-gray-500">
                                Subido: {{ $certificate->created_at->format('m/d/Y H:i') }}
                            </p>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex gap-2 mt-3 pt-3 border-t">
                        <x-base.button 
                            as="a" 
                            href="{{ route('carrier.courses.documents.preview', $certificate->id) }}" 
                            target="_blank"
                            variant="outline-primary" 
                            size="sm"
                            class="flex-1"
                        >
                            <x-base.lucide class="w-4 h-4 mr-1" icon="eye" />
                            Ver
                        </x-base.button>
                        
                        <x-base.button 
                            type="button"
                            variant="outline-danger" 
                            size="sm"
                            class="delete-certificate-btn"
                            data-certificate-id="{{ $certificate->id }}"
                            data-certificate-name="{{ $certificate->file_name }}"
                        >
                            <x-base.lucide class="w-4 h-4" icon="trash-2" />
                        </x-base.button>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <!-- Empty State -->
            <div class="text-center py-12">
                <x-base.lucide class="w-16 h-16 text-gray-400 mx-auto mb-4" icon="file-x" />
                <h3 class="text-lg font-medium text-gray-700 mb-2">
                    No hay certificados
                </h3>
                <p class="text-gray-500 mb-4">
                    Este curso no tiene certificados asociados.
                </p>
                <x-base.button as="a" href="{{ route('carrier.courses.edit', $course->id) }}" variant="primary">
                    <x-base.lucide class="w-4 h-4 mr-1" icon="upload" />
                    Subir Certificados
                </x-base.button>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal" tabindex="-1" aria-hidden="true" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Confirmar Eliminación</h3>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close">
                    <x-base.lucide class="w-4 h-4" icon="x" />
                </button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar el certificado <strong id="certificateName"></strong>?</p>
                <p class="text-sm text-gray-600 mt-2">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" id="confirmDelete" class="btn btn-danger">
                    Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

@pushOnce('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let certificateToDelete = null;
        const deleteModal = document.getElementById('deleteModal');
        const certificateNameEl = document.getElementById('certificateName');
        const confirmDeleteBtn = document.getElementById('confirmDelete');
        
        // Handle delete button clicks
        document.querySelectorAll('.delete-certificate-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                certificateToDelete = this.dataset.certificateId;
                certificateNameEl.textContent = this.dataset.certificateName;
                showModal(deleteModal);
            });
        });
        
        // Handle modal close buttons
        document.querySelectorAll('[data-dismiss="modal"]').forEach(btn => {
            btn.addEventListener('click', function() {
                hideModal(deleteModal);
                certificateToDelete = null;
            });
        });
        
        // Handle confirm delete
        confirmDeleteBtn.addEventListener('click', function() {
            if (!certificateToDelete) return;
            
            // Disable button and show loading state
            confirmDeleteBtn.disabled = true;
            confirmDeleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm mr-2"></span>Eliminando...';
            
            // Send AJAX request
            fetch(`{{ route('carrier.courses.documents.delete', '') }}/${certificateToDelete}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the certificate card from DOM
                    const certificateCard = document.querySelector(`[data-certificate-id="${certificateToDelete}"]`);
                    if (certificateCard) {
                        certificateCard.remove();
                    }
                    
                    // Check if there are no more certificates
                    const remainingCertificates = document.querySelectorAll('[data-certificate-id]');
                    if (remainingCertificates.length === 0) {
                        // Reload page to show empty state
                        window.location.reload();
                    }
                    
                    // Hide modal
                    hideModal(deleteModal);
                    
                    // Show success message
                    showAlert('success', data.message || 'Certificado eliminado exitosamente.');
                } else {
                    // Show error message
                    showAlert('danger', data.message || 'Error al eliminar el certificado.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Ocurrió un error al eliminar el certificado.');
            })
            .finally(() => {
                // Re-enable button
                confirmDeleteBtn.disabled = false;
                confirmDeleteBtn.innerHTML = 'Eliminar';
                certificateToDelete = null;
            });
        });
        
        // Helper functions
        function showModal(modal) {
            modal.style.display = 'block';
            modal.classList.add('show');
            document.body.classList.add('modal-open');
            
            // Add backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'modalBackdrop';
            document.body.appendChild(backdrop);
        }
        
        function hideModal(modal) {
            modal.style.display = 'none';
            modal.classList.remove('show');
            document.body.classList.remove('modal-open');
            
            // Remove backdrop
            const backdrop = document.getElementById('modalBackdrop');
            if (backdrop) {
                backdrop.remove();
            }
        }
        
        function showAlert(type, message) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show mb-5" role="alert">
                    <span>${message}</span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            // Insert at the top of the page
            const container = document.querySelector('.box.box--stacked');
            if (container) {
                container.insertAdjacentHTML('beforebegin', alertHtml);
                
                // Auto-dismiss after 5 seconds
                setTimeout(() => {
                    const alert = document.querySelector('.alert');
                    if (alert) {
                        alert.remove();
                    }
                }, 5000);
            }
        }
    });
</script>
@endPushOnce

@endsection
