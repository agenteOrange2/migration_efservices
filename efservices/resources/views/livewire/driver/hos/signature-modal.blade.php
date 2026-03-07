<div>
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Background overlay -->
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="w-full mt-3 text-center sm:mt-0 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                                Sign Daily Log
                            </h3>

                            <div class="mt-2">
                                <p class="text-sm text-gray-500 mb-4">
                                    Please sign below to certify that your HOS log for {{ \Carbon\Carbon::parse($date)->format('F j, Y') }} is accurate and complete.
                                </p>

                                <!-- Signature Canvas -->
                                <div class="border-2 border-gray-300 rounded-lg mb-4">
                                    <canvas id="signatureCanvas" class="w-full cursor-crosshair" style="touch-action: none;"></canvas>
                                </div>

                                <div class="flex justify-between items-center mb-4">
                                    <button type="button"
                                            onclick="clearSignature()"
                                            class="text-sm text-gray-600 hover:text-gray-900">
                                        Clear Signature
                                    </button>
                                    <span class="text-xs text-gray-500">Sign above</span>
                                </div>

                                <div class="text-xs text-gray-600 bg-gray-50 p-3 rounded">
                                    <strong>Certification:</strong> I certify that this record is true and correct to the best of my knowledge.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button"
                            onclick="saveSignature()"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Save Signature
                    </button>
                    <button type="button"
                            wire:click="closeModal"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    (function() {
        let canvas, ctx, isDrawing = false;
        let lastX = 0, lastY = 0;
        let initialized = false;

        function initializeCanvas(attempt = 0) {
            canvas = document.getElementById('signatureCanvas');
            
            if (!canvas) {
                console.log('Canvas not found, attempt:', attempt);
                if (attempt < 10) {
                    setTimeout(() => initializeCanvas(attempt + 1), 100);
                }
                return false;
            }

            if (initialized) {
                console.log('Canvas already initialized');
                return true;
            }

            console.log('Initializing canvas...');
            ctx = canvas.getContext('2d');
            
            // Set canvas size
            const parent = canvas.parentElement;
            canvas.width = parent.offsetWidth - 4;
            canvas.height = 200;

            // Set drawing styles
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            
            // Fill with white background
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            console.log('Canvas initialized:', canvas.width, 'x', canvas.height);

            // Mouse events
            canvas.addEventListener('mousedown', function(e) {
                isDrawing = true;
                const rect = canvas.getBoundingClientRect();
                lastX = e.clientX - rect.left;
                lastY = e.clientY - rect.top;
            });

            canvas.addEventListener('mousemove', function(e) {
                if (!isDrawing) return;
                const rect = canvas.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                ctx.beginPath();
                ctx.moveTo(lastX, lastY);
                ctx.lineTo(x, y);
                ctx.stroke();
                
                lastX = x;
                lastY = y;
            });

            canvas.addEventListener('mouseup', function() {
                isDrawing = false;
            });

            canvas.addEventListener('mouseout', function() {
                isDrawing = false;
            });

            // Touch events
            canvas.addEventListener('touchstart', function(e) {
                e.preventDefault();
                const touch = e.touches[0];
                const rect = canvas.getBoundingClientRect();
                lastX = touch.clientX - rect.left;
                lastY = touch.clientY - rect.top;
                isDrawing = true;
            });

            canvas.addEventListener('touchmove', function(e) {
                e.preventDefault();
                if (!isDrawing) return;
                const touch = e.touches[0];
                const rect = canvas.getBoundingClientRect();
                const x = touch.clientX - rect.left;
                const y = touch.clientY - rect.top;
                
                ctx.beginPath();
                ctx.moveTo(lastX, lastY);
                ctx.lineTo(x, y);
                ctx.stroke();
                
                lastX = x;
                lastY = y;
            });

            canvas.addEventListener('touchend', function() {
                isDrawing = false;
            });

            initialized = true;
            return true;
        }

        window.clearSignature = function() {
            if (!canvas || !ctx) return;
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
        };

        window.saveSignature = function() {
            if (!canvas || !ctx) {
                alert('Canvas not initialized');
                return;
            }

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
            Livewire.dispatch('saveSignatureData', { signatureData: signatureData });
        };

        // Initialize when modal opens
        document.addEventListener('livewire:initialized', function() {
            Livewire.on('openSignatureModal', () => {
                console.log('Modal opened, initializing canvas...');
                initialized = false;
                initializeCanvas(0);
            });
        });
    })();
</script>
@endpush
