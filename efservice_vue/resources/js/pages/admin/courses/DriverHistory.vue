<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    driver: { id: number; name: string; email?: string | null; carrier_name?: string | null; photo_url?: string | null }
    courses: { data: any[]; links: { url: string | null; label: string; active: boolean }[]; total: number; last_page: number }
    filters: { search_term: string; status: string; sort_field: string; sort_direction: string }
    stats: { total: number; active: number; inactive: number; expiring_soon: number }
}>()

const filters = reactive({ ...props.filters })

function applyFilters() {
    router.get(route('admin.drivers.course-history', props.driver.id), {
        search_term: filters.search_term || undefined,
        status: filters.status || undefined,
        sort_field: filters.sort_field || undefined,
        sort_direction: filters.sort_direction || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    filters.search_term = ''
    filters.status = ''
    filters.sort_field = 'certification_date'
    filters.sort_direction = 'desc'
    applyFilters()
}

function deleteCourse(course: any) {
    if (!confirm(`Delete "${course.organization_name}"?`)) return
    router.delete(route('admin.courses.destroy', course.id), { preserveScroll: true })
}
</script>

<template>
    <Head :title="`${driver.name} Course History`" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-full overflow-hidden bg-slate-100 flex items-center justify-center shrink-0">
                            <img v-if="driver.photo_url" :src="driver.photo_url" :alt="driver.name" class="w-full h-full object-cover" />
                            <Lucide v-else icon="User" class="w-8 h-8 text-slate-400" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">{{ driver.name }}</h1>
                            <p class="text-slate-500">{{ driver.carrier_name || 'No carrier assigned' }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="route('admin.courses.create', { driver_id: driver.id })">
                            <Button variant="primary" class="flex items-center gap-2">
                                <Lucide icon="Plus" class="w-4 h-4" />
                                Add Course
                            </Button>
                        </Link>
                        <Link :href="route('admin.drivers.show', driver.id)">
                            <Button variant="outline-primary" class="flex items-center gap-2">
                                <Lucide icon="User" class="w-4 h-4" />
                                Driver Profile
                            </Button>
                        </Link>
                        <Link :href="route('admin.courses.index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ArrowLeft" class="w-4 h-4" />
                                Back
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Total Courses</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.total }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Active</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.active }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Inactive</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.inactive }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Expiring Soon</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.expiring_soon }}</p></div>
            </div>

            <div class="box box--stacked p-5 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                    <div class="relative"><Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" /><input v-model="filters.search_term" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 pl-10 text-sm" placeholder="Search organization..." /></div>
                    <TomSelect v-model="filters.status"><option value="">All Statuses</option><option value="active">Active</option><option value="inactive">Inactive</option></TomSelect>
                    <TomSelect v-model="filters.sort_field"><option value="certification_date">Certification Date</option><option value="expiration_date">Expiration Date</option><option value="organization_name">Organization</option></TomSelect>
                    <TomSelect v-model="filters.sort_direction"><option value="desc">Newest First</option><option value="asc">Oldest First</option></TomSelect>
                </div>

                <div class="flex flex-wrap items-center gap-3 mt-4">
                    <button type="button" @click="applyFilters" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition"><Lucide icon="Filter" class="w-4 h-4" />Apply Filters</button>
                    <button type="button" @click="resetFilters" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition"><Lucide icon="RotateCcw" class="w-4 h-4" />Reset</button>
                </div>
            </div>

            <div class="box box--stacked p-0 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Organization</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Location</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Certification</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Expiration</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Status</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Documents</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="course in courses.data" :key="course.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4"><div class="font-medium text-slate-800">{{ course.organization_name }}</div><div v-if="course.experience" class="text-xs text-slate-400">{{ course.experience }}</div></td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ [course.city, course.state].filter(Boolean).join(', ') || 'N/A' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ course.certification_date ?? 'N/A' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ course.expiration_date ?? 'N/A' }}</td>
                                <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="course.status === 'active' ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-slate-600'">{{ course.status }}</span></td>
                                <td class="px-5 py-4"><span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ course.document_count }} document<span v-if="course.document_count !== 1">s</span></span></td>
                                <td class="px-5 py-4"><div class="flex items-center justify-center gap-2"><Link :href="route('admin.courses.documents', course.id)" class="p-1.5 text-slate-400 hover:text-primary transition"><Lucide icon="Files" class="w-4 h-4" /></Link><Link :href="route('admin.courses.edit', course.id)" class="p-1.5 text-slate-400 hover:text-primary transition"><Lucide icon="PenLine" class="w-4 h-4" /></Link><button type="button" @click="deleteCourse(course)" class="p-1.5 text-slate-400 hover:text-red-500 transition"><Lucide icon="Trash2" class="w-4 h-4" /></button></div></td>
                            </tr>
                            <tr v-if="!courses.data.length"><td colspan="7" class="px-5 py-12 text-center text-slate-400"><Lucide icon="ShieldCheck" class="w-12 h-12 mx-auto mb-3 text-slate-300" /><p>No course history found</p></td></tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="courses.last_page > 1" class="p-4 border-t border-slate-200/60 flex items-center justify-between">
                    <span class="text-sm text-slate-500">{{ courses.total }} total records</span>
                    <div class="flex gap-1"><template v-for="link in courses.links" :key="link.label"><Link v-if="link.url" :href="link.url" class="px-3 py-1 text-sm rounded" :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'" v-html="link.label" /><span v-else class="px-3 py-1 text-sm text-slate-300" v-html="link.label" /></template></div>
                </div>
            </div>
        </div>
    </div>
</template>
