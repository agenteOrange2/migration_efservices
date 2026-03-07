<div class="mb-6 flex items-center border-b border-dashed border-slate-300/70 pb-5 text-[0.94rem] font-medium">
    Account Deactivation
</div>

<div>
    <!-- Warning Alert -->
    <x-base.alert class="mb-5 flex items-center border-danger/20 bg-danger/5 px-4" variant="outline-danger">
        <div>
            <x-base.lucide class="mr-3 h-5 w-5 stroke-[1.3] md:mr-2 text-danger" icon="AlertTriangle" />
        </div>
        <div class="mr-5 leading-relaxed text-danger">
            <span class="font-medium">Warning:</span> Deactivating your account is a serious action. Please read the information below carefully before proceeding.
        </div>
    </x-base.alert>

    <div class="text-slate-500 mb-6">
        If you no longer wish to use your account, you can deactivate it below. Please note that this action has significant consequences.
    </div>

    <!-- What Happens Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
        <!-- Deactivation Info -->
        <div class="p-5 bg-slate-50 rounded-lg border border-slate-200/80">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-warning/10 rounded-lg">
                    <x-base.lucide class="w-5 h-5 text-warning" icon="PauseCircle" />
                </div>
                <h4 class="font-medium text-slate-800">Account Deactivation</h4>
            </div>
            <ul class="space-y-2 text-sm text-slate-600">
                <li class="flex items-start gap-2">
                    <x-base.lucide class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" icon="Check" />
                    <span>Your profile will be hidden from other users</span>
                </li>
                <li class="flex items-start gap-2">
                    <x-base.lucide class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" icon="Check" />
                    <span>You won't receive any notifications</span>
                </li>
                <li class="flex items-start gap-2">
                    <x-base.lucide class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" icon="Check" />
                    <span>Your data will be preserved</span>
                </li>
                <li class="flex items-start gap-2">
                    <x-base.lucide class="w-4 h-4 text-success mt-0.5 flex-shrink-0" icon="RotateCcw" />
                    <span class="text-success font-medium">You can reactivate anytime by logging in</span>
                </li>
            </ul>
        </div>

        <!-- Deletion Info -->
        <div class="p-5 bg-danger/5 rounded-lg border border-danger/20">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-danger/10 rounded-lg">
                    <x-base.lucide class="w-5 h-5 text-danger" icon="Trash2" />
                </div>
                <h4 class="font-medium text-slate-800">Permanent Deletion</h4>
            </div>
            <ul class="space-y-2 text-sm text-slate-600">
                <li class="flex items-start gap-2">
                    <x-base.lucide class="w-4 h-4 text-danger mt-0.5 flex-shrink-0" icon="X" />
                    <span>All your data will be permanently deleted</span>
                </li>
                <li class="flex items-start gap-2">
                    <x-base.lucide class="w-4 h-4 text-danger mt-0.5 flex-shrink-0" icon="X" />
                    <span>Your documents and files will be removed</span>
                </li>
                <li class="flex items-start gap-2">
                    <x-base.lucide class="w-4 h-4 text-danger mt-0.5 flex-shrink-0" icon="X" />
                    <span>Associated records will be unlinked</span>
                </li>
                <li class="flex items-start gap-2">
                    <x-base.lucide class="w-4 h-4 text-danger mt-0.5 flex-shrink-0" icon="AlertTriangle" />
                    <span class="text-danger font-medium">This action cannot be undone</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Reason for Leaving -->
    <div class="p-5 bg-slate-50 rounded-lg border border-slate-200/80 mb-6">
        <h4 class="font-medium text-slate-800 mb-4">Before you go, please tell us why (optional)</h4>
        <div class="space-y-3">
            <label class="flex items-center gap-3 cursor-pointer">
                <x-base.form-check.input type="radio" name="deactivation_reason" value="not_using" />
                <span class="text-slate-600">I'm not using the service anymore</span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer">
                <x-base.form-check.input type="radio" name="deactivation_reason" value="found_alternative" />
                <span class="text-slate-600">I found a better alternative</span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer">
                <x-base.form-check.input type="radio" name="deactivation_reason" value="too_expensive" />
                <span class="text-slate-600">The service is too expensive</span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer">
                <x-base.form-check.input type="radio" name="deactivation_reason" value="privacy" />
                <span class="text-slate-600">Privacy concerns</span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer">
                <x-base.form-check.input type="radio" name="deactivation_reason" value="other" />
                <span class="text-slate-600">Other reason</span>
            </label>
        </div>
        <div class="mt-4">
            <x-base.form-label>Additional comments</x-base.form-label>
            <x-base.form-textarea rows="3" placeholder="Tell us more about your experience..." />
        </div>
    </div>

    <!-- Confirmation Section -->
    <div class="p-5 bg-slate-50 rounded-lg border border-slate-200/80">
        <h4 class="font-medium text-slate-800 mb-4">Confirm Account Deactivation</h4>
        <p class="text-sm text-slate-500 mb-4">
            To deactivate your account, please enter your password and type "DEACTIVATE" to confirm.
        </p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-base.form-label>Current Password</x-base.form-label>
                <x-base.form-input type="password" placeholder="Enter your password" />
            </div>
            <div>
                <x-base.form-label>Type "DEACTIVATE" to confirm</x-base.form-label>
                <x-base.form-input type="text" placeholder="DEACTIVATE" />
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="mt-6 flex flex-col sm:flex-row gap-3 border-t border-dashed border-slate-300/70 pt-5 md:justify-end">
        <x-base.button class="w-full sm:w-auto" variant="outline-secondary">
            <x-base.lucide class="w-4 h-4 mr-2" icon="ArrowLeft" />
            Cancel
        </x-base.button>
        <x-base.button class="w-full sm:w-auto" variant="danger" disabled>
            <x-base.lucide class="w-4 h-4 mr-2" icon="Trash2" />
            Deactivate Account
        </x-base.button>
    </div>
</div>
