<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class CarrierStatusController extends Controller
{
    public function pendingValidation(): Response|RedirectResponse
    {
        $user    = Auth::user();
        $carrier = $user->carrierDetails?->carrier;

        if (! $carrier) {
            return redirect()->route('carrier.wizard.step1');
        }

        $bankingDetails = $carrier->bankingDetails;

        $allowedStatuses = [
            Carrier::STATUS_PENDING,
            Carrier::STATUS_PENDING_VALIDATION ?? 4,
        ];

        $canAccess = in_array($carrier->status, $allowedStatuses)
            || ($carrier->status === Carrier::STATUS_ACTIVE && $bankingDetails?->isPending());

        if (! $canAccess) {
            return $this->redirectBasedOnStatus($carrier);
        }

        $progress      = $this->calculateRegistrationProgress($carrier);
        $estimatedTime = $this->getEstimatedApprovalTime($carrier);

        return Inertia::render('carrier/status/PendingValidation', [
            'carrier'       => $carrier->only(['id', 'name', 'slug', 'status', 'address', 'state', 'created_at']),
            'progress'      => $progress,
            'estimatedTime' => $estimatedTime,
        ]);
    }

    public function showConfirmation(): Response|RedirectResponse
    {
        $user    = Auth::user();
        $carrier = $user->carrierDetails?->carrier;

        if (! $carrier) {
            return redirect()->route('carrier.wizard.step2')
                ->with('warning', 'Please complete your registration first.');
        }

        $progress = $this->calculateRegistrationProgress($carrier);

        return Inertia::render('carrier/status/Confirmation', [
            'carrier'  => $carrier->only(['id', 'name', 'slug', 'status', 'address', 'state', 'created_at']),
            'progress' => $progress,
        ]);
    }

    public function showInactive(): Response|RedirectResponse
    {
        $user    = Auth::user();
        $carrier = $user->carrierDetails?->carrier;

        if (! $carrier) {
            return redirect()->route('carrier.wizard.step1');
        }

        if ($carrier->status !== Carrier::STATUS_INACTIVE) {
            return $this->redirectBasedOnStatus($carrier);
        }

        return Inertia::render('carrier/status/Inactive', [
            'carrier' => $carrier->only(['id', 'name', 'slug', 'status', 'created_at']),
        ]);
    }

    public function showBankingRejected(): Response|RedirectResponse
    {
        $user           = Auth::user();
        $carrier        = $user->carrierDetails?->carrier;
        $bankingDetails = $carrier?->bankingDetails;

        if (! $carrier || ! $bankingDetails?->isRejected()) {
            return $this->redirectBasedOnStatus($carrier);
        }

        return Inertia::render('carrier/status/BankingRejected', [
            'carrier'        => $carrier->only(['id', 'name', 'slug', 'status', 'created_at']),
            'bankingDetails' => $bankingDetails?->only([
                'id', 'account_holder_name', 'status', 'rejection_reason', 'created_at',
            ]),
        ]);
    }

    public function requestReactivation(Request $request): RedirectResponse
    {
        $request->validate([
            'reason'          => 'required|string|max:1000',
            'additional_info' => 'nullable|string|max:2000',
        ]);

        $user    = Auth::user();
        $carrier = $user->carrierDetails?->carrier;

        Log::info('Carrier requested reactivation', [
            'user_id'    => $user->id,
            'carrier_id' => $carrier?->id,
            'reason'     => $request->reason,
        ]);

        return redirect()->route('carrier.inactive')
            ->with('success', 'Your reactivation request has been submitted. Our team will review it within 2-3 business days.');
    }

    private function calculateRegistrationProgress(Carrier $carrier): int
    {
        $progress = 50;

        if ($carrier->name && $carrier->address) {
            $progress += 25;
        }

        if ($carrier->bankingDetails) {
            $progress += 15;
            if ($carrier->bankingDetails->status === 'approved') {
                $progress += 10;
            }
        }

        if ($carrier->status === Carrier::STATUS_ACTIVE) {
            $progress = 100;
        }

        return min($progress, 100);
    }

    private function getEstimatedApprovalTime(Carrier $carrier): array
    {
        $daysSinceCreation  = (int) $carrier->created_at->diffInDays(now());
        $estimatedRemaining = max(0, 5 - $daysSinceCreation);

        return [
            'days_since_creation'      => $daysSinceCreation,
            'estimated_days_remaining' => $estimatedRemaining,
            'message'                  => $estimatedRemaining > 0
                ? "Estimated approval in {$estimatedRemaining} business days"
                : 'Your application is being reviewed and should be processed soon',
        ];
    }

    private function redirectBasedOnStatus(?Carrier $carrier): RedirectResponse
    {
        if (! $carrier) {
            return redirect()->route('carrier.wizard.step1');
        }

        $bankingDetails = $carrier->bankingDetails;

        if ($carrier->status === Carrier::STATUS_ACTIVE) {
            if ($bankingDetails?->isRejected()) {
                return redirect()->route('carrier.banking.rejected');
            }
            if ($bankingDetails && ! $bankingDetails->isApproved()) {
                return redirect()->route('carrier.pending.validation');
            }
            return redirect()->route('carrier.dashboard');
        }

        if ($carrier->status === Carrier::STATUS_INACTIVE) {
            return redirect()->route('carrier.inactive');
        }

        return redirect()->route('carrier.pending.validation');
    }
}
