<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { FormInput, FormLabel } from '@/components/Base/Form'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

defineOptions({ layout: RazeLayout })

interface CarrierData {
    id: number
    name: string
    slug: string
    dot_number: string | null
    mc_number: string | null
    custom_safety_url: string | null
    status: number
    safety_data_system_url: string | null
    auto_generated_safety_url: string | null
    has_custom_url: boolean
    safety_image_url: string | null
    has_safety_image: boolean
}

const props = defineProps<{
    carrier: CarrierData
}>()

const urlForm = useForm({
    custom_safety_url: props.carrier.custom_safety_url ?? '',
})

const imageFile = ref<File | null>(null)
const imagePreview = ref<string | null>(null)
const uploading = ref(false)

function submitUrl() {
    urlForm.put(route('admin.carriers.safety-data-system.update', props.carrier.slug), {
        preserveScroll: true,
    })
}

function handleImageChange(e: Event) {
    const target = e.target as HTMLInputElement
    const file = target.files?.[0]
    if (file) {
        imageFile.value = file
        const reader = new FileReader()
        reader.onload = (ev) => {
            imagePreview.value = ev.target?.result as string
        }
        reader.readAsDataURL(file)
    }
}

function uploadImage() {
    if (!imageFile.value) return
    uploading.value = true

    const formData = new FormData()
    formData.append('safety_image', imageFile.value)

    router.post(route('admin.carriers.safety-data-system.upload', props.carrier.slug), formData as any, {
        preserveScroll: true,
        onFinish: () => {
            uploading.value = false
            imageFile.value = null
            imagePreview.value = null
        },
    })
}

function deleteImage() {
    if (confirm('Are you sure you want to delete the Safety Data System image?')) {
        router.delete(route('admin.carriers.safety-data-system.delete', props.carrier.slug), {
            preserveScroll: true,
        })
    }
}

const displayImageUrl = computed(() => imagePreview.value || props.carrier.safety_image_url)
</script>

<template>
    <Head :title="`Safety Data System - ${carrier.name}`" />

    <div class="grid grid-cols-12 gap-y-10 gap-x-6">
        <!-- Header -->
        <div class="col-span-12">
            <div class="flex flex-col md:h-10 gap-y-3 md:items-center md:flex-row">
                <div class="text-base font-medium">
                    <Link :href="route('admin.carriers.index')" class="text-primary hover:underline">Carriers</Link>
                    <span class="mx-2 text-slate-400">/</span>
                    <Link :href="route('admin.carriers.show', carrier.slug)" class="text-primary hover:underline">{{ carrier.name }}</Link>
                    <span class="mx-2 text-slate-400">/</span>
                    Safety Data System
                </div>
                <div class="flex flex-col sm:flex-row gap-x-3 gap-y-2 md:ml-auto">
                    <Link :href="route('admin.carriers.show', carrier.slug)">
                        <Button variant="secondary" class="w-full sm:w-auto">
                            <Lucide icon="ArrowLeft" class="w-4 h-4 mr-2" /> Back to Details
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Page Header Card -->
        <div class="col-span-12">
            <div class="box box--stacked p-8">
                <div class="flex flex-col lg:flex-row items-start lg:items-center gap-6">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <Lucide icon="Shield" class="w-8 h-8 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-2">Safety Data System</h1>
                        <p class="text-slate-600">Manage safety data system configuration for <strong>{{ carrier.name }}</strong></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4">
            <!-- Carrier Info -->
            <div class="box box--stacked p-6 mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <Lucide icon="Info" class="w-5 h-5 text-primary" />
                    <h2 class="text-lg font-semibold text-slate-800">Carrier Information</h2>
                </div>

                <div class="space-y-4">
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Name</label>
                        <p class="text-sm font-semibold text-slate-800">{{ carrier.name }}</p>
                    </div>

                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">DOT Number</label>
                        <p class="text-sm font-semibold text-slate-800">{{ carrier.dot_number ?? 'N/A' }}</p>
                    </div>

                    <!-- Custom URL Form -->
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Manage URL</label>
                        <form @submit.prevent="submitUrl" class="space-y-3">
                            <div>
                                <FormLabel class="text-xs">Custom URL (optional)</FormLabel>
                                <FormInput
                                    v-model="urlForm.custom_safety_url"
                                    type="url"
                                    placeholder="https://example.com/safety-data"
                                />
                                <p class="mt-1 text-xs text-slate-500">
                                    Leave blank to use the auto-generated URL with the DOT Number
                                </p>
                                <p v-if="urlForm.errors.custom_safety_url" class="mt-1 text-xs text-red-500">
                                    {{ urlForm.errors.custom_safety_url }}
                                </p>
                            </div>
                            <Button type="submit" variant="primary" size="sm" class="w-full" :disabled="urlForm.processing">
                                <Lucide icon="Save" class="w-4 h-4 mr-1" /> Save URL
                            </Button>
                        </form>
                    </div>

                    <!-- Active URL Display -->
                    <div v-if="carrier.dot_number" class="bg-blue-50/50 rounded-lg p-4 border border-blue-100">
                        <label class="text-xs font-medium text-blue-600 uppercase tracking-wide mb-2 block">
                            {{ carrier.has_custom_url ? 'Active URL (Custom)' : 'Active URL (Automatic)' }}
                        </label>

                        <div v-if="carrier.has_custom_url" class="mb-2 px-2 py-1 bg-warning/10 border border-warning/20 rounded text-xs text-amber-700 flex items-center gap-1">
                            <Lucide icon="AlertCircle" class="w-3 h-3" />
                            Using custom URL
                        </div>

                        <div class="break-all mb-2">
                            <a :href="carrier.safety_data_system_url!" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 underline">
                                {{ carrier.safety_data_system_url }}
                            </a>
                        </div>

                        <div v-if="carrier.has_custom_url && carrier.auto_generated_safety_url" class="mt-3 pt-3 border-t border-blue-200">
                            <label class="text-xs font-medium text-slate-500 mb-1 block">Automatic URL (not in use)</label>
                            <p class="text-xs text-slate-600 break-all">{{ carrier.auto_generated_safety_url }}</p>
                        </div>

                        <div class="mt-3">
                            <a :href="carrier.safety_data_system_url!" target="_blank" class="inline-flex items-center gap-2 w-full justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition text-sm font-medium">
                                <Lucide icon="ExternalLink" class="w-4 h-4" /> View in FMCSA
                            </a>
                        </div>
                    </div>

                    <div v-else class="bg-yellow-50/50 rounded-lg p-4 border border-yellow-100">
                        <div class="flex items-start gap-2">
                            <Lucide icon="AlertTriangle" class="w-5 h-5 text-yellow-600 mt-0.5" />
                            <div>
                                <p class="text-sm font-medium text-yellow-800">DOT Number Required</p>
                                <p class="text-xs text-yellow-700 mt-1">This carrier needs a DOT Number to generate the Safety Data System URL.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Management -->
        <div class="col-span-12 lg:col-span-8">
            <div class="box box--stacked p-6">
                <div class="flex items-center gap-3 mb-6">
                    <Lucide icon="Image" class="w-5 h-5 text-primary" />
                    <h2 class="text-lg font-semibold text-slate-800">Safety Data System Image</h2>
                </div>

                <!-- Current Image -->
                <div v-if="carrier.has_safety_image" class="mb-6">
                    <label class="block text-sm font-medium text-slate-700 mb-3">Current Image</label>
                    <div class="relative inline-block">
                        <img
                            :src="carrier.safety_image_url!"
                            alt="Safety Data System"
                            class="w-full max-w-md rounded-lg border-2 border-slate-200 shadow-sm"
                        />
                        <button
                            @click="deleteImage"
                            class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-danger text-white rounded-lg hover:bg-danger/90 transition text-sm font-medium"
                        >
                            <Lucide icon="Trash2" class="w-4 h-4" /> Delete Image
                        </button>
                    </div>
                </div>

                <div v-else class="mb-6 bg-slate-50 rounded-lg p-8 border-2 border-dashed border-slate-200">
                    <div class="text-center">
                        <Lucide icon="ImageOff" class="w-16 h-16 text-slate-400 mx-auto mb-3" />
                        <p class="text-sm text-slate-600">No safety data system image</p>
                        <p class="text-xs text-slate-500 mt-1">Upload an image to appear in the dashboard and carrier profile</p>
                    </div>
                </div>

                <!-- Upload Form -->
                <div class="border-t pt-6">
                    <div class="mb-6">
                        <FormLabel>
                            {{ carrier.has_safety_image ? 'Change Image' : 'Upload Image' }}
                        </FormLabel>
                        <input
                            type="file"
                            accept="image/*"
                            @change="handleImageChange"
                            class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary/90 cursor-pointer border border-slate-300 rounded-lg"
                        />
                        <p class="mt-2 text-xs text-slate-500">
                            Allowed formats: JPG, PNG, GIF, WebP. Maximum size: 2MB
                        </p>
                    </div>

                    <!-- Preview before upload -->
                    <div v-if="imagePreview" class="mb-6">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Preview</label>
                        <img :src="imagePreview" alt="Preview" class="max-w-xs rounded-lg border-2 border-primary/30 shadow-sm" />
                    </div>

                    <div class="flex items-center gap-3">
                        <Button
                            variant="primary"
                            @click="uploadImage"
                            :disabled="!imageFile || uploading"
                        >
                            <Lucide icon="Upload" class="w-4 h-4 mr-2" />
                            {{ carrier.has_safety_image ? 'Update Image' : 'Upload Image' }}
                        </Button>
                    </div>
                </div>

                <!-- Card Preview -->
                <div v-if="carrier.has_safety_image || carrier.dot_number" class="border-t pt-6 mt-6">
                    <label class="block text-sm font-medium text-slate-700 mb-3">Preview — How it will look for the Carrier</label>

                    <div class="bg-gradient-to-br from-slate-50 to-slate-100 rounded-lg p-6 border border-slate-200">
                        <div class="bg-white rounded-xl shadow-lg overflow-hidden max-w-sm">
                            <div class="relative h-48 bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center">
                                <img
                                    :src="displayImageUrl ?? ''"
                                    alt="Safety Data System"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            <div class="p-6">
                                <h3 class="text-lg font-bold text-slate-800 mb-1">{{ carrier.name }}</h3>
                                <p class="text-sm text-slate-500 mb-4">Safety Data System</p>
                                <div class="inline-flex items-center gap-2 w-full justify-center px-4 py-2 bg-primary/80 text-white rounded-lg text-sm font-medium cursor-not-allowed opacity-75">
                                    <Lucide icon="Shield" class="w-4 h-4" /> Consulting Safety
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
