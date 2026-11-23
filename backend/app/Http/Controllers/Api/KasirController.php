<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Produk;
use App\Models\Kategori;
use App\Models\Pelanggan;
use App\Models\Booking;
use App\Models\DetailBooking;
use App\Models\PembayaranTransaksi;

class KasirController extends Controller
{
    /**
     * Mengambil daftar produk katalog untuk menu kasir
     */
    public function getProduk(Request $request)
    {
        try {
            $query = Produk::with('kategori')->where('status', 'tersedia');

            if ($request->has('kategori') && !empty($request->kategori)) {
                $query->where('id_kategori', $request->kategori);
            }

            if ($request->has('q') && !empty($request->q)) {
                $query->where('nama_produk', 'like', "%{$request->q}%")
                      ->orWhere('kode_produk', 'like', "%{$request->q}%");
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
                    'stok_siap' => $p->stok_siap,
                    'foto_produk' => $p->foto_produk,
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

    /**
     * Memproses pesanan baru dari menu kasir
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'id_pelanggan' => 'required|integer',
            'tgl_ambil' => 'required|date',
            'tgl_pakai' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.id_produk' => 'required|integer',
            'items.*.jumlah' => 'required|integer|min:1',
            'opsi_pembayaran' => 'required|string|in:lunas,dp',
            'metode_bayar' => 'required|string|in:TUNAI,BANK,QRIS',
            'uang_diterima' => 'nullable|numeric',
        ]);

        try {
            DB::beginTransaction();

            $idPelanggan = $request->id_pelanggan;
            $tglAmbil = $request->tgl_ambil;
            $tglPakai = $request->tgl_pakai;
            $tglKembali = date('Y-m-d', strtotime($tglPakai . ' +3 days'));

            // Hitung subtotal produk
            $subtotal = 0;
            $itemsData = [];

            foreach ($request->items as $item) {
                $produk = Produk::findOrFail($item['id_produk']);
                if ($produk->stok_siap < $item['jumlah']) {
                    throw new \Exception("Stok siap untuk {$produk->nama_produk} tidak mencukupi (Tersedia: {$produk->stok_siap})");
                }
                $itemSubtotal = $produk->harga_sewa * $item['jumlah'];
                $subtotal += $itemSubtotal;
                $itemsData[] = [
                    'produk' => $produk,
                    'jumlah' => $item['jumlah'],
                    'harga_satuan' => $produk->harga_sewa,
                    'subtotal' => $itemSubtotal,
                ];
            }

            $totalTagihan = $subtotal;
            $opsi = strtolower($request->opsi_pembayaran);
            $totalDp = round($totalTagihan * 0.25, 2);

            if ($opsi === 'lunas') {
                $jumlahBayarSekarang = $totalTagihan;
                $sisaTagihan = 0;
                $statusPembayaran = 'LUNAS';
                $jenisPembayaran = 'LUNAS_LANGSUNG';
            } else {
                $jumlahBayarSekarang = $totalDp;
                $sisaTagihan = $totalTagihan - $totalDp;
                $statusPembayaran = 'DP';
                $jenisPembayaran = 'DP';
            }

            $metode = strtoupper($request->metode_bayar);
            $uangDiterima = $request->filled('uang_diterima') ? (float)$request->uang_diterima : $jumlahBayarSekarang;
            $kembalian = max(0, $uangDiterima - $jumlahBayarSekarang);

            // Generate No Nota & No Booking
            $countToday = Booking::whereDate('tgl_transaksi', now()->toDateString())->count() + 1;
            $noNota = 'CS/01/' . date('dmy') . '/' . str_pad($countToday, 4, '0', STR_PAD_LEFT);
            $noBooking = Booking::max('no_booking') + 1;

            $booking = Booking::create([
                'no_nota' => $noNota,
                'no_booking' => $noBooking,
                'id_pelanggan' => $idPelanggan,
                'id_user' => auth()->id() ?? 2,
                'tgl_transaksi' => now(),
                'tgl_ambil' => $tglAmbil,
                'tgl_pakai' => $tglPakai,
                'tgl_kembali_rencana' => $tglKembali,
                'durasi_sewa_hari' => 3,
                'subtotal_produk' => $subtotal,
                'total_tagihan' => $totalTagihan,
                'opsi_pembayaran' => $opsi,
                'persentase_dp' => 25.00,
                'total_dp' => $totalDp,
                'total_bayar' => $jumlahBayarSekarang,
                'sisa_tagihan' => $sisaTagihan,
                'metode_bayar_terakhir' => $metode,
                'status_pembayaran' => $statusPembayaran,
                'status_sewa' => 'booking',
            ]);

            // Insert detail booking (Trigger MySQL otomatis mengurangi stok_siap & menambah stok_disewa)
            foreach ($itemsData as $it) {
                DetailBooking::create([
                    'id_booking' => $booking->id_booking,
                    'id_produk' => $it['produk']->id_produk,
                    'jumlah' => $it['jumlah'],
                    'harga_satuan' => $it['harga_satuan'],
                    'subtotal' => $it['subtotal'],
                ]);
            }

            // Insert pembayaran
            $noBukti = 'PAY-' . date('ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            PembayaranTransaksi::create([
                'no_bukti_bayar' => $noBukti,
                'id_booking' => $booking->id_booking,
                'tgl_bayar' => now(),
                'jenis_pembayaran' => $jenisPembayaran,
                'jumlah_bayar' => $jumlahBayarSekarang,
                'uang_diterima' => $uangDiterima,
                'kembalian' => $kembalian,
                'metode_bayar' => $metode,
                'id_user' => auth()->id() ?? 2,
                'keterangan' => 'Pembayaran ' . ($opsi === 'lunas' ? 'Lunas' : 'DP 25%') . ' kasir',
            ]);

            // Update total transaksi di pelanggan
            Pelanggan::where('id_pelanggan', $idPelanggan)->increment('total_transaksi');

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi kasir berhasil dicatat!',
                'data' => [
                    'id_booking' => $booking->id_booking,
                    'no_nota' => $booking->no_nota,
                    'no_booking' => $booking->no_booking,
                    'total_tagihan' => $totalTagihan,
                    'total_bayar' => $jumlahBayarSekarang,
                    'kembalian' => $kembalian,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses transaksi kasir: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mengambil daftar tanggal yang memiliki jadwal booking aktif (untuk kalender kasir)
     */
    public function getBookedDates(Request $request)
    {
        try {
            $month = $request->query('month');
            $year = $request->query('year');

            $query = Booking::with(['pelanggan', 'details.produk'])
                ->where('status_sewa', '!=', 'dibatalkan');

            if ($year) {
                $query->where(function ($q) use ($year) {
                    $q->whereYear('tgl_ambil', $year)
                      ->orWhereYear('tgl_kembali_rencana', $year);
                });
            }

            if ($month) {
                $query->where(function ($q) use ($month) {
                    $q->whereMonth('tgl_ambil', $month)
                      ->orWhereMonth('tgl_kembali_rencana', $month);
                });
            }

            $bookings = $query->get();
            $bookedDates = [];

            $monthsId = [
                'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr',
                'May' => 'Mei', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Ags',
                'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nov', 'Dec' => 'Des'
            ];

            foreach ($bookings as $b) {
                $custName = $b->pelanggan ? $b->pelanggan->nama_pelanggan : 'Pelanggan';
                $productNames = $b->details->map(function ($d) {
                    return $d->produk ? $d->produk->nama_produk : '';
                })->filter()->implode(', ');

                if (empty($productNames)) {
                    $productNames = 'Busana';
                }

                $dpBadge = ($b->status_pembayaran === 'LUNAS')
                    ? 'Lunas'
                    : ('DP ' . round($b->persentase_dp) . '%');

                $tglAmbil = $b->tgl_ambil ? $b->tgl_ambil->format('Y-m-d') : null;
                $tglPakai = $b->tgl_pakai ? $b->tgl_pakai->format('Y-m-d') : null;
                $tglKembali = $b->tgl_kembali_rencana ? $b->tgl_kembali_rencana->format('Y-m-d') : null;

                if (!$tglAmbil) continue;

                $endStr = $tglKembali ?: ($tglPakai ?: $tglAmbil);
                $curr = new \DateTime($tglAmbil);
                $end = new \DateTime($endStr);
                $end->modify('+1 day');

                $period = new \DatePeriod($curr, new \DateInterval('P1D'), $end);

                foreach ($period as $dt) {
                    $dateStr = $dt->format('Y-m-d');
                    $mEng = $dt->format('M');
                    $mId = $monthsId[$mEng] ?? $mEng;
                    $dayNum = $dt->format('j');

                    $type = 'pakai';
                    $typeLabel = 'Pakai';
                    $infoTitle = "{$dayNum} {$mId} • Pemakaian Busana";

                    if ($dateStr === $tglAmbil) {
                        $type = 'ambil';
                        $typeLabel = 'Ambil';
                        $infoTitle = "{$dayNum} {$mId} • Pengambilan Barang";
                    } elseif ($dateStr === $tglKembali) {
                        $type = 'kembali';
                        $typeLabel = 'Kembali';
                        $infoTitle = "{$dayNum} {$mId} • Pengembalian Barang";
                    }

                    $bookedDates[$dateStr] = [
                        'date' => $dateStr,
                        'day' => (int)$dayNum,
                        'type' => $type,
                        'type_label' => $typeLabel,
                        'customer' => $custName,
                        'produk' => $productNames,
                        'no_nota' => $b->no_nota,
                        'status_sewa' => $b->status_sewa,
                        'status_pembayaran' => $b->status_pembayaran,
                        'info_title' => $infoTitle,
                        'info_sub' => "Peminjam: {$custName} ({$productNames})",
                        'badge' => $dpBadge,
                    ];
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $bookedDates,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil jadwal booking: ' . $e->getMessage(),
            ], 500);
        }
    }
}
