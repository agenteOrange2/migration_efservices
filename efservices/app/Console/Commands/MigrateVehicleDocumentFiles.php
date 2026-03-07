<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\MediaLibrary\CustomPathGenerator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MigrateVehicleDocumentFiles extends Command
{
    protected $signature = 'documents:migrate-to-vehicle 
        {--dry-run : Show what would be moved without actually moving}
        {--clean-orphans : Also clean up orphaned media records and files}';
    protected $description = 'Migrate files from storage/app/public/others/ to their correct paths based on CustomPathGenerator';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $cleanOrphans = $this->option('clean-orphans');
        $basePath = storage_path('app/public');
        $othersPath = $basePath . '/others';

        if (!File::isDirectory($othersPath)) {
            $this->info('No "others" directory found. Nothing to migrate.');
            return 0;
        }

        $pathGenerator = new CustomPathGenerator();
        $moved = 0;
        $skipped = 0;
        $errors = 0;
        $orphansCleaned = 0;

        // Scan each folder inside others/
        $dirs = File::directories($othersPath);
        $this->info('Found ' . count($dirs) . ' subdirectories in others/.');
        $this->info('');

        foreach ($dirs as $dir) {
            $folderName = basename($dir);
            $files = File::files($dir);

            if (empty($files)) {
                if (!$dryRun) {
                    File::deleteDirectory($dir);
                }
                continue;
            }

            foreach ($files as $file) {
                $fileName = $file->getFilename();

                // Find the media record that matches this file
                // The default path is others/{model_key}/ where model_key = model's primary key
                // But Spatie uses the media ID as folder in some configurations
                // Try to find by checking media records
                $mediaRecord = $this->findMediaRecord($folderName, $fileName);

                if (!$mediaRecord) {
                    $this->warn("  [ORPHAN] others/{$folderName}/{$fileName} - No matching media record found.");
                    if ($cleanOrphans && !$dryRun) {
                        File::delete($file->getPathname());
                        $orphansCleaned++;
                        $this->line("           -> Deleted orphan file.");
                    }
                    $skipped++;
                    continue;
                }

                // Load the Spatie Media model to use with PathGenerator
                $spatieMedia = Media::find($mediaRecord->id);
                if (!$spatieMedia || !$spatieMedia->model) {
                    $this->warn("  [ORPHAN] others/{$folderName}/{$fileName} - Media #{$mediaRecord->id} model not found (model_type={$mediaRecord->model_type}, model_id={$mediaRecord->model_id}).");
                    if ($cleanOrphans && !$dryRun) {
                        File::delete($file->getPathname());
                        DB::table('media')->where('id', $mediaRecord->id)->delete();
                        $orphansCleaned++;
                        $this->line("           -> Deleted orphan file and media record.");
                    }
                    $skipped++;
                    continue;
                }

                // Get the correct path from CustomPathGenerator
                $correctRelDir = $pathGenerator->getPath($spatieMedia);

                // If the correct path is still others/, skip (no rule defined for this model)
                if (str_starts_with($correctRelDir, 'others/')) {
                    $this->line("  [SKIP] others/{$folderName}/{$fileName} - PathGenerator still returns others/ for {$mediaRecord->model_type}");
                    $skipped++;
                    continue;
                }

                $targetDir = $basePath . '/' . $correctRelDir;
                $targetFile = $targetDir . $fileName;
                $sourceFile = $file->getPathname();

                if ($dryRun) {
                    $this->line("  [DRY-RUN] others/{$folderName}/{$fileName} -> {$correctRelDir}{$fileName}");
                    $this->line("            Model: {$mediaRecord->model_type} #{$mediaRecord->model_id} | Media #{$mediaRecord->id}");
                    $moved++;
                    continue;
                }

                try {
                    // Create target directory
                    if (!File::isDirectory($targetDir)) {
                        File::makeDirectory($targetDir, 0755, true);
                    }

                    // Check if target already exists - delete the old duplicate
                    if (File::exists($targetFile)) {
                        File::delete($sourceFile);
                        $this->info("  [DUP] Deleted duplicate: others/{$folderName}/{$fileName} (already at {$correctRelDir})");
                        $moved++;
                        continue;
                    }

                    // Copy file to new location
                    File::copy($sourceFile, $targetFile);

                    if (File::exists($targetFile)) {
                        // Delete old file
                        File::delete($sourceFile);

                        // Move conversions if they exist
                        $oldConversionsDir = $dir . '/conversions';
                        if (File::isDirectory($oldConversionsDir)) {
                            $targetConversionsDir = $targetDir . 'conversions/';
                            if (!File::isDirectory($targetConversionsDir)) {
                                File::makeDirectory($targetConversionsDir, 0755, true);
                            }
                            foreach (File::files($oldConversionsDir) as $convFile) {
                                $convTarget = $targetConversionsDir . $convFile->getFilename();
                                if (!File::exists($convTarget)) {
                                    File::copy($convFile->getPathname(), $convTarget);
                                }
                                File::delete($convFile->getPathname());
                            }
                            if (count(File::allFiles($oldConversionsDir)) === 0) {
                                File::deleteDirectory($oldConversionsDir);
                            }
                        }

                        $this->info("  [OK] others/{$folderName}/{$fileName} -> {$correctRelDir}{$fileName}");
                        $moved++;
                    } else {
                        $this->error("  [ERROR] Copy failed for others/{$folderName}/{$fileName}");
                        $errors++;
                    }
                } catch (\Exception $e) {
                    $this->error("  [ERROR] others/{$folderName}/{$fileName}: " . $e->getMessage());
                    Log::error('MigrateVehicleDocumentFiles error', [
                        'folder' => $folderName,
                        'file' => $fileName,
                        'error' => $e->getMessage(),
                    ]);
                    $errors++;
                }
            }
        }

        // Clean up empty directories in others/
        if (!$dryRun) {
            $this->info('');
            $this->info('Cleaning up empty directories...');
            $cleaned = 0;
            $dirs = File::directories($othersPath);
            foreach ($dirs as $dir) {
                if (count(File::allFiles($dir)) === 0) {
                    File::deleteDirectory($dir);
                    $cleaned++;
                }
            }
            if (File::isDirectory($othersPath) && count(File::allFiles($othersPath)) === 0 && count(File::directories($othersPath)) === 0) {
                File::deleteDirectory($othersPath);
                $this->info('Removed empty others/ directory.');
            }
            if ($cleaned > 0) {
                $this->info("Cleaned up {$cleaned} empty directories.");
            }
        }

        $this->info('');
        $this->info('=== Migration Summary ===');
        $this->info("Moved:            {$moved}");
        $this->info("Skipped:          {$skipped}");
        $this->info("Errors:           {$errors}");
        if ($cleanOrphans) {
            $this->info("Orphans cleaned:  {$orphansCleaned}");
        }

        if ($dryRun) {
            $this->warn('');
            $this->warn('This was a DRY RUN. No files were actually moved.');
            $this->info('Run without --dry-run to execute the migration.');
        }

        return 0;
    }

    /**
     * Find the media record that corresponds to a file in others/{folderName}/
     * The folder name is the model's primary key (getKey())
     */
    private function findMediaRecord(string $folderName, string $fileName)
    {
        // First try: find media where the file_name matches and the model_id matches the folder
        $media = DB::table('media')
            ->where('file_name', $fileName)
            ->where('model_id', $folderName)
            ->first();

        if ($media) {
            return $media;
        }

        // Second try: the folder might be the media ID itself
        $media = DB::table('media')
            ->where('id', $folderName)
            ->where('file_name', $fileName)
            ->first();

        if ($media) {
            return $media;
        }

        // Third try: search by file_name only in case folder doesn't match
        $media = DB::table('media')
            ->where('file_name', $fileName)
            ->first();

        return $media;
    }
}
