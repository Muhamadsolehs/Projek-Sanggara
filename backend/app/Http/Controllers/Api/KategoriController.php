<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Kategori;

class KategoriController extends Controller
{
    public function index()
    {
        try {
            $kategori = Kategori::withCount('produk')->get()->map(function ($k) {
                return [
                    'id_kategori' => $k->id_kategori,
                    'nama_kategori' => $k->nama_kategori,
                    'slug' => $k->slug,
                    'deskripsi' => $k->deskripsi,
                    'total_produk' => $k->produk_count,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $kategori,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
