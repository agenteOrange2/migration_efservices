{{-- resources/views/auth/user_driver/select_carrier_registration.blade.php --}}
<x-driver-layout>
    <div x-data="carrierTable()" class="container mx-auto px-4 py-8 max-w-7xl">
        <div class="mb-8 text-center">
            <h2 class="text-3xl font-bold text-gray-800"> Choose a Carrier</h2>
            <p class="text-gray-600 mt-2">Choose the company you want to work with</p>
        </div>
        
        <!-- Filtros y búsqueda -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <!-- Búsqueda general -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            x-model="filters.search" 
                            @input="applyFilters()"
                            placeholder="Search by name, DOT, MC, address..." 
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        >
                    </div>
                </div>
                
                <!-- Filtro por estado -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">State</label>
                    <select 
                        x-model="filters.state" 
                        @change="applyFilters()"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                        <option value="">All states</option>
                        @foreach($states as $state)
                            <option value="{{ $state }}">{{ $state }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <!-- Filtro por disponibilidad -->
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                <div class="flex flex-wrap items-center gap-3 sm:gap-4">
                    <label class="flex items-center">
                        <input 
                            type="radio" 
                            name="availability" 
                            value="all"
                            x-model="filters.availability"
                            @change="applyFilters()"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                        >
                        <span class="ml-2 text-sm text-gray-700">All</span>
                    </label>
                    <label class="flex items-center">
                        <input 
                            type="radio" 
                            name="availability" 
                            value="available"
                            x-model="filters.availability"
                            @change="applyFilters()"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                        >
                        <span class="ml-2 text-sm text-gray-700">Available</span>
                    </label>
                    <label class="flex items-center">
                        <input 
                            type="radio" 
                            name="availability" 
                            value="full"
                            x-model="filters.availability"
                            @change="applyFilters()"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                        >
                        <span class="ml-2 text-sm text-gray-700">Full</span>
                    </label>
                </div>
                <div class="text-sm text-gray-600 sm:ml-auto">
                    <span x-text="filteredCarriers.length"></span> of <span x-text="allCarriers.length"></span> companies
                </div>
            </div>
        </div>
        
        <!-- Tabla de empresas - Vista Desktop -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <!-- Vista Desktop (Tabla) -->
            <div class="hidden lg:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th 
                                @click="sortBy('name')"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                            >
                                <div class="flex items-center gap-2">
                                    <span>Company</span>
                                    <svg x-show="sortColumn === 'name' && sortDirection === 'asc'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                    </svg>
                                    <svg x-show="sortColumn === 'name' && sortDirection === 'desc'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </th>
                            <th 
                                @click="sortBy('state')"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                            >
                                <div class="flex items-center gap-2">
                                    <span>Location</span>
                                    <svg x-show="sortColumn === 'state' && sortDirection === 'asc'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                    </svg>
                                    <svg x-show="sortColumn === 'state' && sortDirection === 'desc'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                DOT / MC
                            </th>
                            <th 
                                @click="sortBy('drivers')"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                            >
                                <div class="flex items-center gap-2">
                                    <span>Drivers</span>
                                    <svg x-show="sortColumn === 'drivers' && sortDirection === 'asc'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                    </svg>
                                    <svg x-show="sortColumn === 'drivers' && sortDirection === 'desc'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-if="filteredCarriers.length === 0">
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <h3 class="mt-4 text-lg font-medium text-gray-900">No companies found</h3>
                                    <p class="mt-1 text-gray-500">Try adjusting the search filters</p>
                                </td>
                            </tr>
                        </template>
                        <template x-for="carrier in paginatedCarriers" :key="carrier.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <template x-if="carrier.media && carrier.media.length > 0">
                                            <img :src="carrier.media[0].original_url" :alt="carrier.name" class="h-10 w-10 rounded object-contain mr-3">
                                        </template>
                                        <template x-if="!carrier.media || carrier.media.length === 0">
                                            <div class="h-10 w-10 rounded bg-gray-100 flex items-center justify-center mr-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                            </div>
                                        </template>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900" x-text="carrier.name"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900" x-text="carrier.address"></div>
                                    <div class="text-sm text-gray-500" x-text="carrier.state + ' ' + carrier.zipcode"></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <span x-text="'DOT: ' + carrier.dot_number"></span>
                                    </div>
                                    <template x-if="carrier.mc_number">
                                        <div class="text-sm text-gray-500" x-text="'MC: ' + carrier.mc_number"></div>
                                    </template>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <span x-text="carrier.driver_count"></span> / <span x-text="carrier.max_drivers"></span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                        <div 
                                            class="h-2 rounded-full"
                                            :class="carrier.is_full ? 'bg-red-500' : 'bg-green-500'"
                                            :style="'width: ' + (carrier.driver_count / carrier.max_drivers * 100) + '%'"
                                        ></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <template x-if="carrier.is_full">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Full
                                        </span>
                                    </template>
                                    <template x-if="!carrier.is_full">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Available
                                        </span>
                                    </template>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <template x-if="!carrier.is_full">
                                        <a 
                                            :href="`{{ route('driver.register.form', '') }}/${carrier.slug}`" 
                                            class="text-blue-600 hover:text-blue-900 font-medium"
                                        >
                                            Select
                                        </a>
                                    </template>
                                    <template x-if="carrier.is_full">
                                        <span class="text-gray-400 cursor-not-allowed">Not available</span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            
            <!-- Vista Mobile (Cards) -->
            <div class="lg:hidden">
                <template x-if="filteredCarriers.length === 0">
                    <div class="px-4 py-12 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">No companies found</h3>
                        <p class="mt-1 text-gray-500">Try adjusting the search filters</p>
                    </div>
                </template>
                <div class="divide-y divide-gray-200">
                    <template x-for="carrier in paginatedCarriers" :key="carrier.id">
                        <div class="p-4 hover:bg-gray-50 transition-colors">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center flex-1 min-w-0">
                                    <template x-if="carrier.media && carrier.media.length > 0">
                                        <img :src="carrier.media[0].original_url" :alt="carrier.name" class="h-12 w-12 rounded object-contain mr-3 flex-shrink-0">
                                    </template>
                                    <template x-if="!carrier.media || carrier.media.length === 0">
                                        <div class="h-12 w-12 rounded bg-gray-100 flex items-center justify-center mr-3 flex-shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </div>
                                    </template>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-sm font-medium text-gray-900 truncate" x-text="carrier.name"></h3>
                                        <div class="mt-1">
                                            <template x-if="carrier.is_full">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                    Full
                                                </span>
                                            </template>
                                            <template x-if="!carrier.is_full">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                    Available
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="space-y-2 text-sm">
                                <div class="flex items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 mr-2 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-gray-900" x-text="carrier.address"></div>
                                        <div class="text-gray-500" x-text="carrier.state + ' ' + carrier.zipcode"></div>
                                    </div>
                                </div>
                                
                                <div class="flex items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 mr-2 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <div class="flex-1">
                                        <span class="text-gray-900" x-text="'DOT: ' + carrier.dot_number"></span>
                                        <template x-if="carrier.mc_number">
                                            <span class="text-gray-500 ml-2" x-text="'MC: ' + carrier.mc_number"></span>
                                        </template>
                                    </div>
                                </div>
                                
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <div class="flex-1">
                                        <span class="text-gray-900" x-text="carrier.driver_count + ' / ' + carrier.max_drivers + ' drivers'"></span>
                                        <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                            <div 
                                                class="h-2 rounded-full"
                                                :class="carrier.is_full ? 'bg-red-500' : 'bg-green-500'"
                                                :style="'width: ' + (carrier.driver_count / carrier.max_drivers * 100) + '%'"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <template x-if="!carrier.is_full">
                                    <a 
                                        :href="`{{ route('driver.register.form', '') }}/${carrier.slug}`" 
                                        class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors"
                                    >
                                        Select Carrier
                                    </a>
                                </template>
                                <template x-if="carrier.is_full">
                                    <button disabled class="block w-full text-center bg-gray-300 text-gray-500 cursor-not-allowed font-medium py-2 px-4 rounded-md">
                                        Not Available
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            <!-- Paginación -->
            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6" x-show="totalPages > 1">
                <div class="flex-1 flex justify-between sm:hidden">
                    <button 
                        @click="previousPage()"
                        :disabled="currentPage === 1"
                        :class="currentPage === 1 ? 'opacity-50 cursor-not-allowed' : ''"
                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                    >
                        Previous
                    </button>
                    <button 
                        @click="nextPage()"
                        :disabled="currentPage === totalPages"
                        :class="currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : ''"
                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                    >
                        Next
                    </button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing
                            <span class="font-medium" x-text="((currentPage - 1) * itemsPerPage) + 1"></span>
                            a
                            <span class="font-medium" x-text="Math.min(currentPage * itemsPerPage, filteredCarriers.length)"></span>
                            de
                            <span class="font-medium" x-text="filteredCarriers.length"></span>
                            results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <button 
                                @click="previousPage()"
                                :disabled="currentPage === 1"
                                :class="currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'"
                                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500"
                            >
                                    <span class="sr-only">Previous</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <template x-for="page in visiblePages" :key="page">
                                <button 
                                    @click="goToPage(page)"
                                    :class="page === currentPage ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'"
                                    class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                    x-text="page"
                                ></button>
                            </template>
                            <button 
                                @click="nextPage()"
                                :disabled="currentPage === totalPages"
                                :class="currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'"
                                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500"
                            >
                                <span class="sr-only">Next</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function carrierTable() {
            return {
                allCarriers: @json($carriers->values()),
                filteredCarriers: [],
                filters: {
                    search: '',
                    state: '',
                    availability: 'available'
                },
                sortColumn: 'name',
                sortDirection: 'asc',
                currentPage: 1,
                itemsPerPage: 20,
                
                init() {
                    this.applyFilters();
                },
                
                applyFilters() {
                    let filtered = [...this.allCarriers];
                    
                    // Filtro de búsqueda
                    if (this.filters.search) {
                        const search = this.filters.search.toLowerCase();
                        filtered = filtered.filter(carrier => 
                            carrier.name.toLowerCase().includes(search) ||
                            (carrier.dot_number && carrier.dot_number.toString().includes(search)) ||
                            (carrier.mc_number && carrier.mc_number.toString().toLowerCase().includes(search)) ||
                            (carrier.address && carrier.address.toLowerCase().includes(search)) ||
                            (carrier.state && carrier.state.toLowerCase().includes(search)) ||
                            (carrier.zipcode && carrier.zipcode.toString().includes(search))
                        );
                    }
                    
                    // Filtro por estado
                    if (this.filters.state) {
                        filtered = filtered.filter(carrier => carrier.state === this.filters.state);
                    }
                    
                    // Filtro por disponibilidad
                    if (this.filters.availability === 'available') {
                        filtered = filtered.filter(carrier => !carrier.is_full);
                    } else if (this.filters.availability === 'full') {
                        filtered = filtered.filter(carrier => carrier.is_full);
                    }
                    
                    // Ordenar
                    filtered.sort((a, b) => {
                        let aVal, bVal;
                        
                        if (this.sortColumn === 'name') {
                            aVal = a.name.toLowerCase();
                            bVal = b.name.toLowerCase();
                        } else if (this.sortColumn === 'state') {
                            aVal = (a.state || '') + (a.address || '');
                            bVal = (b.state || '') + (b.address || '');
                        } else if (this.sortColumn === 'drivers') {
                            aVal = a.driver_count / a.max_drivers;
                            bVal = b.driver_count / b.max_drivers;
                        }
                        
                        if (this.sortDirection === 'asc') {
                            return aVal > bVal ? 1 : -1;
                        } else {
                            return aVal < bVal ? 1 : -1;
                        }
                    });
                    
                    this.filteredCarriers = filtered;
                    this.currentPage = 1;
                },
                
                sortBy(column) {
                    if (this.sortColumn === column) {
                        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        this.sortColumn = column;
                        this.sortDirection = 'asc';
                    }
                    this.applyFilters();
                },
                
                get paginatedCarriers() {
                    const start = (this.currentPage - 1) * this.itemsPerPage;
                    const end = start + this.itemsPerPage;
                    return this.filteredCarriers.slice(start, end);
                },
                
                get totalPages() {
                    return Math.ceil(this.filteredCarriers.length / this.itemsPerPage);
                },
                
                get visiblePages() {
                    const pages = [];
                    const maxVisible = 5;
                    let start = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
                    let end = Math.min(this.totalPages, start + maxVisible - 1);
                    
                    if (end - start < maxVisible - 1) {
                        start = Math.max(1, end - maxVisible + 1);
                    }
                    
                    for (let i = start; i <= end; i++) {
                        pages.push(i);
                    }
                    
                    return pages;
                },
                
                nextPage() {
                    if (this.currentPage < this.totalPages) {
                        this.currentPage++;
                    }
                },
                
                previousPage() {
                    if (this.currentPage > 1) {
                        this.currentPage--;
                    }
                },
                
                goToPage(page) {
                    this.currentPage = page;
                }
            }
        }
    </script>
</x-driver-layout>
