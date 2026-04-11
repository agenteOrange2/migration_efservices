<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlanRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PlanRequestController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $query = PlanRequest::query()
            ->with('assignedUser:id,name,email')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));

            $query->where(function ($builder) use ($search) {
                $builder->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('plan_name', 'like', "%{$search}%");
            });
        }

        $planRequests = $query
            ->paginate(15)
            ->through(fn (PlanRequest $planRequest) => $this->transformPlanRequestRow($planRequest))
            ->withQueryString();

        return Inertia::render('admin/plan-requests/Index', [
            'planRequests' => $planRequests,
            'counts' => [
                'all' => PlanRequest::query()->count(),
                'new' => PlanRequest::query()->where('status', 'new')->count(),
                'in_progress' => PlanRequest::query()->where('status', 'in_progress')->count(),
                'contacted' => PlanRequest::query()->where('status', 'contacted')->count(),
                'closed' => PlanRequest::query()->where('status', 'closed')->count(),
            ],
            'filters' => [
                'status' => (string) $request->string('status'),
                'search' => (string) $request->string('search'),
            ],
        ]);
    }

    public function show(PlanRequest $planRequest): InertiaResponse
    {
        $planRequest->load('assignedUser:id,name,email');

        $admins = User::query()
            ->role('superadmin')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => (int) $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->values()
            ->all();

        return Inertia::render('admin/plan-requests/Show', [
            'planRequest' => $this->transformPlanRequestDetail($planRequest),
            'admins' => $admins,
        ]);
    }

    public function update(Request $request, PlanRequest $planRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['new', 'in_progress', 'contacted', 'closed'])],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if ($validated['status'] === 'contacted' && $planRequest->status !== 'contacted') {
            $validated['responded_at'] = now();
        }

        $planRequest->update($validated);

        return redirect()
            ->route('admin.plan-requests.show', $planRequest)
            ->with('success', 'Plan request updated successfully.');
    }

    public function destroy(PlanRequest $planRequest): RedirectResponse
    {
        $planRequest->delete();

        return redirect()
            ->route('admin.plan-requests.index')
            ->with('success', 'Plan request deleted successfully.');
    }

    private function transformPlanRequestRow(PlanRequest $planRequest): array
    {
        return [
            'id' => (int) $planRequest->id,
            'full_name' => $planRequest->full_name,
            'company' => $planRequest->company,
            'email' => $planRequest->email,
            'phone' => $planRequest->phone,
            'plan_name' => $planRequest->plan_name,
            'plan_price' => $planRequest->plan_price !== null ? number_format((float) $planRequest->plan_price, 2) : null,
            'status' => $planRequest->status,
            'assigned_user_name' => $planRequest->assignedUser?->name,
            'created_at' => $planRequest->created_at?->format('M j, Y g:i A'),
            'responded_at' => $planRequest->responded_at?->format('M j, Y g:i A'),
        ];
    }

    private function transformPlanRequestDetail(PlanRequest $planRequest): array
    {
        $phoneDigits = preg_replace('/\D+/', '', (string) ($planRequest->phone ?? ''));

        return [
            'id' => (int) $planRequest->id,
            'full_name' => $planRequest->full_name,
            'company' => $planRequest->company,
            'email' => $planRequest->email,
            'phone' => $planRequest->phone,
            'phone_digits' => $phoneDigits,
            'plan_name' => $planRequest->plan_name,
            'plan_price' => $planRequest->plan_price !== null ? number_format((float) $planRequest->plan_price, 2) : null,
            'status' => $planRequest->status,
            'admin_notes' => $planRequest->admin_notes,
            'assigned_to' => $planRequest->assigned_to ? (string) $planRequest->assigned_to : '',
            'assigned_user' => $planRequest->assignedUser ? [
                'id' => (int) $planRequest->assignedUser->id,
                'name' => $planRequest->assignedUser->name,
                'email' => $planRequest->assignedUser->email,
            ] : null,
            'responded_at' => $planRequest->responded_at?->format('M j, Y g:i A'),
            'ip_address' => $planRequest->ip_address,
            'created_at' => $planRequest->created_at?->format('M j, Y g:i A'),
            'updated_at' => $planRequest->updated_at?->format('M j, Y g:i A'),
        ];
    }
}
