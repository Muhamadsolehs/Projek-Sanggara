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
}
