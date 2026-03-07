<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlanRequest;
use App\Models\User;
use Illuminate\Http\Request;

class PlanRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = PlanRequest::query()->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%")
                  ->orWhere('plan_name', 'like', "%{$search}%");
            });
        }

        $planRequests = $query->paginate(15)->appends($request->query());

        $counts = [
            'all' => PlanRequest::count(),
            'new' => PlanRequest::where('status', 'new')->count(),
            'in_progress' => PlanRequest::where('status', 'in_progress')->count(),
            'contacted' => PlanRequest::where('status', 'contacted')->count(),
            'closed' => PlanRequest::where('status', 'closed')->count(),
        ];

        return view('admin.plan-requests.index', compact('planRequests', 'counts'));
    }

    public function show(PlanRequest $planRequest)
    {
        $admins = User::role('superadmin')->get();
        return view('admin.plan-requests.show', compact('planRequest', 'admins'));
    }

    public function update(Request $request, PlanRequest $planRequest)
    {
        $validated = $request->validate([
            'status' => 'required|in:new,in_progress,contacted,closed',
            'admin_notes' => 'nullable|string|max:5000',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        if ($validated['status'] === 'contacted' && $planRequest->status !== 'contacted') {
            $validated['responded_at'] = now();
        }

        $planRequest->update($validated);

        return redirect()
            ->route('admin.plan-requests.show', $planRequest->id)
            ->with('notification', [
                'type' => 'success',
                'message' => 'Plan request updated',
                'details' => 'Plan request updated successfully.',
            ]);
    }

    public function destroy(PlanRequest $planRequest)
    {
        $planRequest->delete();

        return redirect()
            ->route('admin.plan-requests.index')
            ->with('notification', [
                'type' => 'success',
                'message' => 'Plan request deleted',
                'details' => 'Plan request deleted successfully.',
            ]);
    }
}
