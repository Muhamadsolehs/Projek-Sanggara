<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LaporanPemasukan;
use App\Models\LaporanPengeluaran;

class LaporanController extends Controller
{
    public function pemasukan(Request $request)
    {
        try {
            $query = LaporanPemasukan::with(['booking', 'user'])->orderBy('tanggal', 'desc');

            if ($request->has('kategori') && !empty($request->kategori)) {
                $query->where('kategori_pemasukan', $request->kategori);
            }

            if ($request->has('month') && !empty($request->month)) {
                $query->whereMonth('tanggal', $request->month);
            }

            $data = $query->get()->map(function ($p) {
                return [
                    'id_pemasukan' => $p->id_pemasukan,
                    'no_transaksi' => $p->no_transaksi,
                    'id_booking' => $p->id_booking,
                    'tanggal' => $p->tanggal ? $p->tanggal->format('Y-m-d') : '-',
                    'kategori_pemasukan' => $p->kategori_pemasukan,
                    'nominal' => (float)$p->nominal,
                    'nominal_formatted' => 'Rp ' . number_format($p->nominal, 0, ',', '.'),
                    'metode_pembayaran' => $p->metode_pembayaran,
                    'kasir' => $p->user ? $p->user->nama_lengkap : 'Staf',
                    'keterangan' => $p->keterangan,
                ];
            });

            return response()->json([
                'status' => 'success',
                'total_nominal' => $data->sum('nominal'),
                'total_nominal_formatted' => 'Rp ' . number_format($data->sum('nominal'), 0, ',', '.'),
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function pengeluaran(Request $request)
    {
        try {
            $query = LaporanPengeluaran::orderBy('tanggal', 'desc');

            if ($request->has('kategori') && !empty($request->kategori)) {
                $query->where('kategori_beban', $request->kategori);
            }

            $data = $query->get()->map(function ($p) {
                return [
                    'id_pengeluaran' => $p->id_pengeluaran,
                    'no_transaksi' => $p->no_transaksi,
                    'tanggal' => $p->tanggal ? $p->tanggal->format('Y-m-d') : '-',
                    'kategori_beban' => $p->kategori_beban,
                    'nominal' => (float)$p->nominal,
                    'nominal_formatted' => 'Rp ' . number_format($p->nominal, 0, ',', '.'),
                    'metode_pembayaran' => $p->metode_pembayaran,
                    'pic_staf' => $p->pic_staf,
                    'keterangan' => $p->keterangan,
                ];
            });

            return response()->json([
                'status' => 'success',
                'total_nominal' => $data->sum('nominal'),
                'total_nominal_formatted' => 'Rp ' . number_format($data->sum('nominal'), 0, ',', '.'),
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
