<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    // =====================
    // INDEX
    // =====================
    public function index(Request $request)
    {
        $q    = trim((string) $request->query('q', ''));
        $tipe = trim((string) $request->query('tipe', ''));

        $customers = Customer::query()
            ->when($q, fn($query) => $query->where(function ($query) use ($q) {
                $query->where('nama', 'like', "%{$q}%")
                      ->orWhere('tujuan', 'like', "%{$q}%")
                      ->orWhere('no_telp', 'like', "%{$q}%");
            }))
            ->when($tipe, fn($query) => $query->where('tipe', $tipe))
            ->orderBy('nama')
            ->paginate(20)
            ->withQueryString();

        return view('customers.index', compact('customers', 'q', 'tipe'));
    }

    // =====================
    // CREATE / STORE
    // =====================
    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        // Support JSON request (dari AJAX modal)
        if ($request->isJson()) {
            $request->merge($request->json()->all());
        }

        $data = $request->validate([
            'nama'     => 'required|string|max:255',
            'tipe'     => 'required|in:PENGIRIM,PENERIMA',
            'no_telp'  => 'nullable|string|max:20',
            'tujuan'   => 'nullable|string|max:100',
            'alamat'   => 'nullable|string|max:500',
            'catatan'  => 'nullable|string|max:500',
        ]);

        // Cek duplikat nama + tipe
        $exists = Customer::where('nama', $data['nama'])
                          ->where('tipe', $data['tipe'])
                          ->exists();
        if ($exists) {
            return back()->withInput()->with('error', "Customer \"{$data['nama']}\" ({$data['tipe']}) sudah ada.");
        }

        Customer::create($data);

        return redirect()->route('customers.index')
                         ->with('success', "Customer \"{$data['nama']}\" berhasil ditambahkan.");
    }

    // =====================
    // EDIT / UPDATE
    // =====================
    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'nama'     => 'required|string|max:255',
            'tipe'     => 'required|in:PENGIRIM,PENERIMA',
            'no_telp'  => 'nullable|string|max:20',
            'tujuan'   => 'nullable|string|max:100',
            'alamat'   => 'nullable|string|max:500',
            'catatan'  => 'nullable|string|max:500',
        ]);

        // Cek duplikat nama + tipe (exclude self)
        $exists = Customer::where('nama', $data['nama'])
                          ->where('tipe', $data['tipe'])
                          ->where('id', '!=', $customer->id)
                          ->exists();
        if ($exists) {
            return back()->withInput()->with('error', "Customer \"{$data['nama']}\" ({$data['tipe']}) sudah ada.");
        }

        $customer->update($data);

        return redirect()->route('customers.index')
                         ->with('success', "Customer \"{$customer->nama}\" berhasil diperbarui.");
    }

    // =====================
    // DESTROY
    // =====================
    public function destroy(Customer $customer)
    {
        $nama = $customer->nama;
        $customer->delete();

        return redirect()->route('customers.index')
                         ->with('success', "Customer \"{$nama}\" berhasil dihapus.");
    }

    // =====================
    // SHOW (riwayat nota)
    // =====================
    public function show(Customer $customer)
    {
        $shipments = collect();

        if ($customer->tipe === 'PENERIMA') {
            $shipments = Shipment::where('nama_penerima', $customer->nama)
                ->orderBy('tanggal', 'desc')
                ->paginate(20);
        } else {
            $shipments = Shipment::where('nama_pengirim', $customer->nama)
                ->orderBy('tanggal', 'desc')
                ->paginate(20);
        }

        // Statistik
        $stats = Shipment::when($customer->tipe === 'PENERIMA',
                fn($q) => $q->where('nama_penerima', $customer->nama),
                fn($q) => $q->where('nama_pengirim', $customer->nama)
            )
            ->selectRaw('COUNT(*) as total_nota, SUM(harga_total) as total_nilai')
            ->first();

        return view('customers.show', compact('customer', 'shipments', 'stats'));
    }

    // =====================
    // API SEARCH (untuk autocomplete di form nota)
    // =====================
    public function apiSearch(Request $request)
    {
        $q    = trim((string) $request->query('q', ''));
        $tipe = trim((string) $request->query('tipe', '')); // PENGIRIM / PENERIMA

        if (strlen($q) < 1) {
            return response()->json([]);
        }

        $results = Customer::query()
            ->when($tipe, fn($query) => $query->where('tipe', $tipe))
            ->where(function ($query) use ($q) {
                $query->where('nama', 'like', "%{$q}%")
                      ->orWhere('tujuan', 'like', "%{$q}%");
            })
            ->orderByRaw("CASE WHEN nama LIKE ? THEN 0 ELSE 1 END", ["{$q}%"])
            ->orderBy('nama')
            ->limit(10)
            ->get(['id', 'nama', 'tipe', 'no_telp', 'tujuan', 'alamat']);

        return response()->json($results);
    }

    // =====================
    // IMPORT CSV
    // =====================
    public function importForm()
    {
        return view('customers.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file    = $request->file('file');
        $handle  = fopen($file->getPathname(), 'r');
        $header  = null;
        $inserted = 0;
        $skipped  = 0;
        $errors   = [];

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            // Baca header baris pertama
            if ($header === null) {
                $header = array_map('strtolower', array_map('trim', $row));
                continue;
            }

            if (count($row) < count($header)) continue;

            $data = array_combine($header, $row);

            $nama  = trim($data['nama'] ?? '');
            $tipe  = strtoupper(trim($data['tipe'] ?? ''));
            $telp  = trim($data['no_telp'] ?? $data['telp'] ?? '');
            $tujuan= trim($data['tujuan'] ?? '');
            $alamat= trim($data['alamat'] ?? '');
            $catatan = trim($data['catatan'] ?? '');

            if (!$nama || !in_array($tipe, ['PENGIRIM', 'PENERIMA'])) {
                $errors[] = "Baris dilewati: nama='{$nama}' tipe='{$tipe}'";
                $skipped++;
                continue;
            }

            $exists = Customer::where('nama', $nama)->where('tipe', $tipe)->exists();
            if ($exists) {
                $skipped++;
                continue;
            }

            Customer::create([
                'nama'    => $nama,
                'tipe'    => $tipe,
                'no_telp' => $telp ?: null,
                'tujuan'  => $tujuan ?: null,
                'alamat'  => $alamat ?: null,
                'catatan' => $catatan ?: null,
            ]);
            $inserted++;
        }

        fclose($handle);

        $msg = "Import selesai: {$inserted} ditambahkan, {$skipped} dilewati.";
        if ($errors) $msg .= ' Errors: ' . implode('; ', array_slice($errors, 0, 3));

        return redirect()->route('customers.index')->with('success', $msg);
    }

    // =====================
    // EXPORT CSV
    // =====================
    public function exportCsv(Request $request)
    {
        if (auth()->user()?->role !== 'owner') {
            abort(403, 'Hanya owner yang dapat export data customer.');
        }

        $tipe = trim((string) $request->query('tipe', ''));

        $customers = Customer::query()
            ->when($tipe, fn($q) => $q->where('tipe', $tipe))
            ->orderBy('tipe')->orderBy('nama')
            ->get();

        $filename = 'customers-' . now()->format('Ymd-His') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ];

        $callback = function () use ($customers) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

            fputcsv($out, ['nama', 'tipe', 'no_telp', 'tujuan', 'alamat', 'catatan']);

            foreach ($customers as $c) {
                fputcsv($out, [
                    $c->nama,
                    $c->tipe,
                    $c->no_telp,
                    $c->tujuan,
                    $c->alamat,
                    $c->catatan,
                ]);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}