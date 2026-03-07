<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverW9Form;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class W9Controller extends Controller
{
    /**
     * Download the generated W-9 PDF
     */
    public function download(Request $request, DriverW9Form $driverW9Form): BinaryFileResponse
    {
        abort_unless($driverW9Form->pdf_path && file_exists($driverW9Form->pdf_path), 404);

        return response()->download(
            $driverW9Form->pdf_path,
            "W9_{$driverW9Form->name}_" . ($driverW9Form->signed_date ? $driverW9Form->signed_date->format('Y-m-d') : now()->format('Y-m-d')) . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }
}
