# Kas Laci — Flutter Implementation Guide

## Konteks

Kas Laci adalah modul rekap keuangan kas fisik (uang di laci). Menampilkan daftar
debit/kredit dengan running saldo, dan bisa input manual.

Uang masuk bisa otomatis dari sistem (transaksi/pembayaran tunai) atau input manual.
Uang keluar hanya input manual.

---

## API Endpoints

Base URL sudah terkonfigurasi. Semua endpoint butuh Bearer Token.

### GET /kas-laci

List entri kas laci dengan running saldo.

**Query params** (semua optional):

| Param | Type | Keterangan |
|---|---|---|
| tanggal_dari | string (YYYY-MM-DD) | Filter dari tanggal |
| tanggal_sampai | string (YYYY-MM-DD) | Filter sampai tanggal |
| jenis | "masuk" \| "keluar" | Filter jenis entri |

**Response:**
```json
{
  "success": true,
  "data": {
    "saldo_awal": 500000,
    "data": [
      {
        "id": 1,
        "kode_kas": "KAS-20260315-0001",
        "tanggal": "2026-03-15",
        "keterangan": "Penjualan tunai TRX-20260315-0001 - Toko Buah Segar",
        "jenis": "masuk",
        "nominal": 250000,
        "metode_sumber": "tunai",
        "referensi_tipe": "transaksi",
        "is_auto": true,
        "saldo": 750000,
        "dibuat_oleh": { "id": 1, "name": "Admin" }
      },
      {
        "id": 2,
        "kode_kas": "KAS-20260315-0002",
        "tanggal": "2026-03-15",
        "keterangan": "Beli plastik",
        "jenis": "keluar",
        "nominal": 50000,
        "metode_sumber": "tunai",
        "referensi_tipe": "manual",
        "is_auto": false,
        "saldo": 700000,
        "dibuat_oleh": { "id": 1, "name": "Admin" }
      }
    ]
  }
}
```

---

### GET /kas-laci/summary

Ringkasan kas. Query params sama dengan list (optional).

**Response:**
```json
{
  "success": true,
  "data": {
    "total_masuk": 1500000,
    "total_keluar": 300000,
    "saldo_periode": 1200000,
    "saldo_kas": 2500000
  }
}
```

> `saldo_kas` = saldo all-time (tidak terpengaruh filter tanggal).
> `saldo_periode` = total masuk - total keluar dalam rentang filter.

---

### POST /kas-laci

Input manual entri kas.

**Request body:**
```json
{
  "tanggal": "2026-03-15",
  "keterangan": "Beli plastik",
  "jenis": "keluar",
  "nominal": 50000,
  "metode_sumber": "tunai"
}
```

| Field | Required | Keterangan |
|---|---|---|
| tanggal | Ya | Format YYYY-MM-DD |
| keterangan | Ya | Max 500 karakter |
| jenis | Ya | "masuk" atau "keluar" |
| nominal | Ya | Angka, min 1 |
| metode_sumber | Tidak | Default "tunai". Enum: tunai, transfer, qris, lainnya |

**Response:** `201 Created`
```json
{
  "success": true,
  "message": "Kas laci berhasil dicatat",
  "data": { ...entry }
}
```

---

### DELETE /kas-laci/{id}

Hapus entri manual. Entri otomatis (`is_auto: true`) tidak bisa dihapus.

**Response sukses:** `200 OK`

**Response jika entri otomatis:** `403 Forbidden`
```json
{
  "success": false,
  "message": "Entri otomatis tidak dapat dihapus secara manual. Hapus transaksi atau pembayaran sumbernya."
}
```

---

## Model

```dart
class KasLaciEntry {
  final int id;
  final String kodeKas;
  final String tanggal;
  final String keterangan;
  final String jenis; // "masuk" | "keluar"
  final double nominal;
  final String metodeSumber;
  final String? referensiTipe; // "transaksi" | "pembayaran" | "manual"
  final bool isAuto;
  final double saldo; // running saldo dari API

  bool get isMasuk => jenis == 'masuk';
}

class KasLaciSummary {
  final double totalMasuk;
  final double totalKeluar;
  final double saldoPeriode;
  final double saldoKas; // saldo all-time
}

class KasLaciResponse {
  final double saldoAwal;
  final List<KasLaciEntry> data;
}
```

---

## Service / Repository

Buat `KasLaciService` dengan method berikut. Ikuti pattern service yang sudah ada di project.

```dart
class KasLaciService {
  Future<KasLaciResponse> getList({
    String? tanggalDari,
    String? tanggalSampai,
    String? jenis,
  });

  Future<KasLaciSummary> getSummary({
    String? tanggalDari,
    String? tanggalSampai,
  });

  Future<void> tambahManual({
    required String tanggal,
    required String keterangan,
    required String jenis,
    required double nominal,
    String metodeSumber = 'tunai',
  });

  Future<void> hapus(int id);
}
```

---

## UI — KasLaciScreen

### Layout Utama

```
┌─────────────────────────────────┐
│  Saldo Kas                      │  ← Card hijau, saldo_kas all-time
│  Rp 2.500.000                   │
├─────────────────────────────────┤
│  [15 Mar] s/d [15 Mar] [Semua▼] │  ← Filter bar
├─────────────────────────────────┤
│  Masuk         Keluar   Periode │  ← Card ringkasan (jika filter aktif)
│  Rp 1.500.000  Rp 300.000 Rp.. │
├─────────────────────────────────┤
│ ↑  Penjualan tunai TRX-...      │
│    15 Mar 2026       Rp 250.000 │
│    [Auto]              Saldo .. │
│─────────────────────────────────│
│ ↓  Beli plastik                 │
│    15 Mar 2026        Rp 50.000 │
│                        Saldo .. │
└─────────────────────────────────┘
                              [+]   ← FAB
```

### Card Saldo Atas

- Warna background: hijau
- Label: "Saldo Kas"
- Nilai: `saldo_kas` dari summary, format Rupiah
- Selalu menampilkan saldo all-time (tidak terpengaruh filter)

### Filter Bar

- Date range picker: tampilkan "DD MMM" (contoh: "15 Mar")
- Default: hari ini s/d hari ini
- Dropdown jenis: Semua / Masuk / Keluar
- Saat filter berubah: reload list + summary

### Card Ringkasan Periode

Tampil di bawah filter bar jika filter tanggal aktif.

| Label | Nilai | Warna |
|---|---|---|
| Total Masuk | total_masuk | Hijau |
| Total Keluar | total_keluar | Merah |
| Saldo Periode | saldo_periode | Hitam/putih |

### List Entri

Setiap baris:

```
[icon]  Keterangan teks                    Rp nominal
        tanggal • metode_sumber  [Auto?]   saldo: Rp xxx
```

- **Icon**: `Icons.arrow_upward` hijau untuk masuk, `Icons.arrow_downward` merah untuk keluar
- **Nominal**: bold, hijau jika masuk / merah jika keluar
- **Saldo**: teks kecil abu-abu di kanan bawah, format "Saldo: Rp xxx"
- **Badge "Auto"**: tampilkan chip kecil abu-abu jika `is_auto == true`
- **Hapus**: hanya tampilkan tombol hapus (swipe atau icon) jika `is_auto == false`

### FAB — Tambah Manual

Tombol `+` di kanan bawah, buka bottom sheet.

**Bottom sheet form:**

```
┌─────────────────────────────────┐
│ Tambah Kas Laci                 │
│                                 │
│ Jenis:  [  MASUK  ] [ KELUAR ]  │  ← ToggleButton
│                                 │
│ Tanggal: [15 Maret 2026    📅]  │
│                                 │
│ Nominal: [________________]     │
│                                 │
│ Keterangan: [______________]    │
│                                 │
│ Metode: [Tunai           ▼]     │
│                                 │
│          [      SIMPAN      ]   │
└─────────────────────────────────┘
```

- Default jenis: Keluar (karena masuk biasanya otomatis)
- Default tanggal: hari ini
- Default metode: Tunai
- Validasi: nominal > 0, keterangan tidak kosong
- Setelah simpan: tutup bottom sheet + refresh list

### Hapus Entri

- Swipe to delete atau long press → confirm dialog: "Hapus entri ini?"
- Jika `is_auto == true`: tampilkan SnackBar error, jangan panggil API
- Jika API return 403: tampilkan SnackBar error dengan pesan dari API
- Jika sukses: hapus dari list + refresh summary

---

## Format Angka

Gunakan format Rupiah dengan titik sebagai pemisah ribuan:

```dart
// Contoh hasil: "Rp 1.500.000"
String formatRupiah(double nominal) {
  // gunakan intl package: NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0)
}
```

---

## State & Error Handling

| State | Tampilan |
|---|---|
| Loading | CircularProgressIndicator di tengah |
| Error network | Pesan error + tombol Retry |
| List kosong | Ilustrasi/ikon + teks "Belum ada entri kas" |
| Sukses tambah | SnackBar: "Kas laci berhasil dicatat" |
| Sukses hapus | SnackBar: "Entri berhasil dihapus" |
| Gagal hapus (is_auto) | SnackBar error merah |

---

## Navigasi

Tambahkan menu **Kas Laci** ke drawer atau bottom navigation yang sudah ada:

```dart
// Icon
Icons.account_balance_wallet

// Label
'Kas Laci'

// Route
'/kas-laci'
```

---

## Catatan Penting

- Entri `is_auto: true` **tidak boleh dihapus** — sembunyikan tombol hapus, bukan disable
- Refresh list + summary setelah setiap aksi (tambah / hapus)
- `saldo` pada setiap entri sudah dihitung oleh API sebagai running balance — tidak perlu hitung ulang di Flutter
- `saldo_kas` pada summary adalah saldo fisik kas saat ini (all-time), tampilkan ini di card atas
- Ikuti struktur folder, naming convention, dan pattern yang sudah ada di project
