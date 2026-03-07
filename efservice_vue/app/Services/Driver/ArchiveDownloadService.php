<?php

namespace App\Services\Driver;

use App\Models\DriverArchive;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Archive Download Service
 * 
 * Handles generation and streaming of ZIP archives containing complete driver records.
 * Includes all documents, a PDF report, and metadata for offline storage.
 */
class ArchiveDownloadService
{
    /**
     * Generate a complete ZIP archive for a driver archive.
     * 
     * @param DriverArchive $archive
     * @return string Path to the generated ZIP file
     * @throws \Exception
     */
    public function generateArchiveZip(DriverArchive $archive): string
    {
        // Check for cached ZIP
        $cachedPath = $this->getCachedZipPath($archive);
        if ($cachedPath) {
            return $cachedPath;
        }

        $zip = new ZipArchive();
        $tempFile = tempnam(sys_get_temp_dir(), 'archive_');
        
        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Cannot create ZIP file');
        }

        try {
            // Add PDF report
            $pdfPath = $this->generatePdfReport($archive);
            $zip->addFile($pdfPath, 'driver_report.pdf');

            // Add documents by category
            $documents = $this->collectDocuments($archive);
            foreach ($documents as $category => $files) {
                foreach ($files as $file) {
                    if (isset($file['path']) && file_exists($file['path'])) {
                        $zip->addFile($file['path'], "{$category}/{$file['name']}");
                    }
                }
            }

            // Add metadata.json
            $metadata = $this->generateMetadata($archive);
            $zip->addFromString('metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));

            $zip->close();

            // Cache the ZIP file
            $this->cacheZip($archive, $tempFile);

            // Clean up PDF
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }

            return $tempFile;

        } catch (\Exception $e) {
            $zip->close();
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            throw $e;
        }
    }

    /**
     * Stream the ZIP archive to the browser.
     * 
     * @param DriverArchive $archive
     * @return StreamedResponse
     */
    public function streamArchiveZip(DriverArchive $archive): StreamedResponse
    {
        return response()->streamDownload(function () use ($archive) {
            $zipPath = $this->generateArchiveZip($archive);
            readfile($zipPath);
            
            // Clean up if not cached
            if (!$this->getCachedZipPath($archive)) {
                unlink($zipPath);
            }
        }, $archive->getArchiveFileName(), [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * Generate a PDF report with all driver information.
     * 
     * @param DriverArchive $archive
     * @return string Path to the generated PDF
     */
    protected function generatePdfReport(DriverArchive $archive): string
    {
        try {
            $data = [
                'archive' => $archive,
                'driver_data' => $archive->driver_data_snapshot,
                'licenses' => $archive->licenses_snapshot,
                'medical' => $archive->medical_snapshot,
                'certifications' => $archive->certifications_snapshot,
                'employment' => $archive->employment_history_snapshot,
                'training' => $archive->training_snapshot,
                'testing' => $archive->testing_snapshot,
                'accidents' => $archive->accidents_snapshot,
                'convictions' => $archive->convictions_snapshot,
                'inspections' => $archive->inspections_snapshot,
                'vehicle_assignments' => $archive->vehicle_assignments_snapshot,
            ];

            $pdf = Pdf::loadView('pdf.driver-archive-report', $data);
            
            $tempFile = tempnam(sys_get_temp_dir(), 'pdf_');
            $pdf->save($tempFile);
            
            return $tempFile;
        } catch (\Exception $e) {
            Log::error('Failed to generate PDF report', [
                'archive_id' => $archive->id,
                'error' => $e->getMessage(),
            ]);
            
            // Return empty temp file if PDF generation fails
            $tempFile = tempnam(sys_get_temp_dir(), 'pdf_');
            file_put_contents($tempFile, 'PDF generation failed: ' . $e->getMessage());
            return $tempFile;
        }
    }

    /**
     * Collect all documents organized by category.
     * 
     * @param DriverArchive $archive
     * @return array
     */
    protected function collectDocuments(DriverArchive $archive): array
    {
        $documentsByCategory = $archive->getDocumentsByCategory();
        $organized = [];

        foreach ($documentsByCategory as $categoryData) {
            $category = $categoryData['category'];
            $documents = $categoryData['documents'];

            $organized[$category] = [];

            foreach ($documents as $doc) {
                // Try to resolve the actual file path
                $filePath = null;

                if (isset($doc['path']) && Storage::disk('public')->exists($doc['path'])) {
                    $filePath = Storage::disk('public')->path($doc['path']);
                } elseif (isset($doc['url'])) {
                    // Try to extract path from URL
                    $urlPath = parse_url($doc['url'], PHP_URL_PATH);
                    if ($urlPath && Storage::disk('public')->exists($urlPath)) {
                        $filePath = Storage::disk('public')->path($urlPath);
                    }
                }

                if ($filePath && file_exists($filePath)) {
                    $organized[$category][] = [
                        'name' => $doc['name'],
                        'path' => $filePath,
                        'size' => $doc['size'] ?? filesize($filePath),
                    ];
                } else {
                    // Log missing document
                    Log::warning('Document not found for archive', [
                        'archive_id' => $archive->id,
                        'document' => $doc['name'],
                        'category' => $category,
                    ]);
                }
            }
        }

        return $organized;
    }

    /**
     * Generate metadata for the archive.
     * 
     * @param DriverArchive $archive
     * @return array
     */
    protected function generateMetadata(DriverArchive $archive): array
    {
        return [
            'archive_id' => $archive->id,
            'driver_name' => $archive->full_name,
            'driver_email' => $archive->email,
            'carrier_name' => $archive->carrier->name,
            'carrier_dot' => $archive->carrier->dot_number ?? null,
            'archived_at' => $archive->archived_at->toIso8601String(),
            'archive_reason' => $archive->archive_reason,
            'migration_to' => $archive->migrationRecord?->targetCarrier->name ?? null,
            'document_count' => $archive->getDocumentCount(),
            'generated_at' => now()->toIso8601String(),
            'format_version' => '1.0',
        ];
    }

    /**
     * Get cached ZIP path if it exists and is still valid.
     * 
     * @param DriverArchive $archive
     * @return string|null
     */
    protected function getCachedZipPath(DriverArchive $archive): ?string
    {
        $cacheKey = "archive_zip_{$archive->id}";
        $cachedPath = Cache::get($cacheKey);

        if ($cachedPath && file_exists($cachedPath)) {
            return $cachedPath;
        }

        return null;
    }

    /**
     * Cache the generated ZIP file for 24 hours.
     * 
     * @param DriverArchive $archive
     * @param string $zipPath
     * @return void
     */
    protected function cacheZip(DriverArchive $archive, string $zipPath): void
    {
        $cacheKey = "archive_zip_{$archive->id}";
        Cache::put($cacheKey, $zipPath, now()->addHours(24));
    }
}
