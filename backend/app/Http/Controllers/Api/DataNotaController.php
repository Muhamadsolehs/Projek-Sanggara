<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Booking;
use App\Models\PembayaranTransaksi;
use App\Models\User;

class DataNotaController extends Controller
{
    /**
     * Mengambil daftar data nota transaksi untuk tabel di pages/data-nota.html
     */
    public function index(Request $request)
    {
        try {
            $query = Booking::with(['pelanggan', 'user', 'pembayaran'])
                ->orderByRaw('COALESCE(updated_at, created_at) DESC')
                ->orderByDesc('id_booking');

            // Pencarian kata kunci (no_nota, nama pelanggan, nama kasir)
            if ($request->has('q') && !empty($request->q)) {
                $q = $request->q;
                $query->where(function ($sub) use ($q) {
                    $sub->where('no_nota', 'like', "%{$q}%")
                        ->orWhereHas('pelanggan', function ($p) use ($q) {
                            $p->where('nama_pelanggan', 'like', "%{$q}%");
                        })
                        ->orWhereHas('user', function ($u) use ($q) {
                            $u->where('nama_lengkap', 'like', "%{$q}%");
                        });
                });
            }

            // Filter Status (LUNAS, DP)
            if ($request->has('status') && !empty($request->status) && $request->status !== 'SEMUA') {
                $query->where('status_pembayaran', strtoupper($request->status));
            }

            // Filter Bulan & Tahun
            if ($request->has('month') && !empty($request->month)) {
                $query->whereMonth('tgl_transaksi', $request->month);
            }
            if ($request->has('year') && !empty($request->year)) {
                $query->whereYear('tgl_transaksi', $request->year);
            }

            $bookings = $query->get();

            $formatted = $bookings->map(function ($b) {
                // Evaluasi 2 metode pembayaran jika transaksi awal adalah DP
                $pembayaranDp = $b->pembayaran->firstWhere('jenis_pembayaran', 'DP');
                $pembayaranPelunasan = $b->pembayaran->firstWhere('jenis_pembayaran', 'PELUNASAN');

                $metodeDp = $pembayaranDp ? strtoupper($pembayaranDp->metode_bayar) : ($b->metode_bayar_dp ? strtoupper($b->metode_bayar_dp) : null);
                $metodePelunasan = $pembayaranPelunasan ? strtoupper($pembayaranPelunasan->metode_bayar) : ($b->metode_bayar_pelunasan ? strtoupper($b->metode_bayar_pelunasan) : null);

                if ($metodeDp && $metodePelunasan) {
                    if ($metodeDp === $metodePelunasan) {
                        $metodeBayarDisplay = "{$metodeDp} (DP & Pelunasan)";
                    } else {
                        $metodeBayarDisplay = "{$metodeDp} (DP) • {$metodePelunasan} (Pelunasan)";
                    }
                } elseif ($metodeDp) {
                    $metodeBayarDisplay = "{$metodeDp} (DP)";
                } else {
                    $metodeBayarDisplay = strtoupper($b->metode_bayar_terakhir ?? ($b->pembayaran->first()?->metode_bayar ?? 'TUNAI'));
                }

                return [
                    'id_booking' => $b->id_booking,
                    'no_nota' => $b->no_nota,
                    'no_booking' => $b->no_booking,
                    'waktu' => $b->tgl_transaksi ? strtoupper($b->tgl_transaksi->format('d M y H:i')) : '-',
                    'kasir' => $b->user ? strtoupper($b->user->nama_lengkap) : 'KASIR',
                    'customer' => $b->pelanggan ? strtoupper($b->pelanggan->nama_pelanggan) : 'PELANGGAN',
                    'total_dp' => (float)$b->total_dp,
                    'total_dp_formatted' => 'RP ' . number_format($b->total_dp, 0, ',', '.'),
                    'total_tagihan' => (float)$b->total_tagihan,
                    'total_tagihan_formatted' => 'RP ' . number_format($b->total_tagihan, 0, ',', '.'),
                    'bayar' => (float)$b->total_bayar,
                    'bayar_formatted' => 'RP ' . number_format($b->total_bayar, 0, ',', '.'),
                    'sisa_tagihan' => (float)$b->sisa_tagihan,
                    'sisa_tagihan_formatted' => $b->sisa_tagihan > 0 ? number_format($b->sisa_tagihan, 0, ',', '.') : '0',
                    'metode_bayar' => $metodeBayarDisplay,
                    'metode_bayar_dp' => $metodeDp,
                    'metode_bayar_pelunasan' => $metodePelunasan,
                    'status' => strtoupper($b->status_pembayaran),
                    'status_sewa' => $b->status_sewa,
                ];
            });

            return response()->json([
                'status' => 'success',
                'total' => $formatted->count(),
                'data' => $formatted,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mengambil detail lengkap nota transaksi untuk pages/nota.html
     */
    public function show($id)
    {
        try {
            $query = Booking::with([
                'pelanggan.kecamatan',
                'user',
                'details.produk',
                'pembayaran.user',
            ]);

            if ($id === 'latest') {
                $booking = $query->orderByDesc('id_booking')->firstOrFail();
            } else {
                $booking = $query->where('id_booking', $id)
                    ->orWhere('no_nota', $id)
                    ->firstOrFail();
            }

            $pembayaranTerakhir = $booking->pembayaran->sortByDesc('id_pembayaran')->first();
            $pembayaranDp = $booking->pembayaran->firstWhere('jenis_pembayaran', 'DP');
            $pembayaranPelunasan = $booking->pembayaran->firstWhere('jenis_pembayaran', 'PELUNASAN');

            $metodeDp = $pembayaranDp ? strtoupper($pembayaranDp->metode_bayar) : ($booking->metode_bayar_dp ? strtoupper($booking->metode_bayar_dp) : null);
            $metodePelunasan = $pembayaranPelunasan ? strtoupper($pembayaranPelunasan->metode_bayar) : ($booking->metode_bayar_pelunasan ? strtoupper($booking->metode_bayar_pelunasan) : null);

            $items = $booking->details->map(function ($d) {
                return [
                    'id_produk' => $d->id_produk,
                    'nama_produk' => $d->produk ? $d->produk->nama_produk : 'Item Busana',
                    'jumlah' => $d->jumlah,
                    'harga_satuan' => (float)$d->harga_satuan,
                    'harga_formatted' => number_format($d->harga_satuan, 0, ',', '.'),
                    'subtotal' => (float)$d->subtotal,
                    'subtotal_formatted' => number_format($d->subtotal, 0, ',', '.'),
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id_booking' => $booking->id_booking,
                    'no_nota' => $booking->no_nota,
                    'no_booking' => $booking->no_booking,
                    'waktu' => $booking->tgl_transaksi ? $booking->tgl_transaksi->format('d M y H:i') : '-',
                    'kasir' => $booking->user ? $booking->user->nama_lengkap : 'Ahmad Rivaldy',
                    'customer' => $booking->pelanggan ? $booking->pelanggan->nama_pelanggan : 'Cordelia',
                    'customer_telepon' => $booking->pelanggan ? $booking->pelanggan->no_telepon : '-',
                    'customer_alamat' => $booking->pelanggan ? $booking->pelanggan->alamat_lengkap : '-',
                    'items' => $items,
                    'total_items_count' => $items->sum('jumlah'),
                    'subtotal' => (float)$booking->subtotal_produk,
                    'subtotal_formatted' => number_format($booking->subtotal_produk, 0, ',', '.'),
                    'total_harga_sewa' => (float)$booking->total_tagihan,
                    'total_harga_sewa_formatted' => number_format($booking->total_tagihan, 0, ',', '.'),
                    'opsi_pembayaran' => strtolower($booking->opsi_pembayaran ?? 'lunas'),
                    'status_pembayaran' => strtoupper($booking->status_pembayaran),
                    'total_dp' => (float)$booking->total_dp,
                    'total_dp_formatted' => 'Rp ' . number_format($booking->total_dp, 0, ',', '.'),
                    'pelunasan_nominal' => (float)$booking->sisa_tagihan,
                    'sisa_bayar' => (float)$booking->sisa_tagihan,
                    'sisa_bayar_formatted' => $booking->sisa_tagihan <= 0 ? 'Rp 0 (LUNAS)' : 'Rp ' . number_format($booking->sisa_tagihan, 0, ',', '.'),
                    'total_bersih_terbayar' => (float)$booking->total_bayar,
                    'total_bersih_terbayar_formatted' => number_format($booking->total_bayar, 0, ',', '.'),
                    'metode_bayar' => $booking->metode_bayar_terakhir,
                    'metode_bayar_dp' => $metodeDp,
                    'metode_bayar_pelunasan' => $metodePelunasan,
                    'is_dua_metode' => (bool)($metodeDp && $metodePelunasan),
                    'pembayaran_dp_detail' => $pembayaranDp ? [
                        'metode' => strtoupper($pembayaranDp->metode_bayar),
                        'nominal' => (float)$pembayaranDp->jumlah_bayar,
                        'nominal_formatted' => number_format($pembayaranDp->jumlah_bayar, 0, ',', '.'),
                        'uang_diterima' => (float)$pembayaranDp->uang_diterima,
                        'uang_diterima_formatted' => number_format($pembayaranDp->uang_diterima, 0, ',', '.'),
                        'kembalian' => (float)$pembayaranDp->kembalian,
                        'kembalian_formatted' => number_format($pembayaranDp->kembalian, 0, ',', '.'),
                        'waktu' => $pembayaranDp->tgl_bayar ? $pembayaranDp->tgl_bayar->format('d M y H:i') : '-',
                    ] : null,
                    'pembayaran_pelunasan_detail' => $pembayaranPelunasan ? [
                        'metode' => strtoupper($pembayaranPelunasan->metode_bayar),
                        'nominal' => (float)$pembayaranPelunasan->jumlah_bayar,
                        'nominal_formatted' => number_format($pembayaranPelunasan->jumlah_bayar, 0, ',', '.'),
                        'uang_diterima' => (float)$pembayaranPelunasan->uang_diterima,
                        'uang_diterima_formatted' => number_format($pembayaranPelunasan->uang_diterima, 0, ',', '.'),
                        'kembalian' => (float)$pembayaranPelunasan->kembalian,
                        'kembalian_formatted' => number_format($pembayaranPelunasan->kembalian, 0, ',', '.'),
                        'waktu' => $pembayaranPelunasan->tgl_bayar ? $pembayaranPelunasan->tgl_bayar->format('d M y H:i') : '-',
                    ] : null,
                    'uang_diterima' => $pembayaranTerakhir ? (float)$pembayaranTerakhir->uang_diterima : $booking->total_bayar,
                    'uang_diterima_formatted' => number_format($pembayaranTerakhir ? $pembayaranTerakhir->uang_diterima : $booking->total_bayar, 0, ',', '.'),
                    'kembalian' => $pembayaranTerakhir ? (float)$pembayaranTerakhir->kembalian : 0,
                    'kembalian_formatted' => number_format($pembayaranTerakhir ? $pembayaranTerakhir->kembalian : 0, 0, ',', '.'),
                    'riwayat_pembayaran' => $booking->pembayaran->map(function ($p) {
                        return [
                            'no_bukti' => $p->no_bukti_bayar,
                            'tgl' => $p->tgl_bayar ? $p->tgl_bayar->format('d M y H:i') : '-',
                            'jenis' => $p->jenis_pembayaran,
                            'jumlah' => (float)$p->jumlah_bayar,
                            'metode' => $p->metode_bayar,
                        ];
                    }),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nota transaksi tidak ditemukan: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Memproses pelunasan transaksi pembayaran di modal pages/data-nota.html
     */
    public function bayar(Request $request)
    {
        $request->validate([
            'id_booking' => 'required|integer',
            'metode_bayar' => 'required|string|in:TUNAI,BANK,QRIS',
            'uang_diterima' => 'nullable|numeric',
        ]);

        try {
            DB::beginTransaction();

            $booking = Booking::findOrFail($request->id_booking);

            if ($booking->status_pembayaran === 'LUNAS' || $booking->sisa_tagihan <= 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Transaksi ini sudah lunas!',
                ], 400);
            }

            $sisaBayar = (float)$booking->sisa_tagihan;
            $metode = strtoupper($request->metode_bayar);
            $uangDiterima = $request->filled('uang_diterima') ? (float)$request->uang_diterima : $sisaBayar;

            if ($uangDiterima < $sisaBayar && $metode === 'TUNAI') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Uang yang dibayarkan kurang dari total sisa tagihan (Rp ' . number_format($sisaBayar, 0, ',', '.') . ')',
                ], 422);
            }

            $kembalian = max(0, $uangDiterima - $sisaBayar);
            $idUser = auth()->id() ?? 2; // Default kasir Ivan jika tanpa session auth

            $noBukti = 'PAY-' . date('ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Simpan pembayaran pelunasan
            // Catatan: Trigger MySQL trg_after_insert_pembayaran_transaksi akan otomatis meng-update
            // booking.total_bayar, booking.sisa_tagihan, status_pembayaran = 'LUNAS', serta mencatat ke laporan_pemasukan!
            $pembayaran = PembayaranTransaksi::create([
                'no_bukti_bayar' => $noBukti,
                'id_booking' => $booking->id_booking,
                'tgl_bayar' => now(),
                'jenis_pembayaran' => 'PELUNASAN',
                'jumlah_bayar' => $sisaBayar,
                'uang_diterima' => $uangDiterima,
                'kembalian' => $kembalian,
                'metode_bayar' => $metode,
                'id_user' => $idUser,
                'keterangan' => 'Pelunasan pelunasan sewa via ' . $metode,
            ]);

            DB::commit();

            // Refresh data booking terbaru
            $booking->refresh();

            return response()->json([
                'status' => 'success',
                'message' => "Pelunasan transaksi booking #{$booking->id_booking} senilai Rp " . number_format($sisaBayar, 0, ',', '.') . " via {$metode} berhasil diproses.",
                'data' => [
                    'id_booking' => $booking->id_booking,
                    'no_nota' => $booking->no_nota,
                    'status_pembayaran' => $booking->status_pembayaran,
                    'total_bayar' => (float)$booking->total_bayar,
                    'sisa_tagihan' => (float)$booking->sisa_tagihan,
                    'kembalian' => $kembalian,
                    'no_bukti_bayar' => $noBukti,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses pembayaran: ' . $e->getMessage(),
            ], 500);
        }
    }
}
