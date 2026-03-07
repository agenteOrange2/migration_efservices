<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\CarrierDocument;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\CarrierDocumentService;
use App\Repositories\CarrierDocumentRepository;

class CarrierDocumentController extends Controller
{
    protected $documentService;
    protected $documentRepository;

    public function __construct(
        CarrierDocumentService $documentService,
        CarrierDocumentRepository $documentRepository
    ) {
        $this->documentService = $documentService;
        $this->documentRepository = $documentRepository;
    }

    public function listCarriersForDocuments(): Response
    {
        $carriers = Carrier::with(['documents', 'membership'])->get()->map(function ($carrier) {
            $progress = $this->documentRepository->calculateDocumentProgress($carrier);
            return [
                'id' => $carrier->id,
                'name' => $carrier->name,
                'slug' => $carrier->slug,
                'mc_number' => $carrier->mc_number,
                'dot_number' => $carrier->dot_number,
                'status' => $carrier->status,
                'membership_name' => $carrier->membership?->name,
                'completion_percentage' => round($progress['percentage'], 1),
                'document_status' => $progress['status'],
                'approved' => $progress['approved'],
                'total' => $progress['total'],
            ];
        });

        return Inertia::render('admin/carriers-documents/Index', [
            'carriers' => $carriers,
        ]);
    }

    public function index(Carrier $carrier): Response
    {
        $documents = $this->documentRepository->getDocumentsWithStatus($carrier);
        $progress = $this->documentRepository->calculateDocumentProgress($carrier);
        $documentTypes = DocumentType::select('id', 'name', 'requirement')->get();

        return Inertia::render('admin/carriers-documents/Show', [
            'carrier' => $carrier->only('id', 'name', 'slug', 'mc_number', 'dot_number'),
            'documents' => $documents,
            'progress' => $progress,
            'documentTypes' => $documentTypes,
        ]);
    }

    public function upload(Request $request, Carrier $carrier, DocumentType $documentType): RedirectResponse
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,png|max:2048',
            'notes' => 'nullable|string',
        ]);

        $this->documentRepository->createOrUpdateDocument(
            $carrier,
            $documentType,
            $request->file('document'),
            $request->input('notes')
        );

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function update(Request $request, Carrier $carrier, CarrierDocument $document): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|integer|in:0,1,2,3',
            'document' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
            'notes' => 'nullable|string',
        ]);

        if ($request->hasFile('document')) {
            $document->clearMediaCollection('carrier_documents');
            $file = $request->file('document');
            $fileName = strtolower(str_replace(' ', '_', $document->documentType->name)) . '.' . $file->getClientOriginalExtension();

            $document->addMediaFromRequest('document')
                ->usingFileName($fileName)
                ->toMediaCollection('carrier_documents', 'public');
        }

        $document->update([
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? $document->notes,
        ]);

        \Cache::forget("carrier_stats_{$carrier->id}");

        return back()->with('success', 'Document updated.');
    }

    public function approveDefaultDocument(Request $request, Carrier $carrier, CarrierDocument $document): JsonResponse
    {
        $request->validate(['approved' => 'required|boolean']);

        $document->status = $request->approved ? CarrierDocument::STATUS_APPROVED : CarrierDocument::STATUS_PENDING;
        $document->save();
        $document->refresh();

        return response()->json([
            'status' => 'success',
            'newStatus' => $document->status,
            'statusName' => $document->status_name,
        ]);
    }

    public function destroy(Carrier $carrier, CarrierDocument $document): RedirectResponse
    {
        if ($document->documentType->requirement) {
            return back()->with('error', 'Cannot delete required documents.');
        }

        $document->clearMediaCollection('carrier_documents');
        $document->delete();

        return back()->with('success', 'Document deleted.');
    }
}
