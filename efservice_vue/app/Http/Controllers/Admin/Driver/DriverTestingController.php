<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverTesting;
use App\Models\UserDriverDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DriverTestingController extends Controller
{
    public function create(UserDriverDetail $driver): Response
    {
        $driver->load(['user:id,name,email', 'carrier:id,name', 'licenses:id,user_driver_detail_id,license_number,license_class,expiration_date']);

        return Inertia::render('admin/drivers/testings/Create', [
            'driver' => [
                'id'        => $driver->id,
                'full_name' => trim(($driver->user?->name ?? 'Unknown') . ' ' . ($driver->middle_name ?? '') . ' ' . ($driver->last_name ?? '')),
                'email'     => $driver->user?->email ?? '',
                'phone'     => $driver->phone ?? null,
                'carrier'   => $driver->carrier ? ['id' => $driver->carrier->id, 'name' => $driver->carrier->name] : null,
                'license'   => $driver->licenses->first() ? [
                    'number'  => $driver->licenses->first()->license_number,
                    'class'   => $driver->licenses->first()->license_class,
                    'expires' => $driver->licenses->first()->expiration_date?->format('Y-m-d'),
                ] : null,
            ],
            'testTypes'    => DriverTesting::getTestTypes(),
            'locations'    => DriverTesting::getLocations(),
            'statuses'     => DriverTesting::getStatuses(),
            'testResults'  => DriverTesting::getTestResults(),
            'billOptions'  => DriverTesting::getBillOptions(),
            'administrators' => DriverTesting::getAdministrators(),
        ]);
    }

    public function store(Request $request, UserDriverDetail $driver)
    {
        $validated = $request->validate([
            'test_type'                    => 'required|string',
            'administered_by'              => 'required|string|max:255',
            'test_date'                    => 'required|date',
            'location'                     => 'required|string|max:255',
            'requester_name'               => 'required|string|max:255',
            'mro'                          => 'nullable|string|max:255',
            'scheduled_time'               => 'nullable|date',
            'test_result'                  => 'nullable|in:Positive,Negative,Refusal',
            'status'                       => 'nullable|in:Schedule,In Progress,Pending Review,Completed,Cancelled',
            'next_test_due'                => 'nullable|date',
            'bill_to'                      => 'nullable|in:company,employee',
            'notes'                        => 'nullable|string',
            'is_random_test'               => 'boolean',
            'is_post_accident_test'        => 'boolean',
            'is_reasonable_suspicion_test' => 'boolean',
            'is_pre_employment_test'       => 'boolean',
            'is_follow_up_test'            => 'boolean',
            'is_return_to_duty_test'       => 'boolean',
            'is_other_reason_test'         => 'boolean',
            'other_reason_description'     => 'nullable|string|max:500',
            'attachments'                  => 'nullable|array',
            'attachments.*'                => 'file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
        ]);

        $testing = DriverTesting::create([
            'user_driver_detail_id'        => $driver->id,
            'carrier_id'                   => $driver->carrier_id,
            'test_type'                    => $validated['test_type'],
            'administered_by'              => $validated['administered_by'],
            'test_date'                    => $validated['test_date'],
            'location'                     => $validated['location'],
            'requester_name'               => $validated['requester_name'],
            'mro'                          => $validated['mro'] ?? null,
            'scheduled_time'               => $validated['scheduled_time'] ?? null,
            'test_result'                  => $validated['test_result'] ?? null,
            'status'                       => $validated['status'] ?? 'Schedule',
            'next_test_due'                => $validated['next_test_due'] ?? null,
            'bill_to'                      => $validated['bill_to'] ?? null,
            'notes'                        => $validated['notes'] ?? null,
            'is_random_test'               => $request->boolean('is_random_test'),
            'is_post_accident_test'        => $request->boolean('is_post_accident_test'),
            'is_reasonable_suspicion_test' => $request->boolean('is_reasonable_suspicion_test'),
            'is_pre_employment_test'       => $request->boolean('is_pre_employment_test'),
            'is_follow_up_test'            => $request->boolean('is_follow_up_test'),
            'is_return_to_duty_test'       => $request->boolean('is_return_to_duty_test'),
            'is_other_reason_test'         => $request->boolean('is_other_reason_test'),
            'other_reason_description'     => $validated['other_reason_description'] ?? null,
            'created_by'                   => Auth::id(),
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $testing->addMedia($file)->toMediaCollection('document_attachments');
            }
        }

        return redirect()
            ->route('admin.drivers.show', $driver)
            ->with('success', 'Drug/Alcohol test record created successfully.');
    }

    public function edit(UserDriverDetail $driver, DriverTesting $testing): Response
    {
        $driver->load(['user:id,name,email', 'carrier:id,name', 'licenses:id,user_driver_detail_id,license_number,license_class,expiration_date']);

        $attachments = $testing->getMedia('document_attachments')->map(fn ($m) => [
            'id'   => $m->id,
            'name' => $m->file_name,
            'url'  => $m->getUrl(),
            'size' => round($m->size / 1024, 1) . ' KB',
        ])->toArray();

        return Inertia::render('admin/drivers/testings/Edit', [
            'driver' => [
                'id'        => $driver->id,
                'full_name' => trim(($driver->user?->name ?? 'Unknown') . ' ' . ($driver->middle_name ?? '') . ' ' . ($driver->last_name ?? '')),
                'email'     => $driver->user?->email ?? '',
                'phone'     => $driver->phone ?? null,
                'carrier'   => $driver->carrier ? ['id' => $driver->carrier->id, 'name' => $driver->carrier->name] : null,
                'license'   => $driver->licenses->first() ? [
                    'number'  => $driver->licenses->first()->license_number,
                    'class'   => $driver->licenses->first()->license_class,
                    'expires' => $driver->licenses->first()->expiration_date?->format('Y-m-d'),
                ] : null,
            ],
            'testing' => [
                'id'                           => $testing->id,
                'test_type'                    => $testing->test_type,
                'administered_by'              => $testing->administered_by,
                'test_date'                    => $testing->test_date?->format('Y-m-d'),
                'location'                     => $testing->location,
                'requester_name'               => $testing->requester_name,
                'mro'                          => $testing->mro,
                'scheduled_time'               => $testing->scheduled_time?->format('Y-m-d\TH:i'),
                'test_result'                  => $testing->test_result,
                'status'                       => $testing->status,
                'next_test_due'                => $testing->next_test_due?->format('Y-m-d'),
                'bill_to'                      => $testing->bill_to,
                'notes'                        => $testing->notes,
                'is_random_test'               => $testing->is_random_test,
                'is_post_accident_test'        => $testing->is_post_accident_test,
                'is_reasonable_suspicion_test' => $testing->is_reasonable_suspicion_test,
                'is_pre_employment_test'       => $testing->is_pre_employment_test,
                'is_follow_up_test'            => $testing->is_follow_up_test,
                'is_return_to_duty_test'       => $testing->is_return_to_duty_test,
                'is_other_reason_test'         => $testing->is_other_reason_test,
                'other_reason_description'     => $testing->other_reason_description,
                'attachments'                  => $attachments,
            ],
            'testTypes'      => DriverTesting::getTestTypes(),
            'locations'      => DriverTesting::getLocations(),
            'statuses'       => DriverTesting::getStatuses(),
            'testResults'    => DriverTesting::getTestResults(),
            'billOptions'    => DriverTesting::getBillOptions(),
            'administrators' => DriverTesting::getAdministrators(),
        ]);
    }

    public function update(Request $request, UserDriverDetail $driver, DriverTesting $testing)
    {
        $validated = $request->validate([
            'test_type'                    => 'required|string',
            'administered_by'              => 'required|string|max:255',
            'test_date'                    => 'required|date',
            'location'                     => 'required|string|max:255',
            'requester_name'               => 'required|string|max:255',
            'mro'                          => 'nullable|string|max:255',
            'scheduled_time'               => 'nullable|date',
            'test_result'                  => 'nullable|in:Positive,Negative,Refusal',
            'status'                       => 'nullable|in:Schedule,In Progress,Pending Review,Completed,Cancelled',
            'next_test_due'                => 'nullable|date',
            'bill_to'                      => 'nullable|in:company,employee',
            'notes'                        => 'nullable|string',
            'is_random_test'               => 'boolean',
            'is_post_accident_test'        => 'boolean',
            'is_reasonable_suspicion_test' => 'boolean',
            'is_pre_employment_test'       => 'boolean',
            'is_follow_up_test'            => 'boolean',
            'is_return_to_duty_test'       => 'boolean',
            'is_other_reason_test'         => 'boolean',
            'other_reason_description'     => 'nullable|string|max:500',
            'attachments'                  => 'nullable|array',
            'attachments.*'                => 'file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
            'delete_attachments'           => 'nullable|array',
            'delete_attachments.*'         => 'integer',
        ]);

        $testing->update([
            'test_type'                    => $validated['test_type'],
            'administered_by'              => $validated['administered_by'],
            'test_date'                    => $validated['test_date'],
            'location'                     => $validated['location'],
            'requester_name'               => $validated['requester_name'],
            'mro'                          => $validated['mro'] ?? null,
            'scheduled_time'               => $validated['scheduled_time'] ?? null,
            'test_result'                  => $validated['test_result'] ?? null,
            'status'                       => $validated['status'] ?? $testing->status,
            'next_test_due'                => $validated['next_test_due'] ?? null,
            'bill_to'                      => $validated['bill_to'] ?? null,
            'notes'                        => $validated['notes'] ?? null,
            'is_random_test'               => $request->boolean('is_random_test'),
            'is_post_accident_test'        => $request->boolean('is_post_accident_test'),
            'is_reasonable_suspicion_test' => $request->boolean('is_reasonable_suspicion_test'),
            'is_pre_employment_test'       => $request->boolean('is_pre_employment_test'),
            'is_follow_up_test'            => $request->boolean('is_follow_up_test'),
            'is_return_to_duty_test'       => $request->boolean('is_return_to_duty_test'),
            'is_other_reason_test'         => $request->boolean('is_other_reason_test'),
            'other_reason_description'     => $validated['other_reason_description'] ?? null,
            'updated_by'                   => Auth::id(),
        ]);

        // Delete selected attachments
        if (!empty($validated['delete_attachments'])) {
            foreach ($validated['delete_attachments'] as $mediaId) {
                $media = $testing->getMedia('document_attachments')->find($mediaId);
                $media?->delete();
            }
        }

        // Add new attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $testing->addMedia($file)->toMediaCollection('document_attachments');
            }
        }

        return redirect()
            ->route('admin.drivers.show', $driver)
            ->with('success', 'Test record updated successfully.');
    }

    public function destroy(UserDriverDetail $driver, DriverTesting $testing)
    {
        $testing->clearMediaCollection('document_attachments');
        $testing->clearMediaCollection('drug_test_pdf');
        $testing->clearMediaCollection('test_results');
        $testing->delete();

        return redirect()
            ->route('admin.drivers.show', $driver)
            ->with('success', 'Test record deleted successfully.');
    }
}
