# 🍊 Warung Buah — Sistem Kasir Laravel

Sistem kasir untuk jual beli buah dalam satuan peti, dengan bobot fleksibel per peti, harga hasil tawar-menawar, dan rekap per supplier.

---

## 🚀 Cara Install

### 1. Buat project Laravel baru
```bash
composer create-project laravel/laravel warung-buah
cd warung-buah
```

### 2. Copy semua file dari project ini ke folder Laravel
Salin seluruh isi folder ini ke dalam folder Laravel yang baru dibuat.
Struktur file sudah sesuai dengan struktur Laravel standar.

### 3. Setup database
Edit file `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=warung_buah
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Buat database
```sql
CREATE DATABASE warung_buah;
```

### 5. Jalankan migrasi dan seeder
```bash
php artisan migrate --seed
```

### 6. Jalankan server
```bash
php artisan serve
```

Buka browser: **http://localhost:8000**

---

## 🔐 Login Default

| Email | Password | Role |
|---|---|---|
| admin@warung.com | password | Admin |

---

## 📋 Fitur

### ✅ Form Kasir (Transaksi Baru)
- Input nama pelanggan & status bayar (Lunas / Tempo / Cicil)
- Komisi % otomatis dari pengaturan (bisa diubah per transaksi)
- **Repeater Item Buah** — tambah item buah dinamis:
  - Nama supplier, jenis buah, ukuran (A-E), harga/kg
  - **Repeater Peti** per item — input berat kotor & kemasan
  - Berat bersih & subtotal dihitung otomatis real-time
- **Biaya Operasional** — tambah kuli, pengiriman, dll
- **Ringkasan real-time**: Total Kotor → Komisi → Biaya Ops → Net Pendapatan

### ✅ Detail Transaksi
- Rekap per supplier + per jenis buah
- Detail setiap peti dengan berat kotor/kemasan/bersih
- Ringkasan keuangan lengkap
- Bisa cetak (print)

### ✅ Daftar Transaksi
- List semua transaksi dengan filter
- Badge status bayar

### ✅ Pengaturan (Admin)
- Nama toko & alamat
- Komisi default (%) — otomatis terisi saat buat transaksi baru

### ✅ Auth
- Login/logout
- Role: admin & kasir
- Pengaturan hanya bisa diakses admin

---

## 🗄️ Struktur Database

```
users               — Login kasir/admin
settings            — Konfigurasi toko (komisi, nama, dll)
transaksi           — Header transaksi
item_transaksi      — Grup buah per supplier (jeruk A, jeruk B, dll)
detail_peti         — Detail per peti (berat kotor, kemasan, bersih)
biaya_operasional   — Biaya kuli, pengiriman, dll
```

---

## 🔄 Alur Kalkulasi

```
Setiap peti:
  berat_bersih = berat_kotor - berat_kemasan

Setiap item buah:
  subtotal = SUM(berat_bersih semua peti) × harga_per_kg

Transaksi:
  total_kotor  = SUM(subtotal semua item)
  total_komisi = total_kotor × (komisi_persen / 100)
  total_biaya  = SUM(nominal biaya operasional)
  total_bersih = total_kotor - total_komisi - total_biaya
```

---

## 🛠️ Tech Stack
- **Laravel 11** (backend)
- **Alpine.js** (dynamic form / repeater)
- **Tailwind CSS** (styling via CDN)
- **Font Awesome** (icons)
- **MySQL** (database)
