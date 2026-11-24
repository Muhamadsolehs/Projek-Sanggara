/**
 * Sanggara Management System - REST API Client
 * File: js/api.js
 * Backend: Laravel 13 (http://127.0.0.1:8000/api)
 * Terhubung ke database: MySQL (db_sanggara)
 */

const API_BASE_URL = 'http://127.0.0.1:8000/api';

const SanggaraAPI = {
  baseUrl: API_BASE_URL,

  async isOnline() {
    try {
      const res = await fetch(`${this.baseUrl}/ping`, { 
        method: 'GET', 
        headers: { 'Accept': 'application/json' },
        signal: AbortSignal.timeout(1500) 
      });
      return res.ok;
    } catch (e) {
      return false;
    }
  },

  async getDashboard() {
    const res = await fetch(`${this.baseUrl}/dashboard`, {
      headers: { 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error('Gagal memuat data dashboard');
    return await res.json();
  },

  async getNotaList(params = {}) {
    const query = new URLSearchParams(params).toString();
    const url = `${this.baseUrl}/nota${query ? '?' + query : ''}`;
    const res = await fetch(url, {
      headers: { 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error('Gagal memuat data nota');
    return await res.json();
  },

  async getNotaDetail(id) {
    const res = await fetch(`${this.baseUrl}/nota/${id}`, {
      headers: { 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error(`Nota #${id} tidak ditemukan`);
    return await res.json();
  },

  async bayarPelunasan(payload) {
    const res = await fetch(`${this.baseUrl}/nota/bayar`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Gagal memproses pembayaran');
    return data;
  },

  async getKasirProduk() {
    const res = await fetch(`${this.baseUrl}/kasir/produk`, {
      headers: { 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error('Gagal memuat katalog kasir');
    return await res.json();
  },

  async checkoutKasir(payload) {
    const res = await fetch(`${this.baseUrl}/kasir/checkout`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Gagal memproses transaksi kasir');
    return data;
  },

  async getBookedDates(params = {}) {
    const query = new URLSearchParams(params).toString();
    const res = await fetch(`${this.baseUrl}/kasir/booked-dates${query ? '?' + query : ''}`, {
      headers: { 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error('Gagal memuat jadwal booking');
    return await res.json();
  },

  async getBookingKalender(params = {}) {
    const query = new URLSearchParams(params).toString();
    const res = await fetch(`${this.baseUrl}/booking/kalender${query ? '?' + query : ''}`, {
      headers: { 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error('Gagal memuat data kalender booking');
    return await res.json();
  },

  async getBooking(params = {}) {
    const query = new URLSearchParams(params).toString();
    const res = await fetch(`${this.baseUrl}/booking${query ? '?' + query : ''}`, {
      headers: { 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error('Gagal memuat data tabel booking');
    return await res.json();
  },

  async getBookingDetail(id) {
    const res = await fetch(`${this.baseUrl}/booking/${id}`, {
      headers: { 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error('Gagal memuat detail booking');
    return await res.json();
  },

  async updateBooking(id, data) {
    const res = await fetch(`${this.baseUrl}/booking/${id}`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(data)
    });
    const result = await res.json();
    if (!res.ok) throw new Error(result.message || 'Gagal memperbarui data booking');
    return result;
  },

  async deleteBooking(id) {
    const res = await fetch(`${this.baseUrl}/booking/${id}`, {
      method: 'DELETE',
      headers: { 'Accept': 'application/json' }
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Gagal menghapus data booking');
    return data;
  },

  async getPesanan(params = {}) {
    const query = new URLSearchParams(params).toString();
    const res = await fetch(`${this.baseUrl}/pesanan${query ? '?' + query : ''}`, {
      headers: { 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error('Gagal memuat data pesanan');
    return await res.json();
  },

  async getProduk(params = {}) {
    const query = new URLSearchParams(params).toString();
    const res = await fetch(`${this.baseUrl}/produk${query ? '?' + query : ''}`, {
      headers: { 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error('Gagal memuat data produk');
    return await res.json();
  },

  async getProdukDetail(id) {
    const res = await fetch(`${this.baseUrl}/produk/${id}`, {
      headers: { 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error(`Produk #${id} tidak ditemukan`);
    return await res.json();
  },

  async tambahProduk(payload) {
    const isFormData = typeof FormData !== 'undefined' && payload instanceof FormData;
    const options = {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        ...(isFormData ? {} : { 'Content-Type': 'application/json' })
      },
      body: isFormData ? payload : JSON.stringify(payload)
    };
    const res = await fetch(`${this.baseUrl}/produk`, options);
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Gagal menambahkan produk');
    return data;
  },

  async updateProduk(id, payload) {
    const isFormData = typeof FormData !== 'undefined' && payload instanceof FormData;
    const options = {
      method: isFormData ? 'POST' : 'PUT',
      headers: {
        'Accept': 'application/json',
        ...(isFormData ? {} : { 'Content-Type': 'application/json' })
      },
      body: isFormData ? payload : JSON.stringify(payload)
    };
    const res = await fetch(`${this.baseUrl}/produk/${id}`, options);
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Gagal memperbarui produk');
    return data;
  },

  async deleteProduk(id) {
    const res = await fetch(`${this.baseUrl}/produk/${id}`, {
      method: 'DELETE',
      headers: { 'Accept': 'application/json' }
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Gagal menghapus produk');
    return data;
  },

  async getPelanggan(params = {}) {
    const query = new URLSearchParams(params).toString();
    const res = await fetch(`${this.baseUrl}/pelanggan${query ? '?' + query : ''}`, {
      headers: { 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error('Gagal memuat data pelanggan');
    return await res.json();
  },

  async getKecamatan(params = {}) {
    const query = new URLSearchParams(params).toString();
    const res = await fetch(`${this.baseUrl}/kecamatan${query ? '?' + query : ''}`, {
      headers: { 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error('Gagal memuat data 17 kecamatan');
    return await res.json();
  },

  async getDistricts(regency = '3214') {
    const res = await fetch(`${this.baseUrl}/regions/districts?regency=${regency}`, {
      headers: { 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error('Gagal memuat data kecamatan');
    return await res.json();
  },

  async getVillages(districtId) {
    const res = await fetch(`${this.baseUrl}/regions/villages/${districtId}`, {
      headers: { 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error('Gagal memuat data kelurahan/desa');
    return await res.json();
  },

  async getAnalisaPelanggan() {
    const res = await fetch(`${this.baseUrl}/analisa/pelanggan`, {
      headers: { 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error('Gagal memuat data analisa pelanggan');
    return await res.json();
  },

  async getPelangganDetail(id) {
    const res = await fetch(`${this.baseUrl}/pelanggan/${id}`, {
      headers: { 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error(`Pelanggan #${id} tidak ditemukan`);
    return await res.json();
  },

  async tambahPelanggan(payload) {
    const res = await fetch(`${this.baseUrl}/pelanggan`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Gagal menambahkan pelanggan');
    return data;
  },

  async updatePelanggan(id, payload) {
    const res = await fetch(`${this.baseUrl}/pelanggan/${id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Gagal memperbarui data pelanggan');
    return data;
  },

  async deletePelanggan(id) {
    const res = await fetch(`${this.baseUrl}/pelanggan/${id}`, {
      method: 'DELETE',
      headers: { 'Accept': 'application/json' }
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Gagal menghapus pelanggan');
    return data;
  },

  async getKategori() {
    const res = await fetch(`${this.baseUrl}/kategori`, {
      headers: { 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error('Gagal memuat data kategori');
    return await res.json();
  },

  async getKategoriDetail(id) {
    const res = await fetch(`${this.baseUrl}/kategori/${id}`, {
      headers: { 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error(`Kategori #${id} tidak ditemukan`);
    return await res.json();
  },

  async tambahKategori(payload) {
    const res = await fetch(`${this.baseUrl}/kategori`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Gagal menambahkan kategori');
    return data;
  },

  async updateKategori(id, payload) {
    const res = await fetch(`${this.baseUrl}/kategori/${id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Gagal memperbarui kategori');
    return data;
  },

  async deleteKategori(id) {
    const res = await fetch(`${this.baseUrl}/kategori/${id}`, {
      method: 'DELETE',
      headers: { 'Accept': 'application/json' }
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Gagal menghapus kategori');
    return data;
  },

  async getLaporanPemasukan(params = {}) {
    const query = new URLSearchParams(params).toString();
    const res = await fetch(`${this.baseUrl}/laporan/pemasukan${query ? '?' + query : ''}`, {
      headers: { 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error('Gagal memuat laporan pemasukan');
    return await res.json();
  },

  async getPemasukanDetail(id) {
    const res = await fetch(`${this.baseUrl}/laporan/pemasukan/${id}`, {
      headers: { 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error(`Pemasukan #${id} tidak ditemukan`);
    return await res.json();
  },

  async tambahPemasukan(payload) {
    const res = await fetch(`${this.baseUrl}/laporan/pemasukan`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Gagal menambahkan pemasukan');
    return data;
  },

  async updatePemasukan(id, payload) {
    const res = await fetch(`${this.baseUrl}/laporan/pemasukan/${id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Gagal memperbarui pemasukan');
    return data;
  },

  async deletePemasukan(id) {
    const res = await fetch(`${this.baseUrl}/laporan/pemasukan/${id}`, {
      method: 'DELETE',
      headers: { 'Accept': 'application/json' }
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Gagal menghapus pemasukan');
    return data;
  },

  async getLaporanPengeluaran(params = {}) {
    const query = new URLSearchParams(params).toString();
    const res = await fetch(`${this.baseUrl}/laporan/pengeluaran${query ? '?' + query : ''}`, {
      headers: { 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error('Gagal memuat laporan pengeluaran');
    return await res.json();
  },

  async getPengeluaranDetail(id) {
    const res = await fetch(`${this.baseUrl}/laporan/pengeluaran/${id}`, {
      headers: { 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error(`Pengeluaran #${id} tidak ditemukan`);
    return await res.json();
  },

  async tambahPengeluaran(payload) {
    const res = await fetch(`${this.baseUrl}/laporan/pengeluaran`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Gagal menambahkan pengeluaran');
    return data;
  },

  async updatePengeluaran(id, payload) {
    const res = await fetch(`${this.baseUrl}/laporan/pengeluaran/${id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Gagal memperbarui pengeluaran');
    return data;
  },

  async deletePengeluaran(id) {
    const res = await fetch(`${this.baseUrl}/laporan/pengeluaran/${id}`, {
      method: 'DELETE',
      headers: { 'Accept': 'application/json' }
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Gagal menghapus pengeluaran');
    return data;
  }
};

window.SanggaraAPI = SanggaraAPI;
