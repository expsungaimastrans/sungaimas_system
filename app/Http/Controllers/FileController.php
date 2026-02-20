<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    /**
     * Tampilkan file bukti bayar langsung di browser (inline)
     * tanpa harus download. Support jpg, png, pdf.
     */
    public function viewBukti(Request $request, string $path)
    {
        // Decode path (bisa punya sub-folder: bukti-bayar/xxx.jpg)
        $decoded = urldecode($path);

        if (!Storage::disk('public')->exists($decoded)) {
            abort(404, 'File tidak ditemukan.');
        }

        $fullPath  = Storage::disk('public')->path($decoded);
        $mimeType  = mime_content_type($fullPath);
        $ext       = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        // Hanya izinkan gambar dan PDF
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        if (!in_array($ext, $allowed)) {
            abort(403, 'Tipe file tidak diizinkan.');
        }

        // Tampilkan inline di browser (bukan force download)
        return response()->file($fullPath, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($fullPath) . '"',
        ]);
    }
}