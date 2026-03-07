@auth
    @livewire('quick-search')
@else
    {{-- Fallback for unauthenticated users --}}
    <div
        id="quick-search"
        aria-hidden="true"
        tabindex="-1"
        @class([
            'modal group bg-gradient-to-b from-theme-1/50 via-theme-2/50 to-black/50 transition-[visibility,opacity] w-screen h-screen fixed left-0 top-0 overflow-y-hidden z-[60]',
            '[&:not(.show)]:duration-[0s,0.2s] [&:not(.show)]:delay-[0.2s,0s] [&:not(.show)]:invisible [&:not(.show)]:opacity-0',
            '[&.show]:visible [&.show]:opacity-100 [&.show]:duration-[0s,0.1s]',
        ])
    >
        <div class="relative mx-auto my-2 w-[95%] scale-95 transition-transform group-[.show]:scale-100 sm:mt-40 sm:w-[600px] lg:w-[700px]">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 flex w-12 items-center justify-center">
                    <x-base.lucide class="-mr-1.5 h-5 w-5 stroke-[1] text-slate-500" icon="Search" />
                </div>
                <x-base.form-input
                    class="rounded-lg border-0 py-3.5 pl-12 pr-14 text-base shadow-lg focus:ring-0"
                    type="text"
                    placeholder="Please login to search..."
                    disabled
                />
                <div class="absolute inset-y-0 right-0 flex w-14 items-center">
                    <div class="mr-auto rounded-[0.4rem] border bg-slate-100 px-2 py-1 text-xs text-slate-500/80">
                        ESC
                    </div>
                </div>
            </div>
            <div class="global-search group relative z-10 mt-1 rounded-lg bg-white pb-1 shadow-lg">
                <div class="flex flex-col items-center justify-center pb-10 pt-10">
                    <x-base.lucide class="h-16 w-16 fill-theme-1/5 stroke-[0.5] text-theme-1/20" icon="Lock" />
                    <div class="mt-4 text-lg font-medium">Authentication Required</div>
                    <div class="mt-2 text-center text-slate-500">
                        Please <a href="{{ route('login') }}" class="text-theme-1 hover:underline">login</a> to use the search feature.
                    </div>
                </div>
            </div>
        </div>
    </div>

    @pushOnce('vendors')
        @vite('resources/js/vendors/modal.js')
    @endPushOnce

    @pushOnce('scripts')
        @vite('resources/js/components/quick-search.js')
    @endPushOnce
@endauth
