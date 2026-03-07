<div x-data="{ 
        open: @entangle('showModal'),
        init() {
            this.$watch('open', value => {
                if (value) {
                    setTimeout(() => initializeTripSignatureCanvas(), 200);
                }
            });
        }
    }"
    x-show="open"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    :class="{ 'show': open }"
    class="fixed inset-0 z-[9999] overflow-y-auto"
    style="display: none;"
    @keydown.escape.window="$wire.closeModal()">
    
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gradient-to-b from-theme-1/50 via-theme-2/50 to-black/50" @click="$wire.closeModal()"></div>
    
    <!-- Panel Container -->
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <!-- Panel -->
        <div 
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-lg bg-white relative rounded-xl shadow-2xl max-h-[90vh] overflow-y-auto"
            @click.stop>
        
        <!-- Title -->
        <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-200/60">
            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary/10">
                <x-base.lucide class="w-5 h-5 text-primary" icon="PenTool" />
            </div>
            <div>
                <h3 class="text-lg font-semibold text-slate-800">Sign Trip Reports</h3>
                <p class="text-sm text-slate-500">{{ $trip->trip_number ?? 'Trip #' . $trip->id }}</p>
            </div>
            <button type="button" wire:click="closeModal" class="ml-auto text-slate-400 hover:text-slate-600">
                <x-base.lucide class="w-5 h-5" icon="X" />
            </button>
        </div>

        <!-- Description/Body -->
        <div class="px-5 py-4">
            <!-- Info about reports -->
            <div class="bg-primary/5 border border-primary/20 rounded-lg p-4 mb-5">
                <div class="flex items-start gap-3">
                    <x-base.lucide class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" icon="Info" />
                    <div class="text-sm">
                        <p class="font-medium text-slate-800 mb-1">The following reports will be generated:</p>
                        <ul class="text-slate-600 list-disc list-inside space-y-0.5">
                            <li>Trip Summary Report</li>
                            <li>Pre-Trip Vehicle Inspection Report</li>
                            <li>Post-Trip Vehicle Inspection Report</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Signature Canvas -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-2">Driver Signature</label>
                <div class="border-2 border-slate-300 rounded-lg bg-white overflow-hidden">
                    <canvas id="tripSignatureCanvas" class="w-full cursor-crosshair" style="touch-action: none; height: 180px;"></canvas>
                </div>
                <div class="flex justify-between items-center mt-2">
                    <button type="button" onclick="clearTripSignature()" 
                            class="text-sm text-slate-600 hover:text-primary flex items-center gap-1">
                        <x-base.lucide class="w-4 h-4" icon="Eraser" />
                        Clear Signature
                    </button>
                    <span class="text-xs text-slate-400">Sign using mouse or touch</span>
                </div>
            </div>

            <!-- Certification Text -->
            <div class="p-4 bg-slate-50 rounded-lg border border-slate-200">
                <p class="text-xs text-slate-600 leading-relaxed">
                    <strong>Certification:</strong> I hereby certify that the information contained in this trip report and the vehicle inspection reports is true and accurate to the best of my knowledge. I have complied with all applicable FMCSA regulations during this trip.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex flex-col sm:flex-row gap-3 px-5 py-4 border-t border-slate-200/60 bg-slate-50/50">
            <x-base.button type="button" variant="outline-secondary" wire:click="closeModal" class="sm:order-1">
                Cancel
            </x-base.button>
            <x-base.button type="button" variant="outline-primary" wire:click="skipSignature" class="sm:order-2">
                Skip Signature
            </x-base.button>
            <x-base.button type="button" variant="primary" onclick="saveTripSignature()" class="flex-1 sm:order-3">
                <x-base.lucide class="w-4 h-4 mr-2" icon="CheckCircle" />
                Sign & Generate PDFs
            </x-base.button>
        </div>
    </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    let canvas, ctx, isDrawing = false;
    let lastX = 0, lastY = 0;
    let initialized = false;

    window.initializeTripSignatureCanvas = function(attempt = 0) {
        canvas = document.getElementById('tripSignatureCanvas');
        
        if (!canvas) {            
            if (attempt < 15) {
                setTimeout(() => initializeTripSignatureCanvas(attempt + 1), 150);
            }
            return false;
        }

        // Reset for reinitialization
        if (initialized) {
            ctx = canvas.getContext('2d');
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            return true;
        }
        
        ctx = canvas.getContext('2d');
        
        // Set canvas size
        const parent = canvas.parentElement;
        canvas.width = parent.offsetWidth - 4;
        canvas.height = 180;

        // Set drawing styles
        ctx.strokeStyle = '#1e293b';
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        
        // Fill with white background
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        // Helper function to get correct coordinates with scaling
        function getCanvasCoordinates(clientX, clientY) {
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            return {
                x: (clientX - rect.left) * scaleX,
                y: (clientY - rect.top) * scaleY
            };
        }

        // Mouse events
        canvas.addEventListener('mousedown', function(e) {
            isDrawing = true;
            const coords = getCanvasCoordinates(e.clientX, e.clientY);
            lastX = coords.x;
            lastY = coords.y;
        });

        canvas.addEventListener('mousemove', function(e) {
            if (!isDrawing) return;
            const coords = getCanvasCoordinates(e.clientX, e.clientY);
            
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(coords.x, coords.y);
            ctx.stroke();
            
            lastX = coords.x;
            lastY = coords.y;
        });

        canvas.addEventListener('mouseup', () => isDrawing = false);
        canvas.addEventListener('mouseout', () => isDrawing = false);

        // Touch events
        canvas.addEventListener('touchstart', function(e) {
            e.preventDefault();
            const touch = e.touches[0];
            const coords = getCanvasCoordinates(touch.clientX, touch.clientY);
            lastX = coords.x;
            lastY = coords.y;
            isDrawing = true;
        });

        canvas.addEventListener('touchmove', function(e) {
            e.preventDefault();
            if (!isDrawing) return;
            const touch = e.touches[0];
            const coords = getCanvasCoordinates(touch.clientX, touch.clientY);
            
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(coords.x, coords.y);
            ctx.stroke();
            
            lastX = coords.x;
            lastY = coords.y;
        });

        canvas.addEventListener('touchend', () => isDrawing = false);

        initialized = true;
        return true;
    };

    window.clearTripSignature = function() {
        if (!canvas || !ctx) return;
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
    };

    window.saveTripSignature = function() {
        if (!canvas || !ctx) {
            alert('Canvas not initialized. Please try again.');
            return;
        }

        // Check if canvas has any drawing
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const data = imageData.data;
        let isEmpty = true;

        for (let i = 0; i < data.length; i += 4) {
            if (data[i] !== 255 || data[i + 1] !== 255 || data[i + 2] !== 255) {
                isEmpty = false;
                break;
            }
        }

        if (isEmpty) {
            alert('Please provide a signature before saving.');
            return;
        }

        const signatureData = canvas.toDataURL('image/png');
        Livewire.dispatch('saveTripSignatureData', { signatureData: signatureData });
    };

    // Initialize when Livewire component updates
    document.addEventListener('livewire:initialized', function() {
        Livewire.on('openTripSignatureModal', () => {
            initialized = false;
            setTimeout(() => initializeTripSignatureCanvas(0), 200);
        });
    });
})();
</script>
@endpush
