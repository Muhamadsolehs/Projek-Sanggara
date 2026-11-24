<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Kecamatan;
use App\Models\Booking;

class KecamatanController extends Controller
{
    /**
     * Mengambil daftar master 17 Kecamatan di Purwakarta
     */
    public function index(Request $request)
    {
        try {
            $query = Kecamatan::query();

            if ($request->has('zona') && !empty($request->zona)) {
                $query->where('zona_wilayah', $request->zona);
            }

            // Urutkan alfabetis atau berdasarkan id
            $sortBy = $request->get('sort', 'nama_kecamatan');
            if ($sortBy === 'id') {
                $query->orderBy('id_kecamatan', 'asc');
            } else {
                $query->orderBy('nama_kecamatan', 'asc');
            }

            $kecamatan = $query->get()->map(function ($k) {
                $cleanName = preg_replace('/^Kec\.\s*/i', '', $k->nama_kecamatan);
                return [
                    'id_kecamatan' => $k->id_kecamatan,
                    'nama_kecamatan' => $k->nama_kecamatan,
                    'nama_bersih' => $cleanName,
                    'zona_wilayah' => $k->zona_wilayah,
                    'target_pasar' => $k->target_pasar,
                ];
            });

            return response()->json([
                'status' => 'success',
                'total' => $kecamatan->count(),
                'data' => $kecamatan,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat data kecamatan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mengambil data analisa pelanggan & pasar berdasarkan 17 Kecamatan Purwakarta
     * Untuk kebutuhan visualisasi di pages/analisa-pelanggan.html
     */
    public function analisa(Request $request)
    {
        try {
            $totalSemuaBooking = Booking::count();
            if ($totalSemuaBooking === 0) {
                $totalSemuaBooking = 1;
            }

            // 1. Peringkat 17 Kecamatan dari view v_ranking_kecamatan atau query dinamis
            $ranking = DB::table('kecamatan as k')
                ->leftJoin('pelanggan as p', 'k.id_kecamatan', '=', 'p.id_kecamatan')
                ->leftJoin('booking as b', 'p.id_pelanggan', '=', 'b.id_pelanggan')
                ->select(
                    'k.id_kecamatan',
                    'k.nama_kecamatan',
                    'k.zona_wilayah',
                    'k.target_pasar',
                    DB::raw('COUNT(b.id_booking) as total_booking'),
                    DB::raw('COALESCE(SUM(b.total_tagihan), 0) as total_omset')
                )
                ->groupBy('k.id_kecamatan', 'k.nama_kecamatan', 'k.zona_wilayah', 'k.target_pasar')
                ->orderByDesc('total_booking')
                ->orderBy('k.id_kecamatan', 'asc')
                ->get();

            $maxBooking = $ranking->max('total_booking');
            if ($maxBooking <= 0) $maxBooking = 1;

            $rankingData = $ranking->map(function ($r, $index) use ($totalSemuaBooking, $maxBooking) {
                $cleanName = preg_replace('/^Kec\.\s*/i', '', $r->nama_kecamatan);
                $pct = round(($r->total_booking / $totalSemuaBooking) * 100, 1);
                $barWidth = round(($r->total_booking / $maxBooking) * 85, 1);
                if ($r->total_booking > 0 && $barWidth < 12) $barWidth = 12;

                // Tentukan warna bar berdasarkan peringkat
                $rank = $index + 1;
                $color = '#64748b'; // default low
                if ($rank <= 3) {
                    $color = '#00b87c'; // top 1-3
                } elseif ($rank <= 8) {
                    $color = '#0d9488'; // mid-high 4-8
                } elseif ($rank <= 13) {
                    $color = '#0284c7'; // mid 9-13
                }

                return [
                    'rank' => $rank,
                    'id_kecamatan' => $r->id_kecamatan,
                    'nama_kecamatan' => $r->nama_kecamatan,
                    'nama_singkat' => $cleanName,
                    'zona_wilayah' => $r->zona_wilayah,
                    'total_booking' => (int)$r->total_booking,
                    'total_omset' => (float)$r->total_omset,
                    'total_omset_formatted' => 'Rp ' . number_format($r->total_omset, 0, ',', '.'),
                    'persentase_pasar' => $pct,
                    'bar_width_pct' => $barWidth,
                    'bar_color' => $color,
                ];
            });

            // 2. Sebaran Zona Wilayah (Klaster 17 Kecamatan)
            $zonaList = DB::table('kecamatan as k')
                ->leftJoin('pelanggan as p', 'k.id_kecamatan', '=', 'p.id_kecamatan')
                ->leftJoin('booking as b', 'p.id_pelanggan', '=', 'b.id_pelanggan')
                ->select(
                    'k.zona_wilayah',
                    DB::raw('COUNT(b.id_booking) as booking_count'),
                    DB::raw('COUNT(DISTINCT k.id_kecamatan) as kecamatan_count')
                )
                ->groupBy('k.zona_wilayah')
                ->get();

            $totalZonaBooking = $zonaList->sum('booking_count');
            if ($totalZonaBooking <= 0) $totalZonaBooking = 1;

            $zonaColors = [
                'Pusat Kota' => '#00b87c',
                'Industri & Utara' => '#0284c7',
                'Danau & Wisata' => '#38bdf8',
                'Selatan Pegunungan' => '#f59e0b',
            ];

            $sebaranZona = $zonaList->map(function ($z) use ($totalZonaBooking, $zonaColors) {
                $pct = round(($z->booking_count / $totalZonaBooking) * 100, 1);
                return [
                    'zona' => $z->zona_wilayah,
                    'booking_count' => (int)$z->booking_count,
                    'kecamatan_count' => (int)$z->kecamatan_count,
                    'persentase' => $pct,
                    'color' => $zonaColors[$z->zona_wilayah] ?? '#64748b',
                ];
            });

            // 3. Top 3 Wilayah Teratas dengan info busana favorit
            $top3 = $rankingData->take(3)->map(function ($r) {
                // Cari produk terpopuler di kecamatan ini
                $favorit = DB::table('booking as b')
                    ->join('pelanggan as p', 'b.id_pelanggan', '=', 'p.id_pelanggan')
                    ->join('detail_booking as db', 'b.id_booking', '=', 'db.id_booking')
                    ->join('produk as pr', 'db.id_produk', '=', 'pr.id_produk')
                    ->where('p.id_kecamatan', $r['id_kecamatan'])
                    ->select('pr.nama_produk', DB::raw('SUM(db.jumlah) as total_qty'))
                    ->groupBy('pr.id_produk', 'pr.nama_produk')
                    ->orderByDesc('total_qty')
                    ->first();

                return [
                    'rank' => $r['rank'],
                    'id_kecamatan' => $r['id_kecamatan'],
                    'nama_kecamatan' => $r['nama_kecamatan'],
                    'nama_singkat' => $r['nama_singkat'],
                    'total_booking' => $r['total_booking'],
                    'total_omset_formatted' => $r['total_omset_formatted'],
                    'busana_favorit' => $favorit ? $favorit->nama_produk : 'Kostum Tari & Pengantin',
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_booking_keseluruhan' => Booking::count(),
                    'total_kecamatan' => 17,
                    'ranking_17_kecamatan' => $rankingData,
                    'sebaran_zona' => $sebaranZona,
                    'top_3' => $top3,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat analisa kecamatan: ' . $e->getMessage(),
            ], 500);
        }
    }
}
