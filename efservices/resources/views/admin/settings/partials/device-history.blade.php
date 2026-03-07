<div class="mb-6 flex items-center border-b border-dashed border-slate-300/70 pb-5 text-[0.94rem] font-medium">
    Device History
</div>

<div>
    <div class="text-slate-500 mb-5">
        Access and control your currently connected devices. If you see any suspicious activity, you can log out from all devices.
    </div>

    <!-- Current Session -->
    <div class="p-5 bg-primary/5 rounded-lg border border-primary/20 mb-5">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-primary/10 rounded-lg">
                <x-base.lucide class="w-6 h-6 text-primary" icon="Monitor" />
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <h4 class="font-medium text-slate-800">Current Session</h4>
                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-primary/10 text-primary">Active Now</span>
                </div>
                <p class="text-sm text-slate-500 mt-1">
                    {{ request()->header('User-Agent') ? Str::limit(request()->header('User-Agent'), 60) : 'Unknown Browser' }}
                </p>
                <p class="text-xs text-slate-400 mt-1">
                    IP: {{ request()->ip() }} • Last active: Just now
                </p>
            </div>
        </div>
    </div>

    <!-- Device List -->
    <div class="rounded-lg border border-slate-200/80 overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="bg-slate-50">
                    <th class="py-4 px-5 text-left font-medium text-slate-500">Browser / Device</th>
                    <th class="py-4 px-5 text-left font-medium text-slate-500">Location</th>
                    <th class="py-4 px-5 text-left font-medium text-slate-500">Last Activity</th>
                    <th class="py-4 px-5 text-center font-medium text-slate-500">Status</th>
                </tr>
            </thead>
            <tbody>
                <!-- Sample Device 1 - Current -->
                <tr class="border-t border-slate-200/60 hover:bg-slate-50/50">
                    <td class="py-4 px-5">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-slate-100 rounded-lg">
                                <x-base.lucide class="w-5 h-5 text-slate-600" icon="Chrome" />
                            </div>
                            <div>
                                <div class="font-medium text-slate-800">Chrome on Windows</div>
                                <div class="text-xs text-slate-500">Desktop</div>
                            </div>
                        </div>
                    </td>
                    <td class="py-4 px-5">
                        <div class="text-slate-600">Los Angeles, CA</div>
                        <div class="text-xs text-slate-400">United States</div>
                    </td>
                    <td class="py-4 px-5">
                        <div class="text-slate-600">Just now</div>
                    </td>
                    <td class="py-4 px-5 text-center">
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-success/10 text-success">Current</span>
                    </td>
                </tr>

                <!-- Sample Device 2 -->
                <tr class="border-t border-slate-200/60 hover:bg-slate-50/50">
                    <td class="py-4 px-5">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-slate-100 rounded-lg">
                                <x-base.lucide class="w-5 h-5 text-slate-600" icon="Smartphone" />
                            </div>
                            <div>
                                <div class="font-medium text-slate-800">Safari on iPhone</div>
                                <div class="text-xs text-slate-500">Mobile</div>
                            </div>
                        </div>
                    </td>
                    <td class="py-4 px-5">
                        <div class="text-slate-600">Los Angeles, CA</div>
                        <div class="text-xs text-slate-400">United States</div>
                    </td>
                    <td class="py-4 px-5">
                        <div class="text-slate-600">2 hours ago</div>
                    </td>
                    <td class="py-4 px-5 text-center">
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-slate-200 text-slate-600">Active</span>
                    </td>
                </tr>

                <!-- Sample Device 3 -->
                <tr class="border-t border-slate-200/60 hover:bg-slate-50/50">
                    <td class="py-4 px-5">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-slate-100 rounded-lg">
                                <x-base.lucide class="w-5 h-5 text-slate-600" icon="Tablet" />
                            </div>
                            <div>
                                <div class="font-medium text-slate-800">Chrome on iPad</div>
                                <div class="text-xs text-slate-500">Tablet</div>
                            </div>
                        </div>
                    </td>
                    <td class="py-4 px-5">
                        <div class="text-slate-600">San Francisco, CA</div>
                        <div class="text-xs text-slate-400">United States</div>
                    </td>
                    <td class="py-4 px-5">
                        <div class="text-slate-600">3 days ago</div>
                    </td>
                    <td class="py-4 px-5 text-center">
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-slate-200 text-slate-600">Inactive</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Logout All Devices -->
    <div class="mt-6 flex border-t border-dashed border-slate-300/70 pt-5 md:justify-end">
        <x-base.button class="w-full md:w-auto" variant="outline-danger">
            <x-base.lucide class="w-4 h-4 mr-2" icon="LogOut" />
            Log Out All Other Devices
        </x-base.button>
    </div>
</div>
