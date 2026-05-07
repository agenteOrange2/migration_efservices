<?php
// app/Services/TempUploadService.php

namespace App\Services\Admin;

use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class TempUploadService
{
    /**
     * Almacena un archivo temporalmente y devuelve su información
     */
    public function store(UploadedFile $file, string $folder = 'temp')
    {
        $filename = Str::random(20) . '.' . $file->extension();
        $path = $file->storeAs($folder, $filename, 'public');
        
        // Aplicar compresión automática si es una imagen
        $this->compressAndResizeImage($file, $path);
        
        // Crear un token único para este archivo
        $token = Str::random(20);
        
        // En Laravel 11, es recomendable usar sesiones de manera explícita
        $tempFiles = Session::get('temp_files', []);
        $tempFiles[$token] = [
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'created_at' => now()->toDateTimeString(),
        ];
        Session::put('temp_files', $tempFiles);
        
        return [
            'token' => $token,
            'name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'url' => Storage::disk('public')->url($path)
        ];
    }
    
    /**
     * Recupera un archivo temporal por su token
     */
    public function get(string $token)
    {
        $tempFiles = Session::get('temp_files', []);
        return $tempFiles[$token] ?? null;
    }
    
    /**
     * Transfiere un archivo temporal a su destino permanente
     */
    public function moveToPermanent(string $token)
    {
        try {
            $tempFiles = Session::get('temp_files', []);
            $tempFile  = $tempFiles[$token] ?? null;

            if (!$tempFile) {
                Log::warning('TempUploadService: token not found in session');
                return false;
            }

            $sourcePath = Storage::disk($tempFile['disk'])->path($tempFile['path']);

            if (!file_exists($sourcePath)) {
                Log::error('TempUploadService: temp file missing from disk', [
                    'disk' => $tempFile['disk'],
                ]);
                return false;
            }

            // Consume the token so it cannot be reused
            unset($tempFiles[$token]);
            Session::put('temp_files', $tempFiles);

            return $sourcePath;
        } catch (\Exception $e) {
            Log::error('TempUploadService: moveToPermanent failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
    
    /**
     * Limpia archivos temporales viejos
     */
    public function cleanOldFiles($hours = 24)
    {
        $tempFiles = Session::get('temp_files', []);
        $cleaned = [];
        
        foreach ($tempFiles as $token => $file) {
            $createdAt = isset($file['created_at']) ? new \DateTime($file['created_at']) : null;
            
            if ($createdAt && (new \DateTime())->diff($createdAt)->h > $hours) {
                Storage::disk($file['disk'])->delete($file['path']);
                // No incluimos en el array limpio
            } else {
                $cleaned[$token] = $file;
            }
        }
        
        Session::put('temp_files', $cleaned);
        
        return count($tempFiles) - count($cleaned);
    }

    /**
     * Comprime y redimensiona una imagen
     */
    private function compressAndResizeImage(UploadedFile $file, string $tempPath): bool
    {
        try {
            // Verificar si es una imagen
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $extension = strtolower($file->getClientOriginalExtension());
            
            if (!in_array($extension, $imageExtensions)) {
                return true;
            }

            $manager   = new ImageManager(new Driver());
            $fullPath  = Storage::disk('public')->path($tempPath);
            $image     = $manager->read($fullPath);

            if ($image->width() > 800) {
                $image->scaleDown(width: 800);
            }

            $image->toJpeg(80)->save($fullPath);

            return true;
        } catch (\Exception $e) {
            Log::error('TempUploadService: image compression failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}