<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\CarrierBasicInfoRequest;
use App\Http\Requests\CarrierStep2Request;
use App\Http\Requests\CarrierBankingInfoRequest;
use App\Models\User;
use App\Models\UserCarrierDetail;
use App\Models\Carrier;
use App\Models\Membership;
use App\Models\CarrierBankingDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use App\Events\CarrierStepCompleted;
use App\Events\CarrierRegistrationCompleted;

class CarrierWizardController extends Controller
{
    public function showStep1(): Response|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->carrierDetails?->carrier?->status) {
                return redirect()->route('carrier.dashboard');
            }

            if ($user->email_verified_at) {
                return redirect()->route('carrier.wizard.step2');
            }
        }

        return Inertia::render('carrier/wizard/Step1');
    }

    public function processStep1(CarrierBasicInfoRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            if (User::where('email', $request->email)->exists()) {
                return back()->withErrors(['email' => 'This email is already registered.']);
            }

            $user = User::create([
                'name'     => $request->full_name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'status'   => true,
            ]);

            $user->assignRole('user_carrier');
            app(\App\Services\Notification\NotificationPreferenceService::class)->createDefaultPreferences($user);

            $processedData = $request->getProcessedData();

            UserCarrierDetail::create([
                'user_id'      => $user->id,
                'phone'        => $processedData['phone'],
                'job_position' => $processedData['job_position'],
                'status'       => 1,
            ]);

            $user->sendEmailVerificationNotification();

            DB::commit();

            Log::info('Carrier user created', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'step'    => 'basic_info',
            ]);

            event(new CarrierStepCompleted($user, 'step1', [
                'email'        => $user->email,
                'name'         => $user->name,
                'phone'        => $processedData['phone'],
                'job_position' => $processedData['job_position'],
            ]));

            return redirect()->route('login')
                ->with('success', 'Account created successfully! Please check your email to verify your account, then log in to continue with your registration.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error creating carrier user', [
                'error' => $e->getMessage(),
                'email' => $request->email ?? 'unknown',
                'step'  => 'basic_info',
            ]);

            return back()->withErrors([
                'general' => 'An error occurred while creating your account. Please try again.',
            ])->withInput();
        }
    }

    public function checkVerification(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['verified' => false, 'error' => 'User not authenticated']);
        }

        return response()->json([
            'verified' => ! is_null($user->email_verified_at),
        ]);
    }

    public function showStep2(): Response|RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('carrier.wizard.step1')
                ->withErrors(['general' => 'Please log in first.']);
        }

        $memberships = Membership::where('status', true)->orderBy('id')->get();

        $states = $this->getUsStates();

        return Inertia::render('carrier/wizard/Step2', [
            'memberships' => $memberships,
            'states'      => $states,
        ]);
    }

    public function processStep2(CarrierStep2Request $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $user        = Auth::user();
            $carrierData = $request->getProcessedData();
            $carrierData['status'] = 2;

            $carrier = Carrier::updateOrCreate(
                ['user_id' => $user->id],
                $carrierData,
            );

            $userCarrierDetail = $user->carrierDetails;
            if ($userCarrierDetail) {
                $userCarrierDetail->update([
                    'carrier_id' => $carrier->id,
                    'status'     => 1,
                ]);
            }

            $user->update(['status' => true]);

            DB::commit();

            Log::info('Carrier company info updated', [
                'user_id'    => $user->id,
                'carrier_id' => $carrier->id,
                'step'       => 'company_info',
            ]);

            event(new CarrierStepCompleted($user, 'step2', [
                'carrier_id'   => $carrier->id,
                'company_name' => $carrier->name,
            ]));

            return redirect()->route('carrier.wizard.step3')
                ->with('success', 'Company information saved successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error updating carrier company info', [
                'error'   => $e->getMessage(),
                'user_id' => Auth::id(),
                'step'    => 'company_info',
            ]);

            return back()->withErrors([
                'general' => 'An error occurred while saving your information. Please try again.',
            ])->withInput();
        }
    }

    public function showStep3(): Response|RedirectResponse
    {
        $user    = Auth::user();
        $carrier = $user->carrierDetails?->carrier()->first();

        if (! $carrier) {
            return redirect()->route('carrier.wizard.step2')
                ->withErrors(['general' => 'Please complete your company information first.']);
        }

        $memberships = Membership::where('status', true)->orderBy('id')->get();

        return Inertia::render('carrier/wizard/Step3', [
            'memberships' => $memberships,
            'carrier'     => $carrier,
        ]);
    }

    public function processStep3(Request $request): RedirectResponse
    {
        $request->validate([
            'membership_id'  => 'required|exists:memberships,id',
            'terms_accepted' => 'required|accepted',
        ]);

        try {
            DB::beginTransaction();

            $user    = Auth::user();
            $carrier = $user->carrierDetails?->carrier()->first();

            if (! $carrier) {
                DB::rollBack();
                return redirect()->route('carrier.wizard.step2')
                    ->withErrors(['general' => 'Please complete your company information first.']);
            }

            $membership = Membership::findOrFail($request->membership_id);

            $carrier->update([
                'id_plan'              => $membership->id,
                'documents_ready'      => 'no',
                'terms_accepted_at'    => now(),
                'status'               => Carrier::STATUS_PENDING,
                'document_status'      => Carrier::DOCUMENT_STATUS_PENDING,
            ]);

            $user->update(['status' => true]);

            DB::commit();

            Log::info('Carrier membership selected', [
                'user_id'       => $user->id,
                'carrier_id'    => $carrier->id,
                'membership_id' => $membership->id,
                'step'          => 'membership_selection',
            ]);

            event(new CarrierStepCompleted($user, 'step3', [
                'carrier_id'      => $carrier->id,
                'membership_id'   => $membership->id,
                'membership_name' => $membership->name ?? 'Unknown',
            ]));

            return redirect()->route('carrier.wizard.step4')
                ->with('success', 'Membership selected successfully! Please provide your banking information.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error completing membership selection', [
                'error'   => $e->getMessage(),
                'user_id' => Auth::id(),
                'step'    => 'membership_selection',
            ]);

            return back()->withErrors([
                'general' => 'An error occurred. Please try again.',
            ])->withInput();
        }
    }

    public function showStep4(): Response|RedirectResponse
    {
        $user    = Auth::user();
        $carrier = $user->carrierDetails?->carrier()->first();

        if (! $carrier || ! $carrier->id_plan) {
            return redirect()->route('carrier.wizard.step3')
                ->withErrors(['general' => 'Please complete your membership selection first.']);
        }

        return Inertia::render('carrier/wizard/Step4', [
            'bankingDetails' => $carrier->bankingDetails,
        ]);
    }

    public function processStep4(CarrierBankingInfoRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $user    = Auth::user();
            $carrier = $user->carrierDetails?->carrier()->first();

            if (! $carrier || ! $carrier->id_plan) {
                return redirect()->route('carrier.wizard.step3')
                    ->withErrors(['general' => 'Please complete your membership selection first.']);
            }

            CarrierBankingDetail::updateOrCreate(
                ['carrier_id' => $carrier->id],
                [
                    'account_number'         => $request->account_number,
                    'account_holder_name'    => $request->account_holder_name,
                    'banking_routing_number' => $request->banking_routing_number,
                    'zip_code'               => $request->zip_code,
                    'security_code'          => $request->security_code,
                    'country_code'           => $request->country_code,
                    'status'                 => CarrierBankingDetail::STATUS_PENDING,
                ],
            );

            $carrier->update(['status' => Carrier::STATUS_PENDING]);

            DB::commit();

            Log::info('Banking information submitted', [
                'user_id'    => $user->id,
                'carrier_id' => $carrier->id,
                'step'       => 'banking_information',
            ]);

            event(new CarrierRegistrationCompleted($user, $carrier, [
                'banking_info'        => 'completed',
                'registration_method' => 'wizard',
                'total_steps'         => 4,
            ]));

            return redirect()->route('carrier.pending.validation')
                ->with('success', 'Banking information saved! Your account is now pending validation.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error saving banking information', [
                'error'   => $e->getMessage(),
                'user_id' => Auth::id(),
                'step'    => 'banking_information',
            ]);

            return back()->withErrors([
                'general' => 'An error occurred while saving your banking information. Please try again.',
            ])->withInput();
        }
    }

    public function checkUniqueness(Request $request): JsonResponse
    {
        $field = $request->input('field');
        $value = $request->input('value');
        $currentId = $request->input('current_id');

        if (! $field || ! $value) {
            return response()->json(['unique' => false, 'message' => 'Invalid parameters']);
        }

        $available = true;
        $message   = '';

        try {
            switch ($field) {
                case 'email':
                    $query = User::where('email', $value);
                    if ($currentId) $query->where('id', '!=', $currentId);
                    $exists    = $query->exists();
                    $available = ! $exists;
                    $message   = $exists ? 'This email is already registered' : 'Email is available';
                    break;

                case 'dot':
                    $query = Carrier::where('dot_number', $value);
                    if ($currentId) $query->where('id', '!=', $currentId);
                    $exists    = $query->exists();
                    $available = ! $exists;
                    $message   = $exists ? 'This DOT number is already registered' : 'DOT number is available';
                    break;

                case 'mc':
                    $query = Carrier::where('mc_number', $value);
                    if ($currentId) $query->where('id', '!=', $currentId);
                    $exists    = $query->exists();
                    $available = ! $exists;
                    $message   = $exists ? 'This MC number is already registered' : 'MC number is available';
                    break;

                case 'ein':
                    $query = Carrier::where('ein_number', $value);
                    if ($currentId) $query->where('id', '!=', $currentId);
                    $exists    = $query->exists();
                    $available = ! $exists;
                    $message   = $exists ? 'This EIN is already registered' : 'EIN is available';
                    break;

                default:
                    return response()->json(['unique' => false, 'message' => 'Invalid field']);
            }
        } catch (\Exception $e) {
            Log::error('Error checking field uniqueness', [
                'error' => $e->getMessage(),
                'field' => $field,
            ]);

            return response()->json(['unique' => false, 'message' => 'Error checking availability']);
        }

        return response()->json(['unique' => $available, 'message' => $message]);
    }

    private function getUsStates(): array
    {
        return [
            'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
            'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
            'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
            'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
            'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
            'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
            'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
            'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
            'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
            'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
            'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
            'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
            'WI' => 'Wisconsin', 'WY' => 'Wyoming', 'DC' => 'District of Columbia',
        ];
    }
}
