<div>
    @if($assignment)
        <x-base.dialog :open="true" staticBackdrop>
            <x-base.dialog.panel class="max-w-lg">
                <x-base.dialog.title class="border-b border-slate-200 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-success/10">
                            <x-base.lucide class="w-5 h-5 text-success" icon="CheckCircle2" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-800">Complete Training</h3>
                            <p class="text-sm text-slate-500">Confirm your training completion</p>
                        </div>
                    </div>
                </x-base.dialog.title>
                
                <x-base.dialog.description class="py-6 space-y-4">
                    {{-- Training Info --}}
                    <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                        <h4 class="font-semibold text-slate-800 mb-1">{{ $assignment->training->title }}</h4>
                        @if($assignment->training->description)
                            <p class="text-sm text-slate-600 line-clamp-2">{{ $assignment->training->description }}</p>
                        @endif
                    </div>

                    {{-- Error Messages --}}
                    @if($errors->any())
                        <div class="bg-danger/10 border border-danger/20 text-danger rounded-lg p-3 text-sm">
                            <div class="flex items-start gap-2">
                                <x-base.lucide class="w-4 h-4 flex-shrink-0 mt-0.5" icon="AlertCircle" />
                                <div>
                                    @foreach($errors->all() as $error)
                                        <p>{{ $error }}</p>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Confirmation Checkbox --}}
                    <div class="space-y-3">
                        <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg border-2 {{ $confirmed ? 'border-success bg-success/5' : 'border-slate-200 hover:border-slate-300' }} transition-colors">
                            <input type="checkbox" 
                                wire:model.live="confirmed" 
                                class="mt-1 rounded border-slate-300 text-success focus:ring-success focus:ring-offset-0 cursor-pointer w-5 h-5">
                            <div class="flex-1">
                                <div class="font-medium text-slate-800">I confirm that I have completed this training</div>
                                <div class="text-sm text-slate-600 mt-1">
                                    By checking this box, you acknowledge that you have reviewed all training materials and completed the required content.
                                </div>
                            </div>
                        </label>
                    </div>

                    {{-- Optional Notes --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            Notes (Optional)
                        </label>
                        <textarea 
                            wire:model="notes" 
                            rows="3" 
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm resize-none"
                            placeholder="Add any notes or comments about your training completion..."
                            maxlength="500"></textarea>
                        <div class="text-xs text-slate-500 mt-1 text-right">
                            {{ strlen($notes) }}/500 characters
                        </div>
                    </div>
                </x-base.dialog.description>
                
                <x-base.dialog.footer class="border-t border-slate-200 pt-4">
                    <div class="flex gap-3 justify-end w-full">
                        <button 
                            type="button" 
                            wire:click="closeModal"
                            class="px-6 py-2.5 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors font-medium min-h-[44px]">
                            Cancel
                        </button>
                        <button 
                            type="button" 
                            wire:click="completeTraining"
                            :disabled="!$confirmed"
                            class="px-6 py-2.5 bg-success text-white rounded-lg hover:bg-success/90 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 min-h-[44px]">
                            <x-base.lucide class="w-4 h-4" icon="CheckCircle2" />
                            Complete Training
                        </button>
                    </div>
                </x-base.dialog.footer>
            </x-base.dialog.panel>
        </x-base.dialog>
    @endif
</div>

