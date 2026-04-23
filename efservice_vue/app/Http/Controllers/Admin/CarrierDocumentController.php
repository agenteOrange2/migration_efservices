<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\CarrierDocument;
use App\Models\DocumentType;
use Barryvdh\DomPDF\Facade\Pdf;
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

    public function listCarriersForDocuments(Request $request): Response
    {
        $search  = $request->input('search', '');
        $status  = $request->input('status', '');
        $perPage = (int) $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $query = Carrier::with(['documents', 'membership']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mc_number', 'like', "%{$search}%")
                  ->orWhere('dot_number', 'like', "%{$search}%")
                  ->orWhere('ein_number', 'like', "%{$search}%");
            });
        }

        $paginated = $query->orderBy('name')->paginate($perPage)->withQueryString();

        $carriers = $paginated->getCollection()->map(function ($carrier) use ($status) {
            $progress = $this->documentRepository->calculateDocumentProgress($carrier);
            $docStatus = $progress['status'];
            if ($status && $docStatus !== $status) return null;
            return [
                'id'                    => $carrier->id,
                'name'                  => $carrier->name,
                'slug'                  => $carrier->slug,
                'mc_number'             => $carrier->mc_number,
                'dot_number'            => $carrier->dot_number,
                'status'                => $carrier->status,
                'membership_name'       => $carrier->membership?->name,
                'completion_percentage' => round($progress['percentage'], 1),
                'document_status'       => $docStatus,
                'approved'              => $progress['approved'],
                'total'                 => $progress['total'],
            ];
        })->filter()->values();

        // Rebuild pagination with filtered collection
        $paginated->setCollection($carriers);

        // Stats (always from full set)
        $allCarriers = Carrier::with(['documents', 'membership'])->get()->map(function ($c) {
            $p = $this->documentRepository->calculateDocumentProgress($c);
            return ['document_status' => $p['status']];
        });

        $stats = [
            'total'      => $allCarriers->count(),
            'complete'   => $allCarriers->where('document_status', 'active')->count(),
            'in_progress'=> $allCarriers->where('document_status', 'pending')->count(),
            'none'       => $allCarriers->where('document_status', 'inactive')->count(),
        ];

        return Inertia::render('admin/carriers-documents/Index', [
            'carriers'   => $paginated,
            'stats'      => $stats,
            'filters'    => ['search' => $search, 'status' => $status, 'per_page' => $perPage],
        ]);
    }

    public function exportPdf(Request $request)
    {
        $search = $request->input('search', '');
        $status = $request->input('status', '');

        $query = Carrier::with(['documents', 'membership']);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mc_number', 'like', "%{$search}%")
                  ->orWhere('dot_number', 'like', "%{$search}%");
            });
        }

        $carriers = $query->orderBy('name')->get()->map(function ($carrier) use ($status) {
            $progress = $this->documentRepository->calculateDocumentProgress($carrier);
            $docStatus = $progress['status'];
            if ($status && $docStatus !== $status) return null;
            return [
                'name'                  => $carrier->name,
                'mc_number'             => $carrier->mc_number,
                'dot_number'            => $carrier->dot_number,
                'membership_name'       => $carrier->membership?->name,
                'completion_percentage' => round($progress['percentage'], 1),
                'document_status'       => $docStatus,
                'approved'              => $progress['approved'],
                'total'                 => $progress['total'],
            ];
        })->filter()->values();

        $total    = $carriers->count();
        $complete = $carriers->where('document_status', 'active')->count();
        $pending  = $carriers->where('document_status', 'pending')->count();
        $none     = $carriers->where('document_status', 'inactive')->count();

        $pdf = Pdf::loadView('pdf.carriers-documents', [
            'carriers'     => $carriers,
            'generated_at' => now()->format('m/d/Y H:i:s'),
            'stats'        => compact('total', 'complete', 'pending', 'none'),
            'filters'      => compact('search', 'status'),
        ])->setPaper('A4', 'landscape');

        return $pdf->download('carriers-documents-' . now()->format('Y-m-d') . '.pdf');
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

        $this->documentRepository->syncCarrierDocumentStatus($carrier);

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

        $this->documentRepository->syncCarrierDocumentStatus($carrier);

        return back()->with('success', 'Document updated.');
    }

    public function approveDefaultDocument(Request $request, Carrier $carrier, CarrierDocument $document): JsonResponse
    {
        $request->validate(['approved' => 'required|boolean']);

        $document->status = $request->approved ? CarrierDocument::STATUS_APPROVED : CarrierDocument::STATUS_PENDING;
        $document->save();
        $document->refresh();

        $this->documentRepository->syncCarrierDocumentStatus($carrier);

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

        $this->documentRepository->syncCarrierDocumentStatus($carrier);

        return back()->with('success', 'Document deleted.');
    }
}
