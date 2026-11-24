<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Kategori;
use Illuminate\Support\Str;

class KategoriController extends Controller
{
    public function index()
    {
        try {
            $kategori = Kategori::withCount('produk')
                ->orderByRaw('COALESCE(updated_at, created_at) DESC')
                ->orderByDesc('id_kategori')
                ->get()
                ->map(function ($k) {
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

    public function show($id)
    {
        try {
            $k = Kategori::withCount('produk')->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id_kategori' => $k->id_kategori,
                    'nama_kategori' => $k->nama_kategori,
                    'slug' => $k->slug,
                    'deskripsi' => $k->deskripsi,
                    'total_produk' => $k->produk_count,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kategori tidak ditemukan: ' . $e->getMessage()
            ], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nama_kategori' => 'required|string|max:100',
                'slug' => 'nullable|string|max:120',
                'deskripsi' => 'nullable|string',
            ]);

            if (empty($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['nama_kategori']);
            }

            $k = Kategori::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Kategori berhasil ditambahkan',
                'data' => $k
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan kategori: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $k = Kategori::findOrFail($id);

            $validated = $request->validate([
                'nama_kategori' => 'sometimes|required|string|max:100',
                'slug' => 'nullable|string|max:120',
                'deskripsi' => 'nullable|string',
            ]);

            if (isset($validated['nama_kategori']) && empty($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['nama_kategori']);
            }

            $k->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Kategori berhasil diperbarui',
                'data' => $k
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui kategori: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $k = Kategori::withCount('produk')->findOrFail($id);

            if ($k->produk_count > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Kategori '{$k->nama_kategori}' tidak dapat dihapus karena masih digunakan oleh {$k->produk_count} produk."
                ], 422);
            }

            $nama = $k->nama_kategori;
            $k->delete();

            return response()->json([
                'status' => 'success',
                'message' => "Kategori '{$nama}' berhasil dihapus"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus kategori: ' . $e->getMessage()
            ], 500);
        }
    }
}
