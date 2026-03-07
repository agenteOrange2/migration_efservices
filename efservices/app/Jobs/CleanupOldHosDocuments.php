<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupOldHosDocuments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of years to retain documents (FMCSA requirement: 7 years).
     */
    protected int $retentionYears = 7;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cutoffDate = Carbon::now()->subYears($this->retentionYears);
        
        Log::info('Starting HOS documents cleanup job', [
            'cutoff_date' => $cutoffDate->toDateTimeString(),
            'retention_years' => $this->retentionYears,
        ]);

        $collections = ['trip_reports', 'daily_logs', 'monthly_summaries'];
        $totalDeleted = 0;
        $totalArchived = 0;

        foreach ($collections as $collection) {
            $result = $this->cleanupCollection($collection, $cutoffDate);
            $totalDeleted += $result['deleted'];
            $totalArchived += $result['archived'];
        }

        Log::info('HOS documents cleanup job completed', [
            'total_deleted' => $totalDeleted,
            'total_archived' => $totalArchived,
            'cutoff_date' => $cutoffDate->toDateTimeString(),
        ]);
    }

    /**
     * Cleanup documents in a specific collection.
     */
    protected function cleanupCollection(string $collection, Carbon $cutoffDate): array
    {
        $deleted = 0;
        $archived = 0;

        // Get old documents from this collection
        $oldDocuments = Media::where('collection_name', $collection)
            ->where('created_at', '<', $cutoffDate)
            ->get();

        Log::info("Processing {$collection} collection", [
            'total_documents' => $oldDocuments->count(),
        ]);

        foreach ($oldDocuments as $document) {
            try {
                // Get document date from custom properties
                $documentDate = $document->getCustomProperty('document_date') 
                    ?? $document->created_at->format('Y-m-d');

                // Archive to long-term storage before deletion
                if ($this->archiveDocument($document)) {
                    $archived++;
                    
                    // Delete from active storage
                    $document->delete();
                    $deleted++;
                    
                    Log::info("Document archived and deleted", [
                        'collection' => $collection,
                        'document_id' => $document->id,
                        'document_date' => $documentDate,
                        'file_name' => $document->file_name,
                    ]);
                } else {
                    Log::warning("Failed to archive document, skipping deletion", [
                        'collection' => $collection,
                        'document_id' => $document->id,
                        'file_name' => $document->file_name,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Error processing document for cleanup", [
                    'collection' => $collection,
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'deleted' => $deleted,
            'archived' => $archived,
        ];
    }

    /**
     * Archive document to long-term storage.
     */
    protected function archiveDocument(Media $document): bool
    {
        try {
            // Create archive path structure: archive/YYYY/MM/collection/filename
            $documentDate = $document->getCustomProperty('document_date') 
                ? Carbon::parse($document->getCustomProperty('document_date'))
                : $document->created_at;

            $archivePath = sprintf(
                'hos-archive/%d/%02d/%s/%s',
                $documentDate->year,
                $documentDate->month,
                $document->collection_name,
                $document->file_name
            );

            // Copy file to archive location
            $fileContents = Storage::disk('public')->get($document->getPath());
            Storage::disk('public')->put($archivePath, $fileContents);

            // Create metadata file for the archived document
            $metadata = [
                'original_id' => $document->id,
                'collection' => $document->collection_name,
                'file_name' => $document->file_name,
                'size' => $document->size,
                'mime_type' => $document->mime_type,
                'custom_properties' => $document->custom_properties,
                'created_at' => $document->created_at->toDateTimeString(),
                'archived_at' => now()->toDateTimeString(),
                'model_type' => $document->model_type,
                'model_id' => $document->model_id,
            ];

            $metadataPath = str_replace('.pdf', '.json', $archivePath);
            Storage::disk('public')->put($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to archive document", [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get the retention period in years.
     */
    public function getRetentionYears(): int
    {
        return $this->retentionYears;
    }

    /**
     * Set custom retention period (for testing or special cases).
     */
    public function setRetentionYears(int $years): self
    {
        $this->retentionYears = $years;
        return $this;
    }
}
