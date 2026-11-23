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
            $query = Produk::with('kategori');

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
                    'status' => $p->status,
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

    public function show($id)
    {
        try {
            $p = Produk::with('kategori')->findOrFail($id);
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
                    'status' => $p->status,
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
                'stok_total' => 'sometimes|nullable|integer|min:0',
                'stok_siap' => 'sometimes|nullable|integer|min:0',
                'status' => 'sometimes|required|string|in:tersedia,habis,maintenance',
            ]);

            $p->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Data produk berhasil diperbarui',
                'data' => $p
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
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
