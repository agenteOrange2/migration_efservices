<div class="mb-6 flex items-center border-b border-dashed border-slate-300/70 pb-5 text-[0.94rem] font-medium">
    Two-Factor Authentication (2FA)
    @if(auth()->user()->two_factor_secret)
        <div class="ml-3 flex items-center rounded-md border border-success/10 bg-success/10 px-1.5 py-px text-xs font-medium text-success">
            <span class="-mt-px">Enabled</span>
        </div>
    @else
        <div class="ml-3 flex items-center rounded-md border border-slate-200 bg-slate-100 px-1.5 py-px text-xs font-medium text-slate-500">
            <span class="-mt-px">Disabled</span>
        </div>
    @endif
</div>

<div>
    <div class="text-slate-500">
        Enhance your account security by enabling Two-Factor Authentication. When enabled, you'll be required to enter a secure code during login.
    </div>

    @if(!auth()->user()->two_factor_secret)
        <!-- Enable 2FA Section -->
        <div class="mt-5 p-5 bg-slate-50 rounded-lg border border-slate-200/80">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-primary/10 rounded-lg">
                    <x-base.lucide class="w-6 h-6 text-primary" icon="ShieldCheck" />
                </div>
                <div class="flex-1">
                    <h4 class="font-medium text-slate-800 mb-2">Enable Two-Factor Authentication</h4>
                    <p class="text-sm text-slate-500 mb-4">
                        Add an extra layer of security to your account. You'll need an authenticator app like Google Authenticator or Authy.
                    </p>
                    <form method="POST" action="{{ route('two-factor.enable') }}">
                        @csrf
                        <x-base.button type="submit" variant="primary">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="Shield" />
                            Enable 2FA
                        </x-base.button>
                    </form>
                </div>
            </div>
        </div>
    @else
        <!-- 2FA Enabled Section -->
        <div class="mt-5 space-y-5">
            <!-- QR Code Section -->
            @if(session('status') == 'two-factor-authentication-enabled')
                <div class="p-5 bg-success/5 rounded-lg border border-success/20">
                    <div class="flex items-start gap-4">
                        <div class="p-3 bg-success/10 rounded-lg">
                            <x-base.lucide class="w-6 h-6 text-success" icon="CheckCircle" />
                        </div>
                        <div>
                            <h4 class="font-medium text-success mb-2">Two-Factor Authentication Enabled!</h4>
                            <p class="text-sm text-slate-600 mb-4">
                                Scan the QR code below with your authenticator app to complete setup.
                            </p>
                            <div class="p-4 bg-white rounded-lg inline-block border border-slate-200">
                                {!! auth()->user()->twoFactorQrCodeSvg() !!}
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Recovery Codes -->
            <div class="p-5 bg-warning/5 rounded-lg border border-warning/20">
                <div class="flex items-start gap-4">
                    <div class="p-3 bg-warning/10 rounded-lg">
                        <x-base.lucide class="w-6 h-6 text-warning" icon="Key" />
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium text-slate-800 mb-2">Recovery Codes</h4>
                        <p class="text-sm text-slate-500 mb-4">
                            Store these recovery codes in a secure location. They can be used to recover access to your account if you lose your authenticator device.
                        </p>
                        @if(auth()->user()->recoveryCodes())
                            <div class="grid grid-cols-2 gap-2 p-4 bg-slate-100 rounded-lg font-mono text-sm">
                                @foreach(json_decode(decrypt(auth()->user()->two_factor_recovery_codes), true) as $code)
                                    <div class="text-slate-700">{{ $code }}</div>
                                @endforeach
                            </div>
                            <form method="POST" action="{{ route('two-factor.recovery-codes') }}" class="mt-4">
                                @csrf
                                <x-base.button type="submit" variant="outline-warning" size="sm">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="RefreshCw" />
                                    Regenerate Recovery Codes
                                </x-base.button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Disable 2FA -->
            <div class="p-5 bg-danger/5 rounded-lg border border-danger/20">
                <div class="flex items-start gap-4">
                    <div class="p-3 bg-danger/10 rounded-lg">
                        <x-base.lucide class="w-6 h-6 text-danger" icon="ShieldOff" />
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium text-slate-800 mb-2">Disable Two-Factor Authentication</h4>
                        <p class="text-sm text-slate-500 mb-4">
                            If you disable 2FA, your account will be less secure. You can always re-enable it later.
                        </p>
                        <form method="POST" action="{{ route('two-factor.disable') }}">
                            @csrf
                            @method('DELETE')
                            <x-base.button type="submit" variant="outline-danger">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="ShieldOff" />
                                Disable 2FA
                            </x-base.button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
