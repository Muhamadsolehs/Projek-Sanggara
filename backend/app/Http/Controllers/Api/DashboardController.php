<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Booking;
use App\Models\Produk;
use App\Models\Kecamatan;
use App\Models\LaporanPemasukan;
use App\Models\LaporanPengeluaran;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            // 1. Surplus Keuangan Bersih
            $totalPemasukan = LaporanPemasukan::sum('nominal');
            $totalPengeluaran = LaporanPengeluaran::sum('nominal');
            $surplusBersih = $totalPemasukan - $totalPengeluaran;
            $margin = $totalPemasukan > 0 ? round(($surplusBersih / $totalPemasukan) * 100, 1) : 0;

            // 2. Total Transaksi Booking
            $totalBooking = Booking::count();
            $lunasCount = Booking::where('status_pembayaran', 'LUNAS')->count();
            $dpCount = Booking::where('status_pembayaran', 'DP')->count();
            $persenLunas = $totalBooking > 0 ? round(($lunasCount / $totalBooking) * 100, 1) : 0;
            $persenDp = $totalBooking > 0 ? round(($dpCount / $totalBooking) * 100, 1) : 0;

            // 3. Pasar Terbesar (Kecamatan)
            $topKecamatan = DB::table('v_ranking_kecamatan')->first();
            $totalKecamatan = Kecamatan::count();

            // 4. Kesiapan Stok Busana
            $statusStok = DB::table('v_status_inventaris')->first();

            // 5. Arus Kas Bulanan (12 Bulan untuk chart Jan - Des)
            $arusKas = DB::table('v_arus_kas_bulanan')->get();

            // 6. Top 5 Kecamatan
            $top5Kecamatan = DB::table('v_ranking_kecamatan')->take(5)->get();

            // 7. Kategori Terlaris
            $kategoriStats = DB::table('detail_booking as db')
                ->join('produk as p', 'db.id_produk', '=', 'p.id_produk')
                ->join('kategori as k', 'p.id_kategori', '=', 'k.id_kategori')
                ->select('k.nama_kategori', DB::raw('SUM(db.jumlah) as total_sewa'))
                ->groupBy('k.id_kategori', 'k.nama_kategori')
                ->orderByDesc('total_sewa')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'cards' => [
                        'surplus_keuangan' => [
                            'nominal' => $surplusBersih,
                            'formatted' => 'Rp ' . number_format($surplusBersih, 0, ',', '.'),
                            'margin' => $margin . '%',
                            'total_pemasukan' => $totalPemasukan,
                        ],
                        'transaksi_booking' => [
                            'total' => $totalBooking,
                            'lunas_count' => $lunasCount,
                            'dp_count' => $dpCount,
                            'persen_lunas' => $persenLunas . '%',
                            'persen_dp' => $persenDp . '%',
                        ],
                        'pasar_terbesar' => [
                            'nama_kecamatan' => $topKecamatan ? $topKecamatan->nama_kecamatan : 'Kec. Purwakarta',
                            'total_kecamatan' => $totalKecamatan,
                            'total_booking' => $topKecamatan ? $topKecamatan->total_booking : 0,
                            'persentase_pasar' => $topKecamatan ? $topKecamatan->persentase_pasar . '%' : '0%',
                        ],
                        'kesiapan_stok' => [
                            'siap_sewa' => $statusStok ? (int)$statusStok->siap_sewa_unit : 0,
                            'total_unit' => $statusStok ? (int)$statusStok->total_unit : 0,
                            'disewa' => $statusStok ? (int)$statusStok->sedang_disewa_unit : 0,
                            'persen_siap' => $statusStok ? $statusStok->persen_siap_sewa . '%' : '0%',
                        ],
                    ],
                    'arus_kas_bulanan' => $arusKas,
                    'top_kecamatan' => $top5Kecamatan,
                    'kategori_terlaris' => $kategoriStats,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
