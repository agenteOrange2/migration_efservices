<?php

namespace App\Http\Controllers\Admin;

use App\Models\DocumentType;
use App\Http\Controllers\Controller;
use App\Services\CarrierDocumentService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DocumentTypeController extends Controller
{
    protected $documentService;

    public function __construct(CarrierDocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    public function index(): Response
    {
        $documentTypes = DocumentType::withCount('carrierDocuments')->get()->map(fn ($dt) => [
            'id' => $dt->id,
            'name' => $dt->name,
            'requirement' => $dt->requirement,
            'carrier_documents_count' => $dt->carrier_documents_count,
            'has_default_file' => $dt->getFirstMedia('default_documents') !== null,
            'default_file_url' => $dt->getFirstMediaUrl('default_documents') ?: null,
        ]);

        return Inertia::render('admin/document-types/Index', [
            'documentTypes' => $documentTypes,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/document-types/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:document_types',
            'requirement' => 'required|boolean',
            'allow_default_file' => 'required|boolean',
            'default_file' => 'nullable|file|mimes:pdf,jpg,png|max:10240',
        ]);

        $documentType = DocumentType::create([
            'name' => $request->name,
            'requirement' => $request->requirement,
        ]);

        if ($request->hasFile('default_file') && $request->allow_default_file) {
            $fileName = strtolower(str_replace(' ', '_', $request->name)) . '.' .
                $request->file('default_file')->getClientOriginalExtension();

            $documentType->addMediaFromRequest('default_file')
                ->usingFileName($fileName)
                ->toMediaCollection('default_documents');

            $this->documentService->distributeDefaultDocument($documentType);
        } else {
            $this->documentService->syncNewDocumentTypes();
        }

        return redirect()->route('admin.document-types.index')
            ->with('success', 'Document Type created and distributed to all carriers.');
    }

    public function edit(DocumentType $documentType): Response
    {
        $documentTypeData = $documentType->only(['id', 'name', 'requirement']);
        $documentTypeData['has_default_file'] = $documentType->getFirstMedia('default_documents') !== null;
        $documentTypeData['default_file_url'] = $documentType->getFirstMediaUrl('default_documents') ?: null;

        return Inertia::render('admin/document-types/Edit', [
            'documentType' => $documentTypeData,
        ]);
    }

    public function update(Request $request, DocumentType $documentType): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:document_types,name,' . $documentType->id,
            'requirement' => 'required|boolean',
            'allow_default_file' => 'required|boolean',
            'default_file' => 'nullable|file|mimes:pdf,jpg,png|max:10240',
        ]);

        $hadDefaultDocument = $documentType->getFirstMedia('default_documents') !== null;

        $documentType->fill($request->only(['name', 'requirement']));

        if ($request->hasFile('default_file') && $request->allow_default_file) {
            $documentType->clearMediaCollection('default_documents');
            $fileName = strtolower(str_replace(' ', '_', $documentType->name)) . '.' .
                $request->file('default_file')->getClientOriginalExtension();

            $documentType->addMediaFromRequest('default_file')
                ->usingFileName($fileName)
                ->toMediaCollection('default_documents');

            $this->documentService->distributeDefaultDocument($documentType);
        } elseif (!$request->allow_default_file && $hadDefaultDocument) {
            $documentType->clearMediaCollection('default_documents');
            $this->documentService->distributeDefaultDocument($documentType);
        }

        $documentType->save();

        return redirect()->route('admin.document-types.index')
            ->with('success', 'Document Type updated successfully.');
    }

    public function showDefaultPolicy(): Response
    {
        $policyDocumentType = DocumentType::where('name', 'Politics')->first();
        $policyMedia = $policyDocumentType?->getFirstMedia('default_documents');

        return Inertia::render('admin/document-types/DefaultPolicy', [
            'policyDocumentType' => $policyDocumentType ? $policyDocumentType->only(['id', 'name', 'requirement']) : null,
            'policyMediaUrl' => $policyMedia?->getUrl(),
            'policyMediaName' => $policyMedia?->file_name,
        ]);
    }

    public function uploadDefaultPolicy(Request $request): RedirectResponse
    {
        $request->validate([
            'policy_file' => 'required|file|mimes:pdf|max:10240',
        ]);

        $policyDocumentType = DocumentType::firstOrCreate(
            ['name' => 'Politics'],
            ['requirement' => true]
        );

        $policyDocumentType->clearMediaCollection('default_documents');
        $policyDocumentType->addMediaFromRequest('policy_file')
            ->usingFileName('company_policy.pdf')
            ->toMediaCollection('default_documents');

        $this->documentService->distributeDefaultDocument($policyDocumentType);

        return redirect()->route('admin.document-types.default-policy')
            ->with('success', 'Default Company Policy uploaded and distributed.');
    }

    public function deleteDefaultPolicy(): RedirectResponse
    {
        $policyDocumentType = DocumentType::where('name', 'Politics')->first();
        $policyDocumentType?->clearMediaCollection('default_documents');

        return redirect()->route('admin.document-types.default-policy')
            ->with('success', 'Default Company Policy removed.');
    }

    public function destroy(DocumentType $documentType): RedirectResponse
    {
        if ($documentType->carrierDocuments()->exists()) {
            return redirect()->route('admin.document-types.index')
                ->with('error', 'Cannot delete: document type is associated with carriers.');
        }

        $documentType->delete();

        return redirect()->route('admin.document-types.index')
            ->with('success', 'Document Type deleted.');
    }
}
