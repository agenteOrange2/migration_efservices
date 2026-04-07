<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InteractsWithAdminScope;
use App\Http\Controllers\Controller;
use App\Models\UserDriverDetail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AdminDriverHosController extends Controller
{
    use InteractsWithAdminScope;

    public function index(Request $request): InertiaResponse
    {
        $scope = $this->scopeContext();
        $filters = [
            'search' => (string) $request->input('search', ''),
            'carrier_id' => $scope['is_superadmin'] ? (string) $request->input('carrier_id', '') : (string) ($scope['carrier_id'] ?? ''),
            'pending_only' => (string) $request->input('pending_only', ''),
        ];

        $query = UserDriverDetail::query()
            ->with(['user:id,name,email', 'carrier:id,name', 'cycleChangeApprovedBy:id,name'])
            ->where('status', UserDriverDetail::STATUS_ACTIVE);

        $this->applyDriverScope($query, $scope, $filters['carrier_id']);

        if ($filters['search'] !== '') {
            $search = trim($filters['search']);
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('first_name', 'like', '%' . $search . '%')
                    ->orWhere('middle_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhereHas('user', fn (Builder $userQuery) => $userQuery
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%'));
            });
        }

        if ($filters['pending_only'] === 'yes') {
            $query->where('hos_cycle_change_requested', true);
        }

        $query->orderByDesc('hos_cycle_change_requested')
            ->orderBy('last_name');

        $statsBase = clone $query;
        $drivers = $query->paginate(20)->withQueryString();
        $drivers->through(fn (UserDriverDetail $driver) => [
            'id' => $driver->id,
            'name' => $driver->full_name ?: ($driver->user?->name ?: 'Unknown Driver'),
            'email' => $driver->user?->email,
            'carrier_name' => $driver->carrier?->name,
            'current_cycle' => $driver->getEffectiveHosCycleType(),
            'current_cycle_label' => $driver->getEffectiveHosCycleType() === UserDriverDetail::HOS_CYCLE_60_7 ? '60 hours / 7 days' : '70 hours / 8 days',
            'pending_requested' => (bool) $driver->hos_cycle_change_requested,
            'pending_cycle' => $driver->hos_cycle_change_requested_to,
            'pending_cycle_label' => $driver->hos_cycle_change_requested_to === UserDriverDetail::HOS_CYCLE_60_7
                ? '60 hours / 7 days'
                : ($driver->hos_cycle_change_requested_to === UserDriverDetail::HOS_CYCLE_70_8 ? '70 hours / 8 days' : null),
            'requested_at' => $driver->hos_cycle_change_requested_at?->format('n/j/Y g:i A'),
            'approved_at' => $driver->hos_cycle_change_approved_at?->format('n/j/Y g:i A'),
            'approved_by' => $driver->cycleChangeApprovedBy?->name,
        ]);

        return Inertia::render('admin/drivers/hos/Index', [
            'filters' => $filters,
            'drivers' => $drivers,
            'stats' => [
                'total' => (clone $statsBase)->count(),
                'pending' => (clone $statsBase)->where('hos_cycle_change_requested', true)->count(),
                'cycle_60_7' => (clone $statsBase)->where('hos_cycle_type', UserDriverDetail::HOS_CYCLE_60_7)->count(),
                'cycle_70_8' => (clone $statsBase)->where(function (Builder $builder) {
                    $builder
                        ->where('hos_cycle_type', UserDriverDetail::HOS_CYCLE_70_8)
                        ->orWhereNull('hos_cycle_type');
                })->count(),
            ],
            'carriers' => $this->carrierOptions($scope),
            'canFilterCarriers' => $scope['is_superadmin'],
            'cycleOptions' => [
                ['value' => UserDriverDetail::HOS_CYCLE_60_7, 'label' => '60 hours / 7 days'],
                ['value' => UserDriverDetail::HOS_CYCLE_70_8, 'label' => '70 hours / 8 days'],
            ],
        ]);
    }

    public function update(Request $request, UserDriverDetail $driver): RedirectResponse
    {
        $scope = $this->scopeContext();
        $this->ensureAllowedCarrier((int) $driver->carrier_id, $scope);

        $validated = $request->validate([
            'hos_cycle_type' => ['required', 'in:' . UserDriverDetail::HOS_CYCLE_60_7 . ',' . UserDriverDetail::HOS_CYCLE_70_8],
        ]);

        $driver->update([
            'hos_cycle_type' => $validated['hos_cycle_type'],
            'hos_cycle_change_requested' => false,
            'hos_cycle_change_requested_to' => null,
            'hos_cycle_change_requested_at' => null,
            'hos_cycle_change_approved_at' => now(),
            'hos_cycle_change_approved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Driver HOS cycle updated successfully.');
    }

    public function approveRequest(UserDriverDetail $driver): RedirectResponse
    {
        $scope = $this->scopeContext();
        $this->ensureAllowedCarrier((int) $driver->carrier_id, $scope);

        if (! $driver->hasPendingCycleChangeRequest()) {
            return back()->with('error', 'This driver does not have a pending cycle request.');
        }

        $driver->approveCycleChange((int) auth()->id());

        return back()->with('success', 'Driver cycle request approved.');
    }

    public function rejectRequest(UserDriverDetail $driver): RedirectResponse
    {
        $scope = $this->scopeContext();
        $this->ensureAllowedCarrier((int) $driver->carrier_id, $scope);

        if (! $driver->hasPendingCycleChangeRequest()) {
            return back()->with('error', 'This driver does not have a pending cycle request.');
        }

        $driver->rejectCycleChange();

        return back()->with('success', 'Driver cycle request rejected.');
    }
}
