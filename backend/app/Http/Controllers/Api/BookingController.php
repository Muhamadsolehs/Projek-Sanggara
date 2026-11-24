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
            $query = Booking::with(['pelanggan', 'details.produk'])->orderBy('id_booking', 'desc');

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

            $data = $bookings->map(function ($b, $index) {
                $totalDenda = (float)$b->denda_keterlambatan + (float)$b->denda_kerusakan;
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
                    'pickup' => $b->tgl_ambil ? $b->tgl_ambil->format('d/m/Y') : '-',
                    'pakai' => $b->tgl_pakai ? $b->tgl_pakai->format('d/m/Y') : '-',
                    'pickin' => $b->tgl_kembali_rencana ? $b->tgl_kembali_rencana->format('d/m/Y') : '-',
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
            $query = DetailBooking::with(['booking.pelanggan', 'produk.kategori'])->orderBy('id_detail', 'desc');

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
            $booking = Booking::with(['pelanggan', 'details.produk', 'pembayaran'])->findOrFail($id);

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
