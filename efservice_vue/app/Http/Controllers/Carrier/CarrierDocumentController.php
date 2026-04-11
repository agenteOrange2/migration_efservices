<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\CarrierDocument;
use App\Models\DocumentType;
use App\Services\CarrierDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CarrierDocumentController extends Controller
{
    public function __construct(protected CarrierDocumentService $documentService) {}

    public function index()
    {
        $carrier = Auth::user()->carrierDetails->carrier;

        $documentTypes   = DocumentType::orderBy('requirement', 'desc')->orderBy('name')->get();
        $uploadedDocs    = CarrierDocument::where('carrier_id', $carrier->id)
            ->with('documentType')
            ->get();

        $mappedDocuments = $documentTypes->map(function ($type) use ($uploadedDocs) {
            $doc = $uploadedDocs->firstWhere('document_type_id', $type->id);
            return [
                'type_id'      => $type->id,
                'type_name'    => $type->name,
                'requirement'  => $type->requirement ?? 'optional',
                'document_id'  => $doc?->id,
                'status'       => $doc ? (int) $doc->status : null,
                'status_name'  => $doc ? $doc->status_name : 'Not Uploaded',
                'file_url'     => $doc ? $doc->getFirstMediaUrl('carrier_documents') : null,
                'notes'        => $doc?->notes,
                'date'         => $doc?->date?->format('M d, Y'),
            ];
        });

        $progress = $this->documentService->getDocumentProgress($carrier);

        $stats = [
            'total'      => $progress['total'],
            'approved'   => $progress['approved'],
            'pending'    => $progress['pending'],
            'rejected'   => $progress['rejected'],
            'percentage' => $progress['progress_percentage'],
        ];

        return Inertia::render('carrier/Documents', [
            'carrier'          => [
                'id'   => $carrier->id,
                'name' => $carrier->name,
                'slug' => $carrier->slug,
            ],
            'mappedDocuments'  => $mappedDocuments->values(),
            'stats'            => $stats,
        ]);
    }

    public function upload(Request $request, int $documentTypeId)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $carrier      = Auth::user()->carrierDetails->carrier;
        $documentType = DocumentType::findOrFail($documentTypeId);

        $carrierDocument = CarrierDocument::firstOrCreate(
            ['carrier_id' => $carrier->id, 'document_type_id' => $documentType->id],
            ['status' => CarrierDocument::STATUS_PENDING, 'date' => now()]
        );

        if ($request->hasFile('document')) {
            $carrierDocument->clearMediaCollection('carrier_documents');
            $carrierDocument->addMediaFromRequest('document')
                ->toMediaCollection('carrier_documents');

            $carrierDocument->update(['status' => CarrierDocument::STATUS_PENDING, 'date' => now()]);
        }

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function deleteDocument(int $documentTypeId)
    {
        $carrier = Auth::user()->carrierDetails->carrier;

        $doc = CarrierDocument::where('carrier_id', $carrier->id)
            ->where('document_type_id', $documentTypeId)
            ->first();

        if ($doc) {
            $doc->clearMediaCollection('carrier_documents');
            $doc->delete();
        }

        return back()->with('success', 'Document deleted successfully.');
    }
}
