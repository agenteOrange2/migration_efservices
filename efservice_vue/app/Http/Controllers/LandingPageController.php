<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use App\Models\ContactSubmission;
use App\Models\Membership;
use App\Models\PlanRequest;
use App\Models\UserDriverDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class LandingPageController extends Controller
{
    public function index(): Response
    {
        $plans = $this->resolvePlans();

        return Inertia::render('Welcome', [
            'canRegister' => Features::enabled(Features::registration()),
            'stats' => [
                'activeCarriers' => Carrier::query()->active()->count(),
                'registeredDrivers' => UserDriverDetail::query()->count(),
                'documentsManaged' => Media::query()->count(),
                'complianceRate' => 99,
            ],
            'plans' => $plans,
            'contact' => [
                'phone' => '(432) 853-5493',
                'email' => 'support@efcts.com',
                'address' => '801 Magnolia St Kermit, TX 79745',
                'whatsapp_url' => 'https://wa.me/14328535493?text=Hi%20EFCTS%2C%20I%20would%20like%20more%20information%20about%20your%20platform.',
            ],
        ]);
    }

    public function submitContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:60'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        ContactSubmission::query()->create([
            ...$validated,
            'status' => 'new',
            'ip_address' => $request->ip(),
        ]);

        return redirect()
            ->route('home')
            ->with('success', 'Thank you! We will contact you shortly.');
    }

    public function submitPlanRequest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:60'],
            'plan_name' => ['required', 'string', 'max:255'],
            'plan_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        PlanRequest::query()->create([
            ...$validated,
            'status' => 'new',
            'ip_address' => $request->ip(),
        ]);

        return redirect()
            ->route('home')
            ->with('success', 'Thank you! We will contact you shortly about this plan.');
    }

    protected function resolvePlans(): array
    {
        $baseQuery = Membership::query()
            ->where('status', true)
            ->orderByRaw('COALESCE(price, carrier_price, 0) asc')
            ->orderBy('id');

        $plans = (clone $baseQuery)
            ->when(
                (clone $baseQuery)->where('show_in_register', true)->exists(),
                fn ($query) => $query->where('show_in_register', true),
            )
            ->get()
            ->values()
            ->map(function (Membership $plan, int $index) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'price' => (float) ($plan->price ?? $plan->carrier_price ?? 0),
                    'pricing_type' => $plan->pricing_type ?: 'monthly',
                    'max_users' => max((int) ($plan->max_carrier ?? 0), 1),
                    'max_drivers' => (int) ($plan->max_drivers ?? 0),
                    'max_vehicles' => (int) ($plan->max_vehicles ?? 0),
                    'is_popular' => $index === 1,
                ];
            })
            ->all();

        if (! empty($plans)) {
            return $plans;
        }

        return [
            [
                'id' => 'beginner',
                'name' => 'Beginner',
                'description' => 'For small fleets',
                'price' => 400,
                'pricing_type' => 'monthly',
                'max_users' => 1,
                'max_drivers' => 5,
                'max_vehicles' => 5,
                'is_popular' => false,
            ],
            [
                'id' => 'intermediate',
                'name' => 'Intermediate',
                'description' => 'For medium fleets',
                'price' => 600,
                'pricing_type' => 'monthly',
                'max_users' => 2,
                'max_drivers' => 10,
                'max_vehicles' => 10,
                'is_popular' => true,
            ],
            [
                'id' => 'pro',
                'name' => 'Pro',
                'description' => 'For growing fleets',
                'price' => 800,
                'pricing_type' => 'monthly',
                'max_users' => 3,
                'max_drivers' => 15,
                'max_vehicles' => 15,
                'is_popular' => false,
            ],
        ];
    }
}
