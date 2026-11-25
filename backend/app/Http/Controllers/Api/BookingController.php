<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\DetailBooking;
use App\Models\Pelanggan;
use App\Models\Produk;

class BookingController extends Controller
{
    /**
     * Mengambil daftar data booking untuk tabel di pages/booking-tabel.html
     */
    public function index(Request $request)
    {
        try {
            $query = Booking::with(['pelanggan', 'details.produk'])
                ->orderByRaw('COALESCE(updated_at, created_at) DESC')
                ->orderByDesc('id_booking');

            // Pencarian kata kunci (no_booking, no_nota, nama pelanggan)
            if ($request->has('q') && !empty($request->q)) {
                $q = $request->q;
                $query->where(function ($sub) use ($q) {
                    $sub->where('no_booking', 'like', "%{$q}%")
                        ->orWhere('no_nota', 'like', "%{$q}%")
                        ->orWhereHas('pelanggan', function ($p) use ($q) {
                            $p->where('nama_pelanggan', 'like', "%{$q}%");
                        });
                });
            }

            // Filter Bulan & Tahun
            if ($request->has('month') && !empty($request->month)) {
                $query->whereMonth('tgl_ambil', $request->month);
            }
            if ($request->has('year') && !empty($request->year)) {
                $query->whereYear('tgl_ambil', $request->year);
            }

            $bookings = $query->get();

            $today = now()->startOfDay();

            $data = $bookings->map(function ($b, $index) use ($today) {
                $tglKembali = $b->tgl_kembali_rencana ? \Carbon\Carbon::parse($b->tgl_kembali_rencana)->startOfDay() : null;
                $isReturned = in_array(strtolower($b->status_sewa), ['selesai', 'kembali']);

                // Aturan Denda: jika lebih dari tgl kembali rencana dan status belum dikembalikan, denda otomatis 10% dari nominal bayar barang
                $isOverdue = false;
                if ($tglKembali && !$isReturned && $today->gt($tglKembali)) {
                    $isOverdue = true;
                }

                $nominalBarang = (float)($b->subtotal_produk ?: $b->total_tagihan);
                $dendaTelat = (float)$b->denda_keterlambatan;
                if ($isOverdue && $dendaTelat <= 0) {
                    $dendaTelat = round($nominalBarang * 0.10);
                }
                $totalDenda = $dendaTelat + (float)$b->denda_kerusakan;

                $items = $b->details->map(function ($d) {
                    return $d->produk ? $d->produk->nama_produk : '-';
                })->filter()->implode(', ');

                return [
                    'no' => $index + 1,
                    'id_booking' => $b->id_booking,
                    'no_booking' => $b->no_booking,
                    'no_nota' => $b->no_nota,
                    'nama_konsumen' => $b->pelanggan ? $b->pelanggan->nama_pelanggan : 'Pelanggan Umum',
                    'telepon' => $b->pelanggan ? $b->pelanggan->no_telepon : '-',
                    'item' => $items ?: 'Busana Sewa',
                    'total_item' => $b->details->sum('jumlah') ?: 1,
                    'pickup' => $b->tgl_ambil ? $b->tgl_ambil->format('d/m/Y') : '-',
                    'pakai' => $b->tgl_pakai ? $b->tgl_pakai->format('d/m/Y') : '-',
                    'pickin' => $b->tgl_kembali_rencana ? $b->tgl_kembali_rencana->format('d/m/Y') : '-',
                    'is_overdue' => $isOverdue,
                    'status' => $b->status_sewa,
                    'status_label' => ucfirst($b->status_sewa),
                    'status_pembayaran' => $b->status_pembayaran,
                    'total_tagihan' => (float)$b->total_tagihan,
                    'total_tagihan_formatted' => 'Rp ' . number_format($b->total_tagihan, 0, ',', '.'),
                    'denda' => $totalDenda,
                    'denda_formatted' => 'Rp ' . number_format($totalDenda, 0, ',', '.'),
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat data booking: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mengambil daftar data pesanan (detail produk per booking) untuk pages/pesanan-tabel.html
     */
    public function pesanan(Request $request)
    {
        try {
            $query = DetailBooking::with(['booking.pelanggan', 'produk.kategori'])
                ->orderByRaw('COALESCE(updated_at, created_at) DESC')
                ->orderByDesc('id_detail');

            if ($request->has('q') && !empty($request->q)) {
                $q = $request->q;
                $query->where(function ($sub) use ($q) {
                    $sub->whereHas('produk', function ($pr) use ($q) {
                        $pr->where('nama_produk', 'like', "%{$q}%");
                    })
                    ->orWhereHas('booking', function ($b) use ($q) {
                        $b->where('no_booking', 'like', "%{$q}%")
                          ->orWhere('no_nota', 'like', "%{$q}%")
                          ->orWhereHas('pelanggan', function ($p) use ($q) {
                              $p->where('nama_pelanggan', 'like', "%{$q}%");
                          });
                    });
                });
            }

            // Filter Bulan & Tahun dari tanggal sewa booking
            if ($request->has('month') && !empty($request->month)) {
                $query->whereHas('booking', function ($b) use ($request) {
                    $b->whereMonth('tgl_ambil', $request->month);
                });
            }
            if ($request->has('year') && !empty($request->year)) {
                $query->whereHas('booking', function ($b) use ($request) {
                    $b->whereYear('tgl_ambil', $request->year);
                });
            }

            $details = $query->get();

            $data = $details->map(function ($d, $index) {
                $b = $d->booking;
                $pr = $d->produk;

                return [
                    'no' => $index + 1,
                    'id_detail' => $d->id_detail,
                    'id_booking' => $b ? $b->id_booking : null,
                    'no_booking' => $b ? $b->no_booking : '-',
                    'no_nota' => $b ? $b->no_nota : '-',
                    'nama_konsumen' => ($b && $b->pelanggan) ? $b->pelanggan->nama_pelanggan : '-',
                    'item' => $pr ? $pr->nama_produk : 'Produk',
                    'kategori' => ($pr && $pr->kategori) ? $pr->kategori->nama_kategori : '-',
                    'foto_produk' => $pr ? $pr->foto_produk : null,
                    'jumlah' => $d->jumlah . ' Item',
                    'jumlah_raw' => $d->jumlah,
                    'harga_satuan' => (float)$d->harga_satuan,
                    'harga_satuan_formatted' => 'Rp ' . number_format($d->harga_satuan, 0, ',', '.'),
                    'subtotal' => (float)$d->subtotal,
                    'subtotal_formatted' => 'Rp ' . number_format($d->subtotal, 0, ',', '.'),
                    'tgl_ambil' => ($b && $b->tgl_ambil) ? $b->tgl_ambil->format('d/m/Y') : '-',
                    'tgl_kembali' => ($b && $b->tgl_kembali_rencana) ? $b->tgl_kembali_rencana->format('d/m/Y') : '-',
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat data pesanan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mengambil detail lengkap 1 booking
     */
    public function show($id)
    {
        try {
            $booking = Booking::with(['pelanggan', 'details.produk.kategori', 'pembayaran'])->findOrFail($id);

            // Periksa keterlambatan pengembalian (otomatis denda 10% jika telat & belum dikembalikan)
            $today = now()->startOfDay();
            $tglKembali = $booking->tgl_kembali_rencana ? \Carbon\Carbon::parse($booking->tgl_kembali_rencana)->startOfDay() : null;
            $isReturned = in_array(strtolower($booking->status_sewa), ['selesai', 'kembali']);

            $isOverdue = false;
            if ($tglKembali && !$isReturned && $today->gt($tglKembali)) {
                $isOverdue = true;
            }

            $nominalBarang = (float)($booking->subtotal_produk ?: $booking->total_tagihan);
            $dendaTelat = (float)$booking->denda_keterlambatan;
            if ($isOverdue && $dendaTelat <= 0) {
                $dendaTelat = round($nominalBarang * 0.10);
            }
            $booking->denda_keterlambatan = $dendaTelat;
            $booking->total_denda = $dendaTelat + (float)$booking->denda_kerusakan;
            $booking->is_overdue = $isOverdue;

            return response()->json([
                'status' => 'success',
                'data' => $booking,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Booking tidak ditemukan',
            ], 404);
        }
    }

    /**
     * Memperbarui data booking
     */
    public function update(Request $request, $id)
    {
        try {
            $booking = Booking::with('details')->findOrFail($id);

            $validated = $request->validate([
                'tgl_ambil' => 'sometimes|required|date',
                'tgl_pakai' => 'sometimes|required|date',
                'tgl_kembali_rencana' => 'sometimes|required|date',
                'tgl_kembali_aktual' => 'nullable|date',
                'status_sewa' => 'sometimes|required|string|in:booking,diambil,selesai,kembali,batal',
                'status_pembayaran' => 'sometimes|required|string|in:LUNAS,DP,BELUM LUNAS',
                'denda_keterlambatan' => 'nullable|numeric|min:0',
                'denda_kerusakan' => 'nullable|numeric|min:0',
                'catatan' => 'nullable|string',
            ]);

            // Aturan Sewa: Baju dipakai maksimal 1 hari.
            // Ambil (Pickup) = H-1, Pakai = Hari-H, Kembali (Pickin) = H+1
            if (isset($validated['tgl_pakai'])) {
                $pakaiDate = \Carbon\Carbon::parse($validated['tgl_pakai']);
                if (empty($validated['tgl_ambil'])) {
                    $validated['tgl_ambil'] = (clone $pakaiDate)->subDay()->toDateString();
                }
                if (empty($validated['tgl_kembali_rencana'])) {
                    $validated['tgl_kembali_rencana'] = (clone $pakaiDate)->addDay()->toDateString();
                }
                $validated['durasi_sewa_hari'] = 1;
            }

            // Jika status berubah menjadi selesai/kembali
            if (isset($validated['status_sewa']) && in_array(strtolower($validated['status_sewa']), ['selesai', 'kembali'])) {
                if (empty($validated['tgl_kembali_aktual']) && empty($booking->tgl_kembali_aktual)) {
                    $validated['tgl_kembali_aktual'] = now()->toDateString();
                }

                // Jika sebelumnya belum selesai, kembalikan stok siap produk
                if (!in_array(strtolower($booking->status_sewa), ['selesai', 'kembali'])) {
                    foreach ($booking->details as $detail) {
                        $p = Produk::find($detail->id_produk);
                        if ($p) {
                            $p->stok_siap = min($p->stok_total, $p->stok_siap + $detail->jumlah);
                            $p->stok_disewa = max(0, $p->stok_disewa - $detail->jumlah);
                            $p->save();
                        }
                    }
                }
            } elseif (isset($validated['status_sewa']) && in_array(strtolower($booking->status_sewa), ['selesai', 'kembali']) && !in_array(strtolower($validated['status_sewa']), ['selesai', 'kembali'])) {
                // Jika status dibatalkan dari selesai kembali ke booking/diambil
                $validated['tgl_kembali_aktual'] = null;
                foreach ($booking->details as $detail) {
                    $p = Produk::find($detail->id_produk);
                    if ($p) {
                        $p->stok_siap = max(0, $p->stok_siap - $detail->jumlah);
                        $p->stok_disewa = $p->stok_disewa + $detail->jumlah;
                        $p->save();
                    }
                }
            }

            // Denda otomatis 10% dari nominal bayar barang jika lewat tanggal kembali & belum selesai
            $statusToCheck = isset($validated['status_sewa']) ? strtolower($validated['status_sewa']) : strtolower($booking->status_sewa);
            $kembaliTarget = isset($validated['tgl_kembali_rencana']) ? \Carbon\Carbon::parse($validated['tgl_kembali_rencana']) : ($booking->tgl_kembali_rencana ? \Carbon\Carbon::parse($booking->tgl_kembali_rencana) : null);

            if (!isset($validated['denda_keterlambatan'])) {
                if ($kembaliTarget && !in_array($statusToCheck, ['selesai', 'kembali']) && now()->startOfDay()->gt($kembaliTarget->startOfDay())) {
                    $nominal = (float)($booking->subtotal_produk ?: $booking->total_tagihan);
                    $validated['denda_keterlambatan'] = round($nominal * 0.10);
                } elseif (in_array($statusToCheck, ['selesai', 'kembali']) && (float)$booking->denda_keterlambatan <= 0) {
                    $validated['denda_keterlambatan'] = 0;
                }
            }

            // total_denda dihitung otomatis oleh MySQL generated column dari denda_keterlambatan + denda_kerusakan
            unset($validated['total_denda']);

            // Hitung durasi sewa hari jika tanggal berubah
            $tglAmbil = $validated['tgl_ambil'] ?? $booking->tgl_ambil;
            $tglKembali = $validated['tgl_kembali_rencana'] ?? $booking->tgl_kembali_rencana;
            if ($tglAmbil && $tglKembali) {
                $start = \Carbon\Carbon::parse($tglAmbil);
                $end = \Carbon\Carbon::parse($tglKembali);
                $validated['durasi_sewa_hari'] = max(1, $start->diffInDays($end));
            }

            $booking->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Data booking berhasil diperbarui',
                'data' => $booking->fresh(['pelanggan', 'details.produk'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui booking: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menghapus 1 booking
     */
    public function destroy($id)
    {
        try {
            $booking = Booking::findOrFail($id);
            $booking->details()->delete();
            $booking->pembayaran()->delete();
            $booking->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Data booking berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus booking: ' . $e->getMessage(),
            ], 500);
        }
    }
}
