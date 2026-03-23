# Deposit Pelanggan — Flutter Implementation Guide

## Apa itu Deposit?

**Deposit** adalah fitur titip uang dari pelanggan ke toko.

Analoginya seperti ini:
- Pelanggan langganan sering beli buah, jadi mereka titip uang dulu ke toko misalnya Rp 5.000.000
- Setiap kali mereka beli tapi belum bayar (hutang/piutang), toko bisa pakai uang titipan itu untuk melunasi
- Sistem otomatis lunasi dari transaksi terlama dulu (FIFO)

**Alur lengkap:**
```
Pelanggan titip uang → Deposit bertambah
     ↓
Pelanggan beli → Transaksi status "tempo" (hutang)
     ↓
Admin pakai deposit untuk bayar hutang → Piutang berkurang, Deposit berkurang
```

**Kolom penting di setiap deposit:**
- `nominal` — jumlah yang dititipkan
- `terpakai` — sudah dipakai untuk bayar berapa
- `sisa` — sisa deposit yang belum terpakai (`nominal - terpakai`)

---

## Keterkaitan Fitur

```
Pelanggan ──→ punya banyak Deposit
Pelanggan ──→ punya banyak Transaksi (bisa hutang / piutang)

Deposit dipakai untuk bayar Piutang:
  POST /deposit/bayar-piutang  →  kurangi sisa deposit, lunasi transaksi
  POST /piutang/bayar (metode=deposit)  →  alternatif endpoint yang sama fungsinya
```

---

## API Endpoints

### GET /pelanggan/{id} — Cek saldo deposit & piutang pelanggan

Sebelum menggunakan deposit, cek dulu berapa sisa deposit dan total piutang pelanggan.

Gunakan endpoint pelanggan yang sudah ada. Response-nya sudah include computed attribute:

```json
{
  "id": 1,
  "kode_pelanggan": "PLG-0001",
  "nama": "Toko Buah Segar",
  "telepon": "08123456789",
  "toko": "Pasar Minggu",
  "total_deposit": 3500000,
  "total_piutang": 2000000
}
```

> `total_deposit` = jumlah semua `sisa` deposit aktif pelanggan ini
> `total_piutang` = jumlah semua `sisa_tagihan` transaksi yang belum lunas

---

### GET /deposit — List riwayat deposit

**Query params:**

| Param | Type | Keterangan |
|---|---|---|
| pelanggan_id | integer | Filter per pelanggan (optional) |
| per_page | integer | Default 20 |

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "kode_deposit": "DEP-20260315-0001",
        "pelanggan_id": 5,
        "nominal": 5000000,
        "terpakai": 1500000,
        "sisa": 3500000,
        "metode": "tunai",
        "referensi": null,
        "catatan": "Titip untuk minggu ini",
        "created_at": "2026-03-15T08:00:00.000000Z",
        "pelanggan": {
          "id": 5,
          "nama": "Toko Buah Segar",
          "toko": "Pasar Minggu"
        }
      }
    ],
    "total": 1
  }
}
```

---

### POST /deposit — Tambah deposit baru

Pelanggan titip uang ke toko.

**Request body:**
```json
{
  "pelanggan_id": 5,
  "nominal": 5000000,
  "metode": "tunai",
  "referensi": "BCA-123456",
  "catatan": "Titip untuk minggu ini"
}
```

| Field | Required | Keterangan |
|---|---|---|
| pelanggan_id | Ya | ID pelanggan yang titip |
| nominal | Ya | Jumlah titipan, min 1 |
| metode | Tidak | Default "tunai". Enum: tunai, transfer, qris |
| referensi | Tidak | Nomor referensi transfer (jika ada) |
| catatan | Tidak | Catatan bebas |

**Response:** `201 Created`
```json
{
  "success": true,
  "message": "Deposit berhasil ditambahkan.",
  "data": { ...deposit object }
}
```

---

### POST /deposit/bayar-piutang — Pakai deposit untuk bayar hutang

Gunakan saldo deposit pelanggan untuk melunasi piutang mereka.

**Request body:**
```json
{
  "pelanggan_id": 5,
  "jumlah": 1500000,
  "transaksi_ids": [12, 15]
}
```

| Field | Required | Keterangan |
|---|---|---|
| pelanggan_id | Ya | ID pelanggan |
| jumlah | Ya | Jumlah yang akan dibayarkan dari deposit |
| transaksi_ids | Tidak | Spesifik transaksi mana yang dibayar. Jika kosong → sistem FIFO otomatis dari yang terlama |

**Response sukses:**
```json
{
  "success": true,
  "message": "Pembayaran via deposit berhasil.",
  "data": {
    "total_dibayar": 1500000,
    "sisa_deposit": 2000000,
    "detail_pembayaran": [
      {
        "transaksi_id": 12,
        "kode_transaksi": "TRX-20260310-0003",
        "dibayar": 800000,
        "sisa_tagihan": 0,
        "status_bayar": "lunas"
      },
      {
        "transaksi_id": 15,
        "kode_transaksi": "TRX-20260312-0001",
        "dibayar": 700000,
        "sisa_tagihan": 1200000,
        "status_bayar": "cicil"
      }
    ]
  }
}
```

**Response jika saldo deposit tidak cukup:** `422`
```json
{
  "success": false,
  "message": "Saldo deposit tidak cukup. Saldo: Rp 500.000, Diperlukan: Rp 1.500.000"
}
```

---

### GET /piutang — List piutang pelanggan

Dipakai untuk menampilkan daftar hutang yang bisa dibayar dengan deposit.

**Query params:**

| Param | Type | Keterangan |
|---|---|---|
| pelanggan_id | integer | Filter per pelanggan |
| nama_pelanggan | string | Cari by nama |
| jatuh_tempo | boolean | Filter yang sudah lewat jatuh tempo |

**Response:**
```json
{
  "success": true,
  "data": {
    "data": {
      "data": [
        {
          "id": 12,
          "kode_transaksi": "TRX-20260310-0003",
          "nama_pelanggan": "Toko Buah Segar",
          "total_tagihan": 800000,
          "total_dibayar": 0,
          "sisa_tagihan": 800000,
          "status_bayar": "tempo",
          "tanggal_jatuh_tempo": "2026-03-20"
        }
      ]
    },
    "summary": {
      "total_piutang": 2000000,
      "total_transaksi_open": 3
    }
  }
}
```

---

## Model

```dart
class Deposit {
  final int id;
  final String kodeDeposit;
  final int pelangganId;
  final double nominal;
  final double terpakai;
  final double sisa;
  final String metode; // "tunai" | "transfer" | "qris"
  final String? referensi;
  final String? catatan;
  final String createdAt;
  final Pelanggan? pelanggan;

  double get persenTerpakai => nominal > 0 ? (terpakai / nominal * 100) : 0;
}

class BayarPiutangResult {
  final double totalDibayar;
  final double sisaDeposit;
  final List<DetailPembayaranDeposit> detailPembayaran;
}

class DetailPembayaranDeposit {
  final int transaksiId;
  final String kodeTransaksi;
  final double dibayar;
  final double sisaTagihan;
  final String statusBayar; // "lunas" | "cicil" | "tempo"
}
```

---

## Service

```dart
class DepositService {
  // List riwayat deposit, optional filter per pelanggan
  Future<PaginatedResponse<Deposit>> getList({int? pelangganId, int page = 1});

  // Tambah deposit baru
  Future<Deposit> tambahDeposit({
    required int pelangganId,
    required double nominal,
    String metode = 'tunai',
    String? referensi,
    String? catatan,
  });

  // Pakai deposit untuk bayar piutang
  Future<BayarPiutangResult> bayarPiutang({
    required int pelangganId,
    required double jumlah,
    List<int>? transaksiIds, // opsional, jika null → FIFO otomatis
  });
}
```

---

## UI

### 1. Halaman Deposit Pelanggan (DepositScreen)

Bisa diakses dari:
- Detail halaman Pelanggan → tab/button "Deposit"
- Menu Piutang → tombol "Bayar dengan Deposit"

**Layout:**

```
┌─────────────────────────────────┐
│  Toko Buah Segar                │
│  Saldo Deposit: Rp 3.500.000 ✅ │  ← hijau jika ada saldo
│  Total Piutang: Rp 2.000.000 ⚠️ │  ← merah jika ada hutang
├─────────────────────────────────┤
│  [+ Tambah Deposit] [Bayar Hutang]│  ← 2 tombol aksi
├─────────────────────────────────┤
│  Riwayat Deposit                │
│─────────────────────────────────│
│  DEP-20260315-0001              │
│  15 Mar 2026 • Tunai            │
│  Nominal:  Rp 5.000.000         │
│  Terpakai: Rp 1.500.000 ████░░  │  ← progress bar
│  Sisa:     Rp 3.500.000         │
└─────────────────────────────────┘
```

Setiap card deposit tampilkan:
- Kode deposit + tanggal
- Metode (Tunai / Transfer / QRIS)
- Nominal, terpakai (dengan progress bar), sisa
- Warna sisa: hijau jika > 0, abu-abu jika sudah habis

---

### 2. Bottom Sheet — Tambah Deposit

```
┌─────────────────────────────────┐
│  Tambah Deposit                 │
│                                 │
│  Pelanggan: Toko Buah Segar     │  ← sudah terisi jika dari halaman pelanggan
│                                 │
│  Nominal: [________________]    │
│                                 │
│  Metode: [Tunai           ▼]    │
│                                 │
│  Referensi: [______________]    │  ← muncul jika metode = transfer/qris
│                                 │
│  Catatan: [________________]    │
│                                 │
│       [      SIMPAN      ]      │
└─────────────────────────────────┘
```

- Field referensi hanya muncul jika metode bukan tunai
- Setelah simpan: refresh halaman + tampilkan SnackBar sukses

---

### 3. Bottom Sheet — Bayar Hutang dengan Deposit

```
┌─────────────────────────────────┐
│  Bayar Hutang via Deposit       │
│                                 │
│  Saldo deposit:  Rp 3.500.000   │
│  Total hutang:   Rp 2.000.000   │
│                                 │
│  Jumlah bayar:                  │
│  [2.000.000_____________]       │
│                                 │
│  Pilih transaksi (opsional):    │
│  ☑ TRX-0003  Rp 800.000        │  ← checklist, default semua dicek
│  ☑ TRX-0001  Rp 700.000        │
│  ☑ TRX-0005  Rp 500.000        │
│                                 │
│  Jika tidak dipilih → FIFO auto │
│                                 │
│       [      BAYAR      ]       │
└─────────────────────────────────┘
```

- Default `jumlah` = min(total saldo deposit, total piutang)
- Jika user pilih transaksi spesifik → kirim `transaksi_ids`
- Jika tidak pilih → `transaksi_ids` kosong, sistem FIFO otomatis
- Validasi: jumlah tidak boleh melebihi saldo deposit
- Setelah bayar: tampilkan dialog hasil (berapa yang dilunasi, detail per transaksi)

---

### 4. Dialog Hasil Pembayaran

```
┌─────────────────────────────────┐
│  ✅ Pembayaran Berhasil         │
│                                 │
│  Total dibayar:  Rp 1.500.000   │
│  Sisa deposit:   Rp 2.000.000   │
│                                 │
│  Detail:                        │
│  TRX-0003  Rp 800.000  → LUNAS  │
│  TRX-0001  Rp 700.000  → CICIL  │
│                                 │
│           [  TUTUP  ]           │
└─────────────────────────────────┘
```

Status transaksi warna:
- LUNAS → hijau
- CICIL → oranye
- TEMPO → merah

---

## Validasi di Flutter (sebelum kirim ke API)

| Kondisi | Pesan |
|---|---|
| nominal = 0 | "Nominal tidak boleh kosong" |
| jumlah bayar > saldo deposit | "Jumlah melebihi saldo deposit" |
| tidak ada piutang | "Pelanggan tidak memiliki hutang" |
| pelanggan tidak dipilih | "Pilih pelanggan terlebih dahulu" |

---

## Format Angka

Sama dengan modul lain — gunakan format Rupiah:

```dart
// Hasil: "Rp 3.500.000"
NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0)
```

---

## State & Error Handling

| State | Tampilan |
|---|---|
| Loading | CircularProgressIndicator |
| Saldo deposit = 0 | Tampilkan info + tombol tambah deposit saja |
| Tidak ada piutang | Sembunyikan tombol "Bayar Hutang" |
| Error saldo tidak cukup | SnackBar merah dengan pesan dari API |
| Sukses tambah deposit | SnackBar: "Deposit berhasil ditambahkan" |
| Sukses bayar hutang | Dialog hasil pembayaran |

---

## Navigasi & Integrasi

- Akses dari **halaman detail Pelanggan** → tambahkan tab atau tombol "Deposit & Hutang"
- Akses dari **menu Piutang** → di setiap baris piutang, jika pelanggan punya deposit tampilkan badge/chip "Ada deposit Rp xxx" + tombol shortcut "Bayar dengan Deposit"
- Setelah bayar berhasil → refresh halaman piutang

---

## Ringkasan Alur Lengkap

```
1. Buka halaman Pelanggan
   → Lihat saldo deposit & total piutang

2. Jika pelanggan mau titip uang:
   → Tap [+ Tambah Deposit]
   → Isi nominal, metode, simpan
   → Saldo deposit bertambah

3. Jika mau bayar hutang pakai deposit:
   → Tap [Bayar Hutang]
   → Isi jumlah, pilih transaksi (opsional)
   → Tap Bayar
   → Sistem lunasi dari terlama (FIFO)
   → Saldo deposit berkurang
   → Piutang berkurang / lunas
```
