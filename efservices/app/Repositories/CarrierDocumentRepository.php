<?php

namespace App\Repositories;

use App\Models\Carrier;
use App\Models\DocumentType;
use App\Models\CarrierDocument;

class CarrierDocumentRepository
{
    /**
     * Obtiene todos los documentos de un carrier con sus estados
     */
    public function getDocumentsWithStatus(Carrier $carrier)
    {
        return CarrierDocument::where('carrier_id', $carrier->id)
            ->with('documentType')
            ->get()
            ->map(function ($document) {
                return [
                    'id' => $document->id,
                    'type' => $document->documentType,
                    'status_name' => $document->status_name,
                    'has_file' => $document->getFirstMediaUrl('carrier_documents') ? true : false,
                    'file_url' => $document->getFirstMediaUrl('carrier_documents'),
                    'notes' => $document->notes,
                    'updated_at' => $document->updated_at
                ];
            });
    }

    /**
     * Calcula el progreso de documentación de un carrier
     */
    public function calculateDocumentProgress(Carrier $carrier)
    {
        $totalDocuments = DocumentType::count();
        $approvedDocuments = $carrier->documents()
            ->where('status', CarrierDocument::STATUS_APPROVED)
            ->count();

        return [
            'total' => $totalDocuments,
            'approved' => $approvedDocuments,
            'percentage' => $totalDocuments > 0 ? ($approvedDocuments / $totalDocuments) * 100 : 0,
            'status' => $this->getDocumentationStatus($approvedDocuments, $totalDocuments)
        ];
    }

    /**
     * Crea o actualiza un documento
     */
    public function createOrUpdateDocument(Carrier $carrier, DocumentType $documentType, $file = null, $notes = null)
    {
        // Buscar documento existente
        $document = CarrierDocument::where('carrier_id', $carrier->id)
            ->where('document_type_id', $documentType->id)
            ->first();

        if ($document) {
            // Actualizar documento existente
            $document->update([
                'notes' => $notes,
                'date' => now(),
            ]);
        } else {
            // Crear nuevo documento
            $document = CarrierDocument::create([
                'carrier_id' => $carrier->id,
                'document_type_id' => $documentType->id,
                'status' => CarrierDocument::STATUS_PENDING,
                'notes' => $notes,
                'date' => now(),
            ]);
        }

        if ($file) {
            $document->clearMediaCollection('carrier_documents');
            
            // Get the original file extension to preserve it
            $originalExtension = $file->getClientOriginalExtension();
            $baseName = strtolower(str_replace(' ', '_', $documentType->name));
            $fileName = $baseName . '.' . $originalExtension;
            
            $document
                ->addMedia($file)
                ->usingFileName($fileName)
                ->toMediaCollection('carrier_documents');

            // Actualizar status a IN_PROCESS cuando se sube un archivo
            $document->update(['status' => CarrierDocument::STATUS_IN_PROCESS]);
        }

        return $document->fresh();
    }

    /**
     * Determina el estado general de la documentación
     */
    private function getDocumentationStatus($approved, $total)
    {
        if ($approved === 0) return 'inactive';
        if ($approved === $total) return 'active';
        return 'pending';
    }
}