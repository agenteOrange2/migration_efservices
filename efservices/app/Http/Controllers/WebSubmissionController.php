<?php

namespace App\Http\Controllers;

use App\Models\ContactSubmission;
use App\Models\PlanRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class WebSubmissionController extends Controller
{
    public function submitContact(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'message' => 'nullable|string|max:2000',
        ]);

        $validated['ip_address'] = $request->ip();

        $submission = ContactSubmission::create($validated);

        // Send notification email to admin
        try {
            Mail::raw(
                "New contact submission from {$submission->full_name} ({$submission->email})\n\n" .
                "Company: " . ($submission->company ?? 'N/A') . "\n" .
                "Phone: " . ($submission->phone ?? 'N/A') . "\n" .
                "Message: " . ($submission->message ?? 'N/A') . "\n\n" .
                "View in admin: " . route('admin.contact-submissions.show', $submission->id),
                function ($mail) use ($submission) {
                    $mail->to(config('mail.from.address'))
                        ->subject("New Contact: {$submission->full_name} - EFCTS Website");
                }
            );
        } catch (\Exception $e) {
            Log::warning('Failed to send contact notification email: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Thank you! We will contact you shortly.',
        ]);
    }

    public function submitPlanRequest(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'plan_name' => 'required|string|max:100',
            'plan_price' => 'nullable|numeric',
        ]);

        $validated['ip_address'] = $request->ip();

        $planRequest = PlanRequest::create($validated);

        // Send notification email to admin
        try {
            Mail::raw(
                "New plan request from {$planRequest->full_name} ({$planRequest->email})\n\n" .
                "Plan: {$planRequest->plan_name} - \${$planRequest->plan_price}/month\n" .
                "Company: " . ($planRequest->company ?? 'N/A') . "\n" .
                "Phone: " . ($planRequest->phone ?? 'N/A') . "\n\n" .
                "View in admin: " . route('admin.plan-requests.show', $planRequest->id),
                function ($mail) use ($planRequest) {
                    $mail->to(config('mail.from.address'))
                        ->subject("New Plan Request: {$planRequest->plan_name} - {$planRequest->full_name}");
                }
            );
        } catch (\Exception $e) {
            Log::warning('Failed to send plan request notification email: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your interest! We will contact you shortly.',
        ]);
    }
}
