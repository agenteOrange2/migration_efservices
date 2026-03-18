<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverEmploymentCompany;
use App\Models\Admin\Driver\MasterCompany;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = MasterCompany::query();

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('contact', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('state')) {
            $query->where('state', $request->get('state'));
        }

        if ($request->filled('city')) {
            $query->where('city', $request->get('city'));
        }

        $companies = $query->withCount('driverEmploymentCompanies')
            ->orderBy('company_name')
            ->paginate(15)
            ->withQueryString();

        $allStates = MasterCompany::whereNotNull('state')->where('state', '!=', '')
            ->distinct()->orderBy('state')->pluck('state');

        $allCities = MasterCompany::whereNotNull('city')->where('city', '!=', '')
            ->distinct()->orderBy('city')->pluck('city');

        return Inertia::render('admin/companies/Index', [
            'companies'  => $companies,
            'allStates'  => $allStates,
            'allCities'  => $allCities,
            'filters'    => $request->only(['search', 'state', 'city']),
        ]);
    }

    public function show(MasterCompany $company)
    {
        $company->loadCount('driverEmploymentCompanies');

        $employmentHistory = DriverEmploymentCompany::where('master_company_id', $company->id)
            ->with(['userDriverDetail.user'])
            ->orderByDesc('employed_from')
            ->paginate(15)
            ->withQueryString();

        $employmentHistory->getCollection()->transform(function ($item) {
            $driver = $item->userDriverDetail;

            return [
                'id'                  => $item->id,
                'driver_id'           => $item->user_driver_detail_id,
                'driver_name'         => $driver ? (($driver->user->name ?? '') . ' ' . ($driver->last_name ?? '')) : '—',
                'driver_email'        => $driver?->user?->email ?? '—',
                'positions_held'      => $item->positions_held,
                'employed_from'       => $item->employed_from?->format('M Y'),
                'employed_to'         => $item->employed_to?->format('M Y'),
                'email'               => $item->email,
                'email_sent'          => $item->email_sent,
                'verification_status' => $item->verification_status,
            ];
        });

        return Inertia::render('admin/companies/Show', [
            'company'           => $company,
            'employmentHistory' => $employmentHistory,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'address'      => 'nullable|string|max:255',
            'city'         => 'nullable|string|max:100',
            'state'        => 'nullable|string|max:100',
            'zip'          => 'nullable|string|max:20',
            'contact'      => 'nullable|string|max:255',
            'phone'        => 'nullable|string|max:50',
            'email'        => 'nullable|email|max:255',
            'fax'          => 'nullable|string|max:50',
        ]);

        MasterCompany::create($validated);

        return redirect()->route('admin.companies.index')->with('success', 'Company created successfully.');
    }

    public function update(Request $request, MasterCompany $company)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'address'      => 'nullable|string|max:255',
            'city'         => 'nullable|string|max:100',
            'state'        => 'nullable|string|max:100',
            'zip'          => 'nullable|string|max:20',
            'contact'      => 'nullable|string|max:255',
            'phone'        => 'nullable|string|max:50',
            'email'        => 'nullable|email|max:255',
            'fax'          => 'nullable|string|max:50',
        ]);

        $emailChanged = isset($validated['email']) && $company->email !== $validated['email'];
        $newEmail = $validated['email'] ?? null;

        $company->update($validated);

        if ($emailChanged && ! empty($newEmail)) {
            DriverEmploymentCompany::where('master_company_id', $company->id)
                ->update(['email' => $newEmail]);
        }

        return redirect()->back()->with('success', 'Company updated successfully.');
    }

    public function destroy(MasterCompany $company)
    {
        $hasHistory = DriverEmploymentCompany::where('master_company_id', $company->id)->exists();

        if ($hasHistory) {
            return redirect()->route('admin.companies.index')
                ->with('error', 'Cannot delete: this company has employment history records.');
        }

        $company->delete();

        return redirect()->route('admin.companies.index')->with('success', 'Company deleted successfully.');
    }
}
