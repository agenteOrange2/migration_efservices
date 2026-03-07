@extends('../themes/' . $activeTheme)

@section('title', 'Maintenance Calendar')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Vehicles', 'url' => route('admin.vehicles.index')],
        ['label' => 'Maintenance', 'url' => route('admin.maintenance.index')],
        ['label' => 'Calendar', 'active' => true],
    ];
@endphp

@section('styles')
    <!-- Estilos personalizados para los eventos del calendario de mantenimiento -->
    <style>
        /* Estilos para eventos de mantenimiento - Mostrar como bloques completos */
        .maintenance-completed {
            background-color: rgba(16, 185, 129, 0.7) !important;
            /* Verde semi-transparente */
            border-color: #10b981 !important;
            color: white !important;
        }

        .maintenance-pending {
            background-color: rgba(239, 68, 68, 0.7) !important;
            /* Rojo semi-transparente */
            border-color: #ef4444 !important;
            color: white !important;
        }

        .maintenance-upcoming {
            background-color: rgba(245, 158, 11, 0.7) !important;
            /* Amarillo/naranja semi-transparente */
            border-color: #f59e0b !important;
            color: white !important;
        }

        /* Asegurar que todos los eventos se muestren como bloques completos */
        .fc-daygrid-event {
            white-space: normal !important;
            align-items: normal !important;
            display: block !important;
        }

        /* Mejorar la visibilidad del texto en los eventos */
        .fc-event-title {
            font-weight: 500;
            padding: 2px 0;
        }

        /* Ajustar altura mínima para eventos */
        .fc-daygrid-event-harness {
            min-height: 25px;
        }
    </style>
@endsection

@section('subcontent')


    <!-- Professional Header -->
    <div class="box box--stacked p-8 mb-8">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <x-base.lucide class="w-8 h-8 text-primary" icon="Calendar" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">Maintenance Calendar</h1>
                    <p class="text-slate-600">Manage and track maintenance records</p>
                </div>
            </div>
            <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                <x-base.button as="a" href="{{ route('admin.maintenance.create') }}" class="btn btn-primary  mr-2"
                    variant="primary">
                    <i class="w-4 h-4 mr-2" data-lucide="plus"></i> New Maintenance
                </x-base.button>
                <x-base.button as="a" href="{{ route('admin.maintenance.index') }}" class="btn btn-secondary "
                    variant="primary">
                    <i class="w-4 h-4 mr-2" data-lucide="list"></i> List
                </x-base.button>
            </div>
        </div>
    </div>

    <div class="intro-y box p-5 mt-5">
        <div class="grid grid-cols-12 gap-5">
            <!-- Filtros -->
            <div class="col-span-12 lg:col-span-3">
                <div class="box p-5">
                    <h2 class="font-medium text-base mb-5">Filters</h2>
                    <form id="filter-form" action="{{ route('admin.maintenance.calendar') }}" method="GET">
                        <div class="mb-4">
                            <label class="form-label">Vehicle</label>
                            <select name="vehicle_id"
                                class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                                <option value="">All vehicles</option>
                                @php
                                    $availableVehicles = isset($vehicles) ? $vehicles : collect();
                                @endphp
                                @foreach ($availableVehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}"
                                        {{ isset($vehicleId) && $vehicleId == $vehicle->id ? 'selected' : '' }}>
                                        {{ $vehicle->make }} {{ $vehicle->model }}
                                        ({{ $vehicle->company_unit_number ?? $vehicle->vin }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Status</label>
                            <select name="status"
                                class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                                @php $selectedStatus = $status ?? ''; @endphp
                                <option value="">All</option>
                                <option value="1" {{ $selectedStatus == '1' ? 'selected' : '' }}>Completed</option>
                                <option value="0" {{ $selectedStatus == '0' ? 'selected' : '' }}>Pending</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-full">Apply filters</button>
                        @if (isset($vehicleId) || isset($status))
                            <a href="{{ route('admin.maintenance.calendar') }}" class="btn btn-secondary w-full mt-2">
                                Clear filters
                            </a>
                        @endif
                    </form>

                    @if (isset($vehicleId) || isset($status))
                        <div class="mt-3 p-3 bg-blue-50 rounded-md text-sm">
                            <div class="font-medium text-blue-800 mb-1">Active filters:</div>
                            @if (isset($vehicleId))
                                <div class="text-blue-600">• Vehicle: {{ $vehicles->find($vehicleId)->make ?? '' }}
                                    {{ $vehicles->find($vehicleId)->model ?? '' }}</div>
                            @endif
                            @if (isset($status))
                                <div class="text-blue-600">• Status: {{ $status == '1' ? 'Completed' : 'Pending' }}</div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="box p-5 mt-5">
                    <h2 class="font-medium text-base mb-3">Next Maintenance</h2>
                    @if (isset($vehicleId))
                        <p class="text-xs text-slate-500 mb-4">Showing upcoming maintenance for selected vehicle</p>
                    @else
                        <p class="text-xs text-slate-500 mb-4">Showing next 5 upcoming maintenance</p>
                    @endif
                    <div class="space-y-4">
                        @php
                            $upcomingMaintenances = $upcomingMaintenances ?? collect();
                        @endphp
                        @forelse($upcomingMaintenances as $maintenance)
                            <div class="border rounded-md p-3 bg-amber-50">
                                <div class="font-medium">{{ $maintenance->service_tasks }}</div>
                                <div class="text-slate-500 text-xs mt-1">
                                    <span class="font-medium">Vehicle:</span> {{ $maintenance->vehicle->make }}
                                    {{ $maintenance->vehicle->model }}
                                </div>
                                <div class="text-slate-500 text-xs mt-1">
                                    <span class="font-medium">Date:</span>
                                    {{ Carbon\Carbon::parse($maintenance->next_service_date)->format('m/d/Y') }}
                                </div>
                                <div class="text-slate-500 text-xs mt-1">
                                    <span class="font-medium">Cost:</span> ${{ number_format($maintenance->cost, 2) }}
                                </div>
                                <div class="mt-2">
                                    <a href="{{ route('admin.maintenance.edit', $maintenance->id) }}"
                                        class="btn btn-sm btn-secondary w-full">View details</a>
                                </div>
                            </div>
                        @empty
                            <div class="text-slate-500 text-center py-4">
                                No upcoming maintenance scheduled
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Calendario -->
            <div class="col-span-12 lg:col-span-9">
                <div class="box box--stacked flex flex-col p-5">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-medium text-base">
                            Maintenance Calendar
                            <span class="text-slate-500 text-sm ml-2">({{ count($events ?? []) }} events)</span>
                        </h3>
                    </div>
                    <!-- Datos de eventos almacenados para que el calendario los lea -->
                    <div id="maintenance-events-data" style="display: none;"
                        data-events="{{ json_encode($events ?? []) }}"></div>
                    <x-calendar id="calendar" />
                </div>
            </div>
        </div>
    </div>
    <!-- Modal de detalles de mantenimiento usando Alpine.js -->
    <div id="maintenance-modal-wrapper" x-data="modalData">
        <x-base.dialog id="maintenance-modal">
            <x-base.dialog.panel>
                <div class="p-5 text-center">
                    <h2 id="modal-title" class="font-medium text-base mr-auto text-left" x-text="title">Maintenance Details
                    </h2>
                    <div class="mt-4 text-left">
                        <div class="mb-4">
                            <div class="font-medium">Vehicle</div>
                            <div id="modal-vehicle" class="text-slate-600 mt-1" x-text="vehicle"></div>
                        </div>
                        <div class="mb-4">
                            <div class="font-medium">Service type</div>
                            <div id="modal-service-type" class="text-slate-600 mt-1" x-text="serviceType"></div>
                        </div>
                        <div class="mb-4">
                            <div class="font-medium">Service date</div>
                            <div id="modal-service-date" class="text-slate-600 mt-1" x-text="serviceDate"></div>
                        </div>
                        <div class="mb-4">
                            <div class="font-medium">Status</div>
                            <div id="modal-status" class="mt-1" x-html="status"></div>
                        </div>
                        <div id="modal-cost-section" class="mb-4" x-show="showCost">
                            <div class="font-medium">Cost</div>
                            <div id="modal-cost" class="text-slate-600 mt-1" x-text="cost"></div>
                        </div>
                        <div id="modal-description-section" class="mb-4" x-show="showDescription">
                            <div class="font-medium">Description</div>
                            <div id="modal-description" class="text-slate-600 mt-1" x-text="description"></div>
                        </div>
                    </div>
                    <div class="mt-5 text-right">
                        <a id="modal-view-complete-btn" x-bind:href="viewLink" class="btn btn-primary mr-1">View
                            complete</a>
                        <x-base.button id="modal-close-btn" data-tw-dismiss="modal" variant="outline-secondary"
                            type="button">
                            Close
                        </x-base.button>
                    </div>
                </div>
            </x-base.dialog.panel>
        </x-base.dialog>
    </div>
@endsection


@push('scripts')
    <script>
        // Reemplazar directamente la inicialización del calendario para usar eventos de mantenimiento
        document.addEventListener('DOMContentLoaded', function() {
            // Sobrescribir la función que inicializa el calendario
            const originalInit = window.initCalendar;

            window.initCalendar = function() {
                // Task 3.1: Implement event data extraction from DOM
                // Parse JSON from #maintenance-events-data element's data attribute
                let maintenanceEvents = [];
                const maintenanceEventsElement = document.getElementById('maintenance-events-data');

                if (maintenanceEventsElement && maintenanceEventsElement.dataset.events) {
                    try {
                        // Parse the JSON data from the data attribute
                        maintenanceEvents = JSON.parse(maintenanceEventsElement.dataset.events);
                        console.log('Successfully parsed maintenance events data');
                        console.log('Number of events:', maintenanceEvents.length);
                    } catch (e) {
                        // Add error handling for invalid JSON with console logging
                        console.error('Error parsing maintenance events JSON:', e);
                        console.error('Raw data:', maintenanceEventsElement.dataset.events);
                        // Initialize empty array as fallback
                        maintenanceEvents = [];
                    }
                } else {
                    // Initialize empty array as fallback if element not found
                    console.warn('Maintenance events data element not found, using empty array');
                    maintenanceEvents = [];
                }

                // Task 3.2: Implement event formatting for FullCalendar
                // Map each event to include prefixed ID, formatted title, colors, and extendedProps
                maintenanceEvents = maintenanceEvents.map(event => {
                    // Determine backgroundColor and borderColor based on status
                    let backgroundColor, borderColor;

                    if (event.status === 1 || event.completed === 1 || event.completed === true) {
                        // Completed - green
                        backgroundColor = '#10b981';
                        borderColor = '#10b981';
                    } else if (event.status === 2 || event.upcoming === true) {
                        // Upcoming - amber
                        backgroundColor = '#f59e0b';
                        borderColor = '#f59e0b';
                    } else {
                        // Pending - red
                        backgroundColor = '#ef4444';
                        borderColor = '#ef4444';
                    }

                    // Override with custom color if provided
                    if (event.backgroundColor) {
                        backgroundColor = event.backgroundColor;
                    }
                    if (event.borderColor) {
                        borderColor = event.borderColor;
                    }

                    // Format title with service type and vehicle name
                    const title = event.title ||
                        `${event.service_type || 'Service'} - ${event.vehicle_name || event.vehicle || 'Unknown Vehicle'}`;

                    return {
                        // Map each event to include prefixed ID
                        id: `service-${event.id}`,
                        // Format title with service type and vehicle name
                        title: title,
                        start: event.start || event.date,
                        // Set backgroundColor and borderColor based on status
                        backgroundColor: backgroundColor,
                        borderColor: borderColor,
                        // Store all original data in extendedProps object
                        extendedProps: {
                            ...event, // Spread all original event data
                            // Ensure we have normalized property names for easy access
                            id: event.id,
                            vehicle_name: event.vehicle_name || event.vehicle || '',
                            service_type: event.service_type || '',
                            date: event.date || event.start,
                            cost: event.cost || '',
                            description: event.description || '',
                            status: event.status,
                            completed: event.completed
                        }
                    };
                });

                console.log('Events formatted for FullCalendar:', maintenanceEvents.length, 'events');

                // Buscar todas las instancias de calendario
                $(".full-calendar").each(function() {
                    // Obtener el elemento del DOM para el calendario
                    const el = $(this).children()[0];

                    // Task 4.1: Update calendar initialization options
                    // Configure FullCalendar with enhanced options for better user experience
                    const calendarOptions = {
                        plugins: [
                            interactionPlugin,
                            dayGridPlugin,
                            timeGridPlugin,
                            listPlugin,
                        ],
                        droppable: true,
                        // Configure header toolbar with all view options (Requirement 1.4, 7.1)
                        headerToolbar: {
                            left: "prev,next today",
                            center: "title",
                            right: "dayGridMonth,timeGridWeek,timeGridDay,listWeek",
                        },
                        initialDate: new Date(),
                        navLinks: true,
                        editable: false,
                        dayMaxEvents: true,
                        events: maintenanceEvents,

                        // Set displayEventTime: false to hide event times (Requirement 1.4)
                        displayEventTime: false,
                        // Set eventDisplay: 'block' for block-level event rendering (Requirement 1.4)
                        eventDisplay: 'block',
                        eventTimeFormat: {
                            hour: '2-digit',
                            minute: '2-digit',
                            meridiem: 'short'
                        },
                        // Enable selectable: true for date click handling (Requirement 7.1)
                        selectable: true,
                        // Manejar clic en una fecha vacía
                        dateClick: function(info) {
                            console.log('CLICK EN FECHA:', info);

                            // Formatear la fecha correctamente para la URL y para la visualización
                            const formattedDate = info.date.toLocaleDateString();
                            // Formato ISO para la URL (YYYY-MM-DD) - Extraer solo la fecha sin timestamp
                            const dateForUrl = info.dateStr.split('T')[
                                0]; // Obtener solo la parte YYYY-MM-DD

                            // Establecer atributos directamente en elementos del DOM usando IDs específicos
                            const modalTitle = document.getElementById('modal-title');
                            const modalVehicle = document.getElementById('modal-vehicle');
                            const modalServiceType = document.getElementById(
                                'modal-service-type');
                            const modalServiceDate = document.getElementById(
                                'modal-service-date');
                            const modalStatus = document.getElementById('modal-status');
                            const modalCostSection = document.getElementById(
                                'modal-cost-section');
                            const modalDescriptionSection = document.getElementById(
                                'modal-description-section');
                            const viewCompleteButton = document.getElementById(
                                'modal-view-complete-btn');

                            if (modalTitle) modalTitle.textContent = 'New Maintenance';
                            if (modalVehicle) modalVehicle.textContent = 'Select a vehicle';
                            if (modalServiceType) modalServiceType.textContent = 'New service';
                            if (modalServiceDate) modalServiceDate.textContent = formattedDate;
                            if (modalStatus) {
                                modalStatus.innerHTML =
                                    '<span class="px-2 py-1 rounded-full bg-primary text-white">New</span>';
                            }

                            // Ocultar elementos cost y description
                            if (modalCostSection) modalCostSection.style.display = 'none';
                            if (modalDescriptionSection) modalDescriptionSection.style.display =
                                'none';

                            // Establecer el enlace del botón "View complete"
                            const viewCompleteUrl =
                                `{{ route('admin.maintenance.create') }}?date=${dateForUrl}`;
                            if (viewCompleteButton) {
                                viewCompleteButton.href = viewCompleteUrl;
                            }

                            console.log('Datos actualizados directamente en el DOM');

                            // Abrir el modal usando el atributo data-tw-toggle
                            const modal = document.getElementById('maintenance-modal');
                            if (modal) {
                                console.log('Abriendo modal');
                                // Usar el método correcto para abrir modales en Tailwind Elements
                                const modalInstance = tailwind.Modal.getOrCreateInstance(modal);
                                modalInstance.show();
                            } else {
                                console.error('No se pudo encontrar el modal');
                            }
                        },
                        // Task 4.2: Implement select handler to prevent range selection
                        // Add select callback that immediately calls unselect() (Requirement 7.2, 7.4)
                        // Ensure single-date clicks are prioritized
                        select: function(info) {
                            // Immediately unselect to prevent date range selection
                            info.view.calendar.unselect();
                        },
                        eventClick: function(info) {
                            // Task 5.1: Create eventClick callback function
                            // Prevent default event behavior
                            info.jsEvent.preventDefault();

                            // Extract event data from event.extendedProps (Requirement 2.1, 6.1, 6.2)
                            const event = info.event;
                            const props = event.extendedProps || {};

                            console.log('Event clicked - Full event:', event);
                            console.log('Event clicked - Extended props:', props);

                            // Parse maintenance ID by removing "service-" prefix if present (Requirement 6.1, 6.2)
                            let maintenanceId = props.id;
                            if (event.id && typeof event.id === 'string' && event.id.startsWith(
                                    'service-')) {
                                maintenanceId = event.id.replace('service-', '');
                            } else if (typeof maintenanceId === 'string' && maintenanceId
                                .startsWith('service-')) {
                                maintenanceId = maintenanceId.replace('service-', '');
                            }

                            console.log('Parsed maintenance ID:', maintenanceId);

                            // Task 5.2: Implement modal content population via DOM manipulation
                            // Get all modal elements by their specific IDs
                            const modalTitle = document.getElementById('modal-title');
                            const modalVehicle = document.getElementById('modal-vehicle');
                            const modalServiceType = document.getElementById(
                                'modal-service-type');
                            const modalServiceDate = document.getElementById(
                                'modal-service-date');
                            const modalStatus = document.getElementById('modal-status');
                            const modalCost = document.getElementById('modal-cost');
                            const modalCostSection = document.getElementById(
                                'modal-cost-section');
                            const modalDescription = document.getElementById(
                                'modal-description');
                            const modalDescriptionSection = document.getElementById(
                                'modal-description-section');
                            const viewCompleteButton = document.getElementById(
                                'modal-view-complete-btn');

                            // Update modal title with event title (Requirement 2.2, 5.2)
                            const title = event.title || props.title || 'Maintenance Details';
                            if (modalTitle) {
                                modalTitle.textContent = title;
                            }

                            // Set vehicle name, service type, and service date text content (Requirement 2.2, 5.2)
                            const vehicle = props.vehicle_name || props.vehicle || '';
                            if (modalVehicle) {
                                modalVehicle.textContent = vehicle;
                            }

                            const serviceType = props.service_type || props.serviceType || (
                                title ? title.split(' - ')[0] : '');
                            if (modalServiceType) {
                                modalServiceType.textContent = serviceType;
                            }

                            // Format service date
                            let serviceDate = '';
                            if (props.date) {
                                serviceDate = props.date;
                            } else if (props.serviceDate) {
                                serviceDate = props.serviceDate;
                            } else if (event.start) {
                                serviceDate = event.start.toLocaleDateString();
                            }
                            if (modalServiceDate) {
                                modalServiceDate.textContent = serviceDate;
                            }

                            // Set status badge HTML with color-coded styling (Requirement 2.2, 5.3)
                            let statusHtml = '';
                            const status = props.status || props.completed;

                            if (status === 1 || status === true || props.completed === 1 ||
                                props.completed === true) {
                                statusHtml =
                                    '<span class="px-2 py-1 rounded-full bg-success text-white">Completed</span>';
                            } else if (status === 2 || props.upcoming === true) {
                                statusHtml =
                                    '<span class="px-2 py-1 rounded-full bg-warning text-white">Upcoming</span>';
                            } else {
                                statusHtml =
                                    '<span class="px-2 py-1 rounded-full bg-danger text-white">Pending</span>';
                            }

                            if (modalStatus) {
                                modalStatus.innerHTML = statusHtml;
                            }

                            // Conditionally show/hide cost and description sections (Requirement 2.3, 5.4, 5.5)
                            const cost = props.cost || '';
                            const description = props.description || '';

                            if (cost && cost.trim() !== '') {
                                if (modalCost) modalCost.textContent = cost;
                                if (modalCostSection) modalCostSection.style.display = 'block';
                            } else {
                                if (modalCostSection) modalCostSection.style.display = 'none';
                            }

                            if (description && description.trim() !== '') {
                                if (modalDescription) modalDescription.textContent =
                                    description;
                                if (modalDescriptionSection) modalDescriptionSection.style
                                    .display = 'block';
                            } else {
                                if (modalDescriptionSection) modalDescriptionSection.style
                                    .display = 'none';
                            }

                            // Task 5.3: Implement "View complete" button URL construction
                            // Build edit URL using format /admin/maintenance/{id}/edit (Requirement 2.4, 6.3, 6.4, 6.5)
                            let viewLink = '#';

                            if (maintenanceId) {
                                // Construct the URL using the admin maintenance edit route pattern
                                viewLink =
                                    `{{ url('admin/maintenance') }}/${maintenanceId}/edit`;
                                console.log('Constructed edit URL:', viewLink);
                            } else {
                                // Add error logging if ID cannot be determined (Requirement 6.4)
                                console.error('Cannot determine maintenance ID for edit URL');
                            }

                            // Set href attribute on the button element (Requirement 2.4, 6.5)
                            if (viewCompleteButton) {
                                viewCompleteButton.href = viewLink;
                            }

                            console.log('Modal content populated via DOM manipulation');

                            // Task 5.4: Implement modal opening with error handling
                            // Open modal using Tailwind Modal API (Requirement 2.1, 8.3)
                            const modal = document.getElementById('maintenance-modal');
                            if (modal) {
                                try {
                                    console.log('Opening maintenance details modal');
                                    // Use Tailwind Modal API to open the modal
                                    const modalInstance = tailwind.Modal.getOrCreateInstance(
                                        modal);
                                    modalInstance.show();
                                } catch (error) {
                                    // Add try-catch with fallback method (Requirement 8.3)
                                    // Log errors to console for debugging
                                    console.error('Error opening modal:', error);

                                    // Fallback: trigger click on modal toggle button if exists
                                    const toggleButton = document.querySelector(
                                        '[data-tw-toggle="modal"][data-tw-target="#maintenance-modal"]'
                                    );
                                    if (toggleButton) {
                                        console.log('Using fallback method to open modal');
                                        toggleButton.click();
                                    } else {
                                        console.error(
                                            'Fallback method failed: no toggle button found'
                                        );
                                    }
                                }
                            } else {
                                console.error('Modal element not found');
                            }
                        }
                    };

                    // Crear el calendario con nuestras opciones
                    console.log('Inicializando FullCalendar con eventos:', maintenanceEvents);
                    let calendar = new Calendar(el, calendarOptions);
                    calendar.render();

                    // Almacenar el calendario en una variable global para referencia
                    window.calendar = calendar;
                });
            };

            // Task 8: Add DOMContentLoaded wrapper and initialization
            // Ensure FullCalendar plugins are loaded before initialization (Requirement 8.1, 8.4, 8.5)
            // Add console logging for debugging
            console.log('DOMContentLoaded event fired - checking for FullCalendar plugins');

            // Check if all required FullCalendar plugins are loaded
            if (typeof interactionPlugin !== 'undefined' &&
                typeof dayGridPlugin !== 'undefined' &&
                typeof timeGridPlugin !== 'undefined' &&
                typeof listPlugin !== 'undefined') {
                console.log('All FullCalendar plugins loaded successfully');
                console.log('Initializing calendar...');
                window.initCalendar();
            } else {
                console.error('FullCalendar plugins not loaded. Missing plugins:');
                if (typeof interactionPlugin === 'undefined') console.error('- interactionPlugin');
                if (typeof dayGridPlugin === 'undefined') console.error('- dayGridPlugin');
                if (typeof timeGridPlugin === 'undefined') console.error('- timeGridPlugin');
                if (typeof listPlugin === 'undefined') console.error('- listPlugin');

                // Retry after a short delay in case plugins are still loading
                console.log('Retrying plugin check in 500ms...');
                setTimeout(() => {
                    if (typeof interactionPlugin !== 'undefined' &&
                        typeof dayGridPlugin !== 'undefined' &&
                        typeof timeGridPlugin !== 'undefined' &&
                        typeof listPlugin !== 'undefined') {
                        console.log('Plugins loaded on retry - initializing calendar');
                        window.initCalendar();
                    } else {
                        console.error('Failed to load FullCalendar plugins after retry');
                    }
                }, 500);
            }
        });
    </script>

    <script>
        // Definir variables iniciales para Alpine.js
        document.addEventListener('alpine:init', () => {
            Alpine.data('modalData', () => ({
                title: 'Maintenance Details',
                vehicle: 'No vehicle selected',
                serviceType: 'No service selected',
                serviceDate: '-',
                status: '<span class="px-2 py-1 rounded-full bg-primary text-white">Default</span>',
                cost: '',
                description: '',
                viewLink: '#',
                showCost: false,
                showDescription: false
            }));
        });

        // Función global para abrir el modal
        window.openMaintenanceModal = function() {
            // Utilizamos setTimeout para evitar reflow forzado
            setTimeout(() => {
                const modal = document.getElementById('maintenance-modal');
                if (modal) {
                    try {
                        const modalInstance = tailwind.Modal.getOrCreateInstance(modal);
                        modalInstance.show();
                    } catch (error) {
                        console.error('Error opening modal:', error);
                        // Fallback: trigger click on modal toggle button if exists
                        const toggleButton = document.querySelector(
                            '[data-tw-toggle="modal"][data-tw-target="#maintenance-modal"]');
                        if (toggleButton) {
                            toggleButton.click();
                        }
                    }
                }
            }, 10);
        };
    </script>
@endpush
