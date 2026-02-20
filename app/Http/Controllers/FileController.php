<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FileController extends Controller
{
    public function viewBukti(Request $request, string $path)
    {
        // Decode URL encoding
        $decoded = urldecode($path);

        Log::info('FileController::viewBukti', [
            'raw_path'    => $path,
            'decoded'     => $decoded,
            'disk_root'   => Storage::disk('public')->path(''),
            'file_exists' => Storage::disk('public')->exists($decoded),
        ]);

        // Coba disk public dulu
        if (Storage::disk('public')->exists($decoded)) {
            $fullPath = Storage::disk('public')->path($decoded);
            return $this->streamFile($fullPath);
        }

        // Fallback: coba path langsung (storage/app/public/...)
        $directPath = storage_path('app/public/' . $decoded);
        if (file_exists($directPath)) {
            return $this->streamFile($directPath);
        }

        // Fallback: coba public_path/storage/...
        $publicPath = public_path('storage/' . $decoded);
        if (file_exists($publicPath)) {
            return $this->streamFile($publicPath);
        }

        Log::warning('FileController: file not found', [
            'decoded'     => $decoded,
            'disk_path'   => Storage::disk('public')->path($decoded),
            'direct_path' => $directPath,
            'public_path' => $publicPath,
        ]);

        abort(404, 'File tidak ditemukan: ' . $decoded);
    }

    private function streamFile(string $fullPath): \Symfony\Component\HttpFoundation\Response
    {
        $ext      = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'pdf', 'webp'];

        if (!in_array($ext, $allowed)) {
            abort(403, 'Tipe file tidak diizinkan.');
        }

        $mimeMap = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'pdf'  => 'application/pdf',
            'webp' => 'image/webp',
        ];

        $mimeType = $mimeMap[$ext] ?? mime_content_type($fullPath);

        return response()->file($fullPath, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($fullPath) . '"',
            'Cache-Control'       => 'private, max-age=3600',
        ]);
    }
}