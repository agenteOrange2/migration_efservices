@props(['phoneNumber' => '+1 4328535493'])

<div class="box box--stacked flex flex-col p-6 hover:shadow-lg transition-all duration-200 group">
    <div class="flex items-center gap-3 mb-6">
        <div class="p-3 bg-primary/10 rounded-xl border-primary/20 border group-hover:bg-primary/20 transition-colors">
            <x-base.lucide class="w-5 h-5 text-primary" icon="Headphones" />
        </div>
        <h2 class="text-lg font-semibold text-slate-800">Contact Support</h2>
        <x-base.badge variant="primary" class="ml-auto gap-1.5">
            <span class="w-1.5 h-1.5 bg-primary rounded-full"></span>
            24/7
        </x-base.badge>
    </div>

    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100 mb-4">
        <div class="flex items-start gap-3 mb-4">
            <x-base.lucide class="w-4 h-4 text-primary mt-0.5 flex-shrink-0" icon="Phone" />
            <div class="flex-1">
                <p class="text-sm font-medium text-slate-700 leading-relaxed mb-2">
                    Need assistance? Our support team is available 24/7 to help you with any questions or concerns.
                </p>
                <div class="flex flex-col sm:flex-row py-3 items-center gap-2">
                    <x-base.badge variant="soft" class="text-xs">{{ $phoneNumber }}</x-base.badge>                    
                    <div class="flex items-center gap-1 text-xs text-slate-500">
                        <x-base.lucide class="w-3 h-3" icon="Clock" />
                        Available 24/7
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1  lg:grid-cols-3 gap-2">
        <!-- Call Button -->
        <x-base.button 
            as="a" 
            href="tel:{{ $phoneNumber }}" 
            variant="primary" 
            class="flex flex-col items-center justify-center p-2 h-20 group/btn hover:scale-105 transition-all duration-200"
        >
            <x-base.lucide icon="Phone" class="w-5 h-5 mb-2 group-hover/btn:animate-pulse" />
            <span class="text-sm font-medium">Call Now</span>
        </x-base.button>

        <!-- SMS Button -->
        <x-base.button 
            as="a" 
            href="sms:{{ $phoneNumber }}" 
            variant="warning" 
            class="flex flex-col items-center justify-center p-2 h-20 group/btn hover:scale-105 transition-all duration-200"
        >
            <x-base.lucide icon="MessageSquare" class="w-5 h-5 mb-2 group-hover/btn:animate-pulse" />
            <span class="text-sm font-medium">Send SMS</span>
        </x-base.button>

        <!-- WhatsApp Button -->
        <x-base.button 
            as="a" 
            href="https://wa.me/{{ str_replace(['+', ' ', '-'], '', $phoneNumber) }}" 
            target="_blank"
            variant="success"
            class="flex flex-col items-center justify-center p-2 h-20 group/btn hover:scale-105 transition-all duration-200"
        >
            <x-base.lucide icon="MessageCircle" class="w-5 h-5 mb-2 group-hover/btn:animate-pulse" />
            <span class="text-sm font-medium">WhatsApp</span>
        </x-base.button>
    </div>

    <div class="mt-4 pt-2 border-t border-slate-100">
        <div class="flex items-center justify-center gap-4 text-xs text-slate-500">
            <div class="flex items-center gap-1">
                <x-base.lucide class="w-3 h-3" icon="Shield" />
                <span>Secure Support</span>
            </div>            
            <div class="flex items-center gap-1">
                <x-base.lucide class="w-3 h-3" icon="Zap" />
                <span>Fast Response</span>
            </div>            
            <div class="flex items-center gap-1">
                <x-base.lucide class="w-3 h-3" icon="Users" />
                <span>Expert Team</span>
            </div>
        </div>
    </div>
</div>