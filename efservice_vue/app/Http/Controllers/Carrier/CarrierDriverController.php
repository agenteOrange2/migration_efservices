<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Admin\Driver\DriverListController;
use App\Http\Controllers\Carrier\Concerns\ResolvesCarrierContext;
use App\Models\UserDriverDetail;
use App\Services\Driver\StepCompletionCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CarrierDriverController extends DriverListController
{
    use ResolvesCarrierContext;

    public function index(Request $request): Response
    {
        $carrier = $this->resolveCarrier();
        $carrier->loadMissing('membership');
        $search = trim((string) $request->input('search', ''));
        $status = trim((string) ($request->input('status', $request->input('tab', ''))));
        $perPage = max(10, min((int) $request->input('per_page', 15), 50));

        $baseQuery = UserDriverDetail::query()
            ->with(['user:id,name,email', 'application:id,user_id,status', 'carrier:id,name'])
            ->where('carrier_id', $carrier->id);

        $query = (clone $baseQuery);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($userQuery) => $userQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $this->applyEffectiveStatusFilter($query, $status);

        $drivers = $query
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $drivers->getCollection()->transform(fn (UserDriverDetail $driver) => $this->transformDriver($driver));

        $currentDrivers = (clone $baseQuery)->count();
        $maxDrivers = (int) ($carrier->membership?->max_drivers ?? 0);
        $remainingDrivers = max($maxDrivers - $currentDrivers, 0);
        $usagePercentage = $maxDrivers > 0
            ? min((int) round(($currentDrivers / $maxDrivers) * 100), 100)
            : 0;

        return Inertia::render('carrier/drivers/Index', [
            'drivers' => $drivers,
            'filters' => [
                'search' => $search,
                'tab' => $status,
                'per_page' => $perPage,
            ],
            'stats' => [
                'total' => $currentDrivers,
                'active' => $this->countByEffectiveStatus((clone $baseQuery), UserDriverDetail::EFFECTIVE_STATUS_ACTIVE),
                'pending_review' => $this->countByEffectiveStatus((clone $baseQuery), UserDriverDetail::EFFECTIVE_STATUS_PENDING_REVIEW),
                'inactive' => $this->countByEffectiveStatus((clone $baseQuery), UserDriverDetail::EFFECTIVE_STATUS_INACTIVE),
                'draft' => $this->countByEffectiveStatus((clone $baseQuery), UserDriverDetail::EFFECTIVE_STATUS_DRAFT),
            ],
            'driverLimit' => [
                'current' => $currentDrivers,
                'max' => $maxDrivers,
                'remaining' => $remainingDrivers,
                'usage_percentage' => $usagePercentage,
                'has_limit' => $maxDrivers > 0,
            ],
            'carrier' => [
                'id' => $carrier->id,
                'name' => $carrier->name,
            ],
            'routeNames' => [
                'index'      => 'carrier.drivers.index',
                'show'       => 'carrier.drivers.show',
                'create'     => 'carrier.drivers.create',
                'edit'       => 'carrier.drivers.edit',
                'activate'   => 'carrier.drivers.activate',
                'deactivate' => 'carrier.drivers.deactivate',
                'destroy'    => 'carrier.drivers.destroy',
            ],
        ]);
    }

    public function show(UserDriverDetail $driver): Response
    {
        abort_unless((int) $driver->carrier_id === (int) $this->resolveCarrierId(), 403);

        $driver->load([
            'user:id,name,email',
            'carrier:id,name,address,dot_number,mc_number,state',
            'application.addresses',
            'application.details',
            'licenses.endorsements',
            'medicalQualification',
            'experiences',
            'employmentCompanies.company',
            'relatedEmployments',
            'unemploymentPeriods',
            'trainingSchools',
            'courses',
            'accidents',
            'trafficConvictions',
            'testings',
            'inspections.vehicle',
            'vehicleAssignments.vehicle',
            'fmcsrData',
            'criminalHistory',
            'certification',
            'companyPolicy',
            'w9Form',
            'media',
            'trips.vehicle:id,make,model,year,company_unit_number',
        ]);

        $driverData = $this->buildDriverShowData($driver, true);
        $driverData['routeNames'] = [
            'index'                    => 'carrier.drivers.index',
            'edit'                     => 'carrier.drivers.edit',
            'documentsDownload'        => 'carrier.drivers.documents.download',
            'activate'                 => 'carrier.drivers.activate',
            'deactivate'               => 'carrier.drivers.deactivate',
            'hosGenerateDailyLog'      => 'carrier.hos.documents.generate-daily-log',
            'hosGenerateMonthlySummary'=> 'carrier.hos.documents.generate-monthly-summary',
            'hosGenerateFmcsaMonthly'  => 'carrier.hos.documents.generate-fmcsa-monthly',
            'hosDestroy'               => 'carrier.hos.documents.destroy',
        ];
        $driverData['isCarrierContext'] = true;

        return Inertia::render('carrier/drivers/Show', $driverData);
    }

    public function activate(UserDriverDetail $driver): RedirectResponse
    {
        abort_unless((int) $driver->carrier_id === (int) $this->resolveCarrierId(), 403);

        $driver->status = UserDriverDetail::STATUS_ACTIVE;
        $driver->save();

        return redirect()->route('carrier.drivers.show', $driver)
            ->with('success', 'Driver has been activated.');
    }

    public function deactivate(UserDriverDetail $driver): RedirectResponse
    {
        abort_unless((int) $driver->carrier_id === (int) $this->resolveCarrierId(), 403);

        $driver->status = UserDriverDetail::STATUS_INACTIVE;
        $driver->save();

        return redirect()->route('carrier.drivers.show', $driver)
            ->with('success', 'Driver has been deactivated.');
    }

    public function destroy(UserDriverDetail $driver): RedirectResponse
    {
        abort_unless((int) $driver->carrier_id === (int) $this->resolveCarrierId(), 403);

        try {
            $user = $driver->user;

            $driver->clearMediaCollection('profile_photo_driver');
            $driver->delete();

            if ($user) {
                $user->delete();
            }

            return redirect()
                ->route('carrier.drivers.index')
                ->with('success', 'Driver deleted successfully.');
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'Driver could not be deleted.');
        }
    }

    protected function countByEffectiveStatus($query, string $status): int
    {
        $this->applyEffectiveStatusFilter($query, $status);

        return $query->count();
    }

    protected function applyEffectiveStatusFilter($query, string $status): void
    {
        switch ($status) {
            case UserDriverDetail::EFFECTIVE_STATUS_DRAFT:
                $query->whereHas('application', fn ($app) => $app->where('status', 'draft'));
                break;

            case UserDriverDetail::EFFECTIVE_STATUS_REJECTED:
                $query->whereHas('application', fn ($app) => $app->where('status', 'rejected'));
                break;

            case UserDriverDetail::EFFECTIVE_STATUS_PENDING_REVIEW:
                $query->where(function ($pending) {
                    $pending->whereHas('application', fn ($app) => $app->where('status', 'pending'))
                        ->orWhere(function ($driverPending) {
                            $driverPending->where('status', UserDriverDetail::STATUS_PENDING)
                                ->where(function ($appScope) {
                                    $appScope->whereDoesntHave('application')
                                        ->orWhereHas('application', fn ($app) => $app->where('status', 'approved'));
                                });
                        });
                });
                break;

            case UserDriverDetail::EFFECTIVE_STATUS_ACTIVE:
                $query->where('status', UserDriverDetail::STATUS_ACTIVE)
                    ->where(function ($approved) {
                        $approved->whereDoesntHave('application')
                            ->orWhereHas('application', fn ($app) => $app->where('status', 'approved'));
                    });
                break;

            case UserDriverDetail::EFFECTIVE_STATUS_INACTIVE:
                $query->where('status', UserDriverDetail::STATUS_INACTIVE)
                    ->where(function ($approved) {
                        $approved->whereDoesntHave('application')
                            ->orWhereHas('application', fn ($app) => $app->where('status', 'approved'));
                    });
                break;
        }
    }
}
