<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pelanggan;

class PelangganController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Pelanggan::with('kecamatan');

            if ($request->has('q') && !empty($request->q)) {
                $q = $request->q;
                $query->where(function ($sub) use ($q) {
                    $sub->where('nama_pelanggan', 'like', "%{$q}%")
                        ->orWhere('kode_pelanggan', 'like', "%{$q}%")
                        ->orWhere('no_telepon', 'like', "%{$q}%")
                        ->orWhere('nama_kecamatan', 'like', "%{$q}%")
                        ->orWhere('alamat_lengkap', 'like', "%{$q}%");
                });
            }

            $pelanggan = $query->get()->map(function ($p) {
                return [
                    'id_pelanggan' => $p->id_pelanggan,
                    'kode_pelanggan' => $p->kode_pelanggan,
                    'nama_pelanggan' => $p->nama_pelanggan,
                    'no_telepon' => $p->no_telepon,
                    'id_kecamatan' => $p->id_kecamatan,
                    'kecamatan' => $p->nama_kecamatan ?? ($p->kecamatan ? $p->kecamatan->nama_kecamatan : '-'),
                    'kelurahan' => $p->kelurahan_desa,
                    'alamat_lengkap' => $p->alamat_lengkap,
                    'total_transaksi' => $p->total_transaksi,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $pelanggan,
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
            $p = Pelanggan::with(['kecamatan', 'bookings'])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id_pelanggan' => $p->id_pelanggan,
                    'kode_pelanggan' => $p->kode_pelanggan,
                    'nama_pelanggan' => $p->nama_pelanggan,
                    'no_telepon' => $p->no_telepon,
                    'id_kecamatan' => $p->id_kecamatan,
                    'kecamatan' => $p->nama_kecamatan ?? ($p->kecamatan ? $p->kecamatan->nama_kecamatan : '-'),
                    'kelurahan' => $p->kelurahan_desa,
                    'alamat_lengkap' => $p->alamat_lengkap,
                    'total_transaksi' => $p->total_transaksi,
                    'created_at' => $p->created_at ? $p->created_at->format('Y-m-d H:i') : null,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pelanggan tidak ditemukan: ' . $e->getMessage()
            ], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nama_pelanggan' => 'required|string|max:100',
                'no_telepon' => 'required|string|max:30',
                'alamat_lengkap' => 'required|string',
                'nama_kecamatan' => 'nullable|string|max:100',
                'id_kecamatan' => 'nullable|integer',
                'kelurahan_desa' => 'nullable|string|max:100',
            ]);

            if (empty($request->kode_pelanggan)) {
                $nextId = (Pelanggan::max('id_pelanggan') ?? 0) + 1;
                $validated['kode_pelanggan'] = 'CUST-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
            } else {
                $validated['kode_pelanggan'] = $request->kode_pelanggan;
            }

            $validated['total_transaksi'] = 0;

            $p = Pelanggan::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Pelanggan berhasil ditambahkan',
                'data' => $p
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan pelanggan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $p = Pelanggan::findOrFail($id);

            $validated = $request->validate([
                'nama_pelanggan' => 'sometimes|required|string|max:100',
                'no_telepon' => 'sometimes|required|string|max:30',
                'alamat_lengkap' => 'sometimes|required|string',
                'nama_kecamatan' => 'nullable|string|max:100',
                'id_kecamatan' => 'nullable|integer',
                'kelurahan_desa' => 'nullable|string|max:100',
            ]);

            $p->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Data pelanggan berhasil diperbarui',
                'data' => $p
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui pelanggan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $p = Pelanggan::withCount('bookings')->findOrFail($id);

            if ($p->bookings_count > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Pelanggan '{$p->nama_pelanggan}' tidak dapat dihapus karena masih memiliki {$p->bookings_count} riwayat booking/transaksi."
                ], 422);
            }

            $nama = $p->nama_pelanggan;
            $p->delete();

            return response()->json([
                'status' => 'success',
                'message' => "Pelanggan '{$nama}' berhasil dihapus"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus pelanggan: ' . $e->getMessage()
            ], 500);
        }
    }
}
