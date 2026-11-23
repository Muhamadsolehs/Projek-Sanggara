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
                        ->orWhere('no_telepon', 'like', "%{$q}%");
                });
            }

            $pelanggan = $query->get()->map(function ($p) {
                return [
                    'id_pelanggan' => $p->id_pelanggan,
                    'kode_pelanggan' => $p->kode_pelanggan,
                    'nama_pelanggan' => $p->nama_pelanggan,
                    'no_telepon' => $p->no_telepon,
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
}
