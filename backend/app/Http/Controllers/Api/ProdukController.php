<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Produk;

class ProdukController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Produk::with('kategori')
                ->orderByRaw('COALESCE(updated_at, created_at) DESC')
                ->orderByDesc('id_produk');

            if ($request->has('kategori') && !empty($request->kategori)) {
                $query->where('id_kategori', $request->kategori);
            }

            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            if ($request->has('q') && !empty($request->q)) {
                $q = $request->q;
                $query->where(function ($sub) use ($q) {
                    $sub->where('nama_produk', 'like', "%{$q}%")
                        ->orWhere('kode_produk', 'like', "%{$q}%");
                });
            }

            $produk = $query->get()->map(function ($p) {
                $status = $p->status;
                if ($status !== 'maintenance') {
                    if ((int)$p->stok_siap <= 0) {
                        $status = 'habis';
                    } elseif ($status === 'habis' && (int)$p->stok_siap > 0) {
                        $status = 'tersedia';
                    }
                }
                return [
                    'id_produk' => $p->id_produk,
                    'kode_produk' => $p->kode_produk,
                    'nama_produk' => $p->nama_produk,
                    'kategori' => $p->kategori ? $p->kategori->nama_kategori : '-',
                    'kualitas' => $p->kualitas,
                    'harga_sewa' => (float)$p->harga_sewa,
                    'harga_formatted' => 'Rp ' . number_format($p->harga_sewa, 0, ',', '.'),
                    'denda_per_hari' => (float)$p->denda_per_hari,
                    'stok_total' => $p->stok_total,
                    'stok_siap' => $p->stok_siap,
                    'stok_disewa' => $p->stok_disewa,
                    'stok_cuci' => $p->stok_cuci,
                    'stok_rusak' => $p->stok_rusak,
                    'foto_url' => $p->foto_url,
                    'foto_produk' => $p->foto_produk,
                    'status' => $status,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $produk,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nama_produk' => 'required|string|max:150',
                'id_kategori' => 'nullable|integer',
                'kategori' => 'nullable|string',
                'kualitas' => 'nullable|string|max:60',
                'harga_sewa' => 'required|numeric|min:0',
                'denda_per_hari' => 'nullable|numeric|min:0',
                'stok_total' => 'nullable|integer|min:0',
                'stok_siap' => 'nullable|integer|min:0',
                'status' => 'nullable|string',
                'deskripsi' => 'nullable|string',
            ]);

            // Auto-resolve id_kategori jika dikirim nama atau slug
            if (empty($validated['id_kategori']) && !empty($request->kategori)) {
                $kat = \App\Models\Kategori::where('nama_kategori', 'like', "%{$request->kategori}%")
                    ->orWhere('slug', 'like', "%{$request->kategori}%")
                    ->first();
                if ($kat) {
                    $validated['id_kategori'] = $kat->id_kategori;
                }
            }
            if (empty($validated['id_kategori'])) {
                $firstKat = \App\Models\Kategori::first();
                $validated['id_kategori'] = $firstKat ? $firstKat->id_kategori : 1;
            }

            // Auto-generate kode_produk jika kosong
            if (empty($request->kode_produk)) {
                $nextId = (Produk::max('id_produk') ?? 0) + 1;
                $validated['kode_produk'] = 'PRD-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
            } else {
                $validated['kode_produk'] = $request->kode_produk;
            }

            // Sistem Single Item (Setiap produk adalah 1 item unik)
            $validated['status'] = !empty($validated['status']) ? strtolower($validated['status']) : 'tersedia';
            $validated['stok_total'] = 1;
            $validated['stok_siap'] = ($validated['status'] === 'tersedia') ? 1 : 0;
            $validated['stok_disewa'] = ($validated['status'] === 'habis' || $validated['status'] === 'disewa') ? 1 : 0;
            $validated['stok_cuci'] = 0;
            $validated['stok_rusak'] = ($validated['status'] === 'maintenance') ? 1 : 0;
            $validated['denda_per_hari'] = isset($validated['denda_per_hari']) ? (float)$validated['denda_per_hari'] : round($validated['harga_sewa'] * 0.15);

            if ($request->hasFile('foto_produk') || $request->filled('foto_produk')) {
                $validated['foto_produk'] = $this->handleFotoUpload($request);
            }

            $produk = Produk::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Produk baru berhasil ditambahkan',
                'data' => $produk
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan produk: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $p = Produk::with('kategori')->findOrFail($id);
            $status = $p->status;
            if ($status !== 'maintenance') {
                if ((int)$p->stok_siap <= 0) {
                    $status = 'habis';
                } elseif ($status === 'habis' && (int)$p->stok_siap > 0) {
                    $status = 'tersedia';
                }
            }
            return response()->json([
                'status' => 'success',
                'data' => [
                    'id_produk' => $p->id_produk,
                    'kode_produk' => $p->kode_produk,
                    'id_kategori' => $p->id_kategori,
                    'kategori' => $p->kategori ? $p->kategori->nama_kategori : '-',
                    'nama_produk' => $p->nama_produk,
                    'kualitas' => $p->kualitas,
                    'harga_sewa' => (float)$p->harga_sewa,
                    'harga_formatted' => 'Rp ' . number_format($p->harga_sewa, 0, ',', '.'),
                    'denda_per_hari' => (float)$p->denda_per_hari,
                    'denda_formatted' => 'Rp ' . number_format($p->denda_per_hari, 0, ',', '.'),
                    'stok_total' => $p->stok_total,
                    'stok_siap' => $p->stok_siap,
                    'stok_disewa' => $p->stok_disewa,
                    'stok_cuci' => $p->stok_cuci,
                    'stok_rusak' => $p->stok_rusak,
                    'foto_url' => $p->foto_url,
                    'status' => $status,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produk tidak ditemukan'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $p = Produk::findOrFail($id);

            $validated = $request->validate([
                'nama_produk' => 'sometimes|required|string|max:150',
                'id_kategori' => 'sometimes|nullable|integer',
                'kualitas' => 'sometimes|nullable|string|max:60',
                'harga_sewa' => 'sometimes|required|numeric|min:0',
                'status' => 'sometimes|required|string|in:tersedia,habis,disewa,maintenance',
                'foto_produk' => 'nullable',
                'hapus_foto' => 'nullable',
            ]);

            if ($request->hasFile('foto_produk') || $request->has('foto_produk') || $request->has('hapus_foto')) {
                $validated['foto_produk'] = $this->handleFotoUpload($request, $p);
            }

            // Sistem Single Item
            $targetStatus = $validated['status'] ?? $p->status;
            $validated['stok_total'] = 1;
            $validated['stok_siap'] = ($targetStatus === 'tersedia') ? 1 : 0;
            $validated['stok_disewa'] = ($targetStatus === 'habis' || $targetStatus === 'disewa') ? 1 : 0;
            $validated['stok_rusak'] = ($targetStatus === 'maintenance') ? 1 : 0;

            $p->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Data produk berhasil diperbarui',
                'data' => [
                    'id_produk' => $p->id_produk,
                    'kode_produk' => $p->kode_produk,
                    'nama_produk' => $p->nama_produk,
                    'kategori' => $p->kategori ? $p->kategori->nama_kategori : '-',
                    'kualitas' => $p->kualitas,
                    'harga_sewa' => (float)$p->harga_sewa,
                    'harga_formatted' => 'Rp ' . number_format($p->harga_sewa, 0, ',', '.'),
                    'stok_total' => $p->stok_total,
                    'stok_siap' => $p->stok_siap,
                    'status' => $p->status,
                    'foto_url' => $p->foto_url,
                    'foto_produk' => $p->foto_produk,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper untuk upload dan penyimpanan foto produk (file multipart atau base64)
     */
    private function handleFotoUpload(Request $request, ?Produk $produk = null)
    {
        // 1. File Upload (multipart/form-data)
        if ($request->hasFile('foto_produk')) {
            $file = $request->file('foto_produk');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $id = $produk ? $produk->id_produk : ((Produk::max('id_produk') ?? 0) + 1);
            $filename = 'prod_' . $id . '_' . time() . '.' . $ext;
            
            $publicUploadDir = public_path('uploads/produk');
            if (!file_exists($publicUploadDir)) {
                @mkdir($publicUploadDir, 0777, true);
            }
            $file->move($publicUploadDir, $filename);
            
            $rootUploadDir = base_path('../uploads/produk');
            if (!file_exists($rootUploadDir)) {
                @mkdir($rootUploadDir, 0777, true);
            }
            @copy($publicUploadDir . DIRECTORY_SEPARATOR . $filename, $rootUploadDir . DIRECTORY_SEPARATOR . $filename);
            
            return 'uploads/produk/' . $filename;
        }

        // 2. Base64 String Upload (JSON or Form field)
        if ($request->has('foto_produk') && is_string($request->foto_produk)) {
            $str = $request->foto_produk;
            if (preg_match('/^data:image\/(\w+);base64,/', $str, $matches)) {
                $ext = strtolower($matches[1]);
                if ($ext === 'jpeg') $ext = 'jpg';
                $base64Data = substr($str, strpos($str, ',') + 1);
                $decoded = base64_decode($base64Data);
                if ($decoded !== false) {
                    $id = $produk ? $produk->id_produk : ((Produk::max('id_produk') ?? 0) + 1);
                    $filename = 'prod_' . $id . '_' . time() . '.' . $ext;
                    $publicUploadDir = public_path('uploads/produk');
                    if (!file_exists($publicUploadDir)) {
                        @mkdir($publicUploadDir, 0777, true);
                    }
                    file_put_contents($publicUploadDir . DIRECTORY_SEPARATOR . $filename, $decoded);
                    
                    $rootUploadDir = base_path('../uploads/produk');
                    if (!file_exists($rootUploadDir)) {
                        @mkdir($rootUploadDir, 0777, true);
                    }
                    @copy($publicUploadDir . DIRECTORY_SEPARATOR . $filename, $rootUploadDir . DIRECTORY_SEPARATOR . $filename);
                    
                    return 'uploads/produk/' . $filename;
                }
            } elseif ($str === 'null' || $str === 'delete' || $str === 'hapus' || $str === '') {
                return null;
            } else {
                return $str;
            }
        }

        // 3. Permintaan eksplisit hapus foto
        if ($request->has('hapus_foto') && $request->hapus_foto) {
            return null;
        }

        return $produk ? $produk->foto_produk : null;
    }

    public function destroy($id)
    {
        try {
            $p = Produk::findOrFail($id);
            $nama = $p->nama_produk;
            $p->delete();

            return response()->json([
                'status' => 'success',
                'message' => "Produk {$nama} berhasil dihapus"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus produk: ' . $e->getMessage()
            ], 500);
        }
    }
}
