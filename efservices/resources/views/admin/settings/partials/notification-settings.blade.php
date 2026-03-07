<div class="mb-6 flex items-center border-b border-dashed border-slate-300/70 pb-5 text-[0.94rem] font-medium">
    Notification Settings
</div>

<div>
    <x-base.alert class="mb-5 flex items-center border-primary/20 bg-primary/5 px-4" variant="outline-primary">
        <div>
            <x-base.lucide class="mr-3 h-4 w-4 stroke-[1.3] md:mr-2" icon="AlertCircle" />
        </div>
        <div class="mr-5 leading-relaxed">
            We'd like to request your browser's permission to display notifications.
            <a class="ml-1 font-medium underline decoration-primary/50 decoration-dotted underline-offset-[3px] cursor-pointer" onclick="requestNotificationPermission()">
                Request permission
            </a>
        </div>
    </x-base.alert>

    <div class="text-slate-500 mb-5">
        Choose how you want to receive notifications. You can customize notifications for different types of activities.
    </div>

    <!-- Notification Types Table -->
    <div class="rounded-lg border border-slate-200/80 overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="bg-slate-50">
                    <th class="py-4 px-5 text-left font-medium text-slate-500">Notification Type</th>
                    <th class="py-4 px-5 text-center font-medium text-slate-500">
                        <div class="flex flex-col items-center">
                            <x-base.lucide class="w-5 h-5 mb-1" icon="Mail" />
                            <span>Email</span>
                        </div>
                    </th>
                    <th class="py-4 px-5 text-center font-medium text-slate-500">
                        <div class="flex flex-col items-center">
                            <x-base.lucide class="w-5 h-5 mb-1" icon="Globe" />
                            <span>Browser</span>
                        </div>
                    </th>
                    <th class="py-4 px-5 text-center font-medium text-slate-500">
                        <div class="flex flex-col items-center">
                            <x-base.lucide class="w-5 h-5 mb-1" icon="Smartphone" />
                            <span>App</span>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
                <!-- Security Notifications -->
                <tr class="border-t border-slate-200/60">
                    <td class="py-4 px-5" colspan="4">
                        <div class="font-medium text-slate-800">Security</div>
                    </td>
                </tr>
                <tr class="border-t border-slate-100 hover:bg-slate-50/50">
                    <td class="py-3 px-5 pl-8">
                        <div class="text-slate-600">Unusual login activity detected</div>
                    </td>
                    <td class="py-3 px-5 text-center">
                        <x-base.form-check.input type="checkbox" checked />
                    </td>
                    <td class="py-3 px-5 text-center">
                        <x-base.form-check.input type="checkbox" checked />
                    </td>
                    <td class="py-3 px-5 text-center">
                        <x-base.form-check.input type="checkbox" checked />
                    </td>
                </tr>
                <tr class="border-t border-slate-100 hover:bg-slate-50/50">
                    <td class="py-3 px-5 pl-8">
                        <div class="text-slate-600">Password changed</div>
                    </td>
                    <td class="py-3 px-5 text-center">
                        <x-base.form-check.input type="checkbox" checked />
                    </td>
                    <td class="py-3 px-5 text-center">
                        <x-base.form-check.input type="checkbox" />
                    </td>
                    <td class="py-3 px-5 text-center">
                        <x-base.form-check.input type="checkbox" checked />
                    </td>
                </tr>
                <tr class="border-t border-slate-100 hover:bg-slate-50/50">
                    <td class="py-3 px-5 pl-8">
                        <div class="text-slate-600">New device login</div>
                    </td>
                    <td class="py-3 px-5 text-center">
                        <x-base.form-check.input type="checkbox" checked />
                    </td>
                    <td class="py-3 px-5 text-center">
                        <x-base.form-check.input type="checkbox" checked />
                    </td>
                    <td class="py-3 px-5 text-center">
                        <x-base.form-check.input type="checkbox" checked />
                    </td>
                </tr>

                <!-- Account Notifications -->
                <tr class="border-t border-slate-200/60">
                    <td class="py-4 px-5" colspan="4">
                        <div class="font-medium text-slate-800">Account</div>
                    </td>
                </tr>
                <tr class="border-t border-slate-100 hover:bg-slate-50/50">
                    <td class="py-3 px-5 pl-8">
                        <div class="text-slate-600">Profile updates</div>
                    </td>
                    <td class="py-3 px-5 text-center">
                        <x-base.form-check.input type="checkbox" />
                    </td>
                    <td class="py-3 px-5 text-center">
                        <x-base.form-check.input type="checkbox" />
                    </td>
                    <td class="py-3 px-5 text-center">
                        <x-base.form-check.input type="checkbox" />
                    </td>
                </tr>
                <tr class="border-t border-slate-100 hover:bg-slate-50/50">
                    <td class="py-3 px-5 pl-8">
                        <div class="text-slate-600">Membership updates</div>
                    </td>
                    <td class="py-3 px-5 text-center">
                        <x-base.form-check.input type="checkbox" checked />
                    </td>
                    <td class="py-3 px-5 text-center">
                        <x-base.form-check.input type="checkbox" />
                    </td>
                    <td class="py-3 px-5 text-center">
                        <x-base.form-check.input type="checkbox" />
                    </td>
                </tr>

                <!-- System Notifications -->
                <tr class="border-t border-slate-200/60">
                    <td class="py-4 px-5" colspan="4">
                        <div class="font-medium text-slate-800">System</div>
                    </td>
                </tr>
                <tr class="border-t border-slate-100 hover:bg-slate-50/50">
                    <td class="py-3 px-5 pl-8">
                        <div class="text-slate-600">Document expiration reminders</div>
                    </td>
                    <td class="py-3 px-5 text-center">
                        <x-base.form-check.input type="checkbox" checked />
                    </td>
                    <td class="py-3 px-5 text-center">
                        <x-base.form-check.input type="checkbox" checked />
                    </td>
                    <td class="py-3 px-5 text-center">
                        <x-base.form-check.input type="checkbox" checked />
                    </td>
                </tr>
                <tr class="border-t border-slate-100 hover:bg-slate-50/50">
                    <td class="py-3 px-5 pl-8">
                        <div class="text-slate-600">Maintenance reminders</div>
                    </td>
                    <td class="py-3 px-5 text-center">
                        <x-base.form-check.input type="checkbox" checked />
                    </td>
                    <td class="py-3 px-5 text-center">
                        <x-base.form-check.input type="checkbox" />
                    </td>
                    <td class="py-3 px-5 text-center">
                        <x-base.form-check.input type="checkbox" />
                    </td>
                </tr>
                <tr class="border-t border-slate-100 hover:bg-slate-50/50">
                    <td class="py-3 px-5 pl-8">
                        <div class="text-slate-600">System updates and announcements</div>
                    </td>
                    <td class="py-3 px-5 text-center">
                        <x-base.form-check.input type="checkbox" checked />
                    </td>
                    <td class="py-3 px-5 text-center">
                        <x-base.form-check.input type="checkbox" />
                    </td>
                    <td class="py-3 px-5 text-center">
                        <x-base.form-check.input type="checkbox" />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Save Button -->
    <div class="mt-6 flex border-t border-dashed border-slate-300/70 pt-5 md:justify-end">
        <x-base.button class="w-full border-primary/50 px-4 md:w-auto" variant="outline-primary">
            Save Changes
        </x-base.button>
    </div>
</div>

<script>
function requestNotificationPermission() {
    if ('Notification' in window) {
        Notification.requestPermission().then(function(permission) {
            if (permission === 'granted') {
                alert('Notifications enabled!');
            }
        });
    }
}
</script>
