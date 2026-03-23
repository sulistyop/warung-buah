# Flutter Prompt — Fitur Rekap Harian Supplier (FIFO Settlement)

## Konteks Bisnis

**FIFO sudah dihandle penuh di backend (Laravel).** Flutter hanya consume hasilnya.

Alur FIFO di backend:
1. **Barang Datang** → setiap batch dicatat di `DetailBarangDatang` dengan `stok_awal`, `stok_terjual`, `stok_sisa`, `status_stok`
2. **Transaksi penjualan** → backend otomatis kurangi stok dari batch terlama dulu (FIFO)
3. **Rekap** → absensi harian supplier. Hanya bisa dibuat jika SEMUA batch hari itu sudah `status_stok = "habis"` (terjual habis)

Flutter perlu:
- Menampilkan sisa stok per batch di detail BarangDatang (stok_sisa, status_stok)
- Modul Rekap lengkap: list, create, detail, finalisasi, nota

---

## Data Models Dart

### `Rekap`
```dart
class Rekap {
  final int id;
  final String kodeRekap;         // "RKP-20260307-0001"
  final int supplierId;
  final DateTime tanggal;
  final double komisiPersen;
  final double kuliPerPeti;
  final int totalPeti;
  final double totalKotor;        // sum(subtotal detail)
  final double totalKomisi;       // totalKotor × komisiPersen/100
  final double totalKuli;         // totalPeti × kuliPerPeti
  final double totalOngkos;       // ongkos angkut tambahan
  final String? keteranganOngkos;
  final double totalBusuk;        // sum(komplain.total)
  final double pendapatanBersih;  // totalKotor - totalKomisi - totalKuli - totalOngkos
  final double sisa;              // pendapatanBersih - totalBusuk
  final String status;            // 'draft' | 'final'
  final DateTime? finalAt;
  final int dibuatOleh;
  final Supplier? supplier;
  final User? dibuatOlehUser;
  final List<DetailRekap> details;
  final List<KomplainRekap> komplain;

  bool get isDraft => status == 'draft';
  bool get isFinal => status == 'final';
}
```

### `DetailRekap`
```dart
class DetailRekap {
  final int id;
  final int rekapId;
  final String namaProduk;
  final String? ukuran;
  final int jumlahPeti;
  final double totalBeratKotor;
  final double totalBeratPeti;
  final double totalBeratBersih;
  final double hargaPerKg;       // harga beli dari batch FIFO
  final double subtotal;         // totalBeratBersih × hargaPerKg
}
```

### `KomplainRekap`
```dart
class KomplainRekap {
  final int id;
  final int rekapId;
  final String namaProduk;
  final int jumlahBs;            // jumlah buah busuk/retur
  final double hargaGanti;       // harga ganti per unit
  final double total;            // jumlahBs × hargaGanti
  final String? keterangan;
}
```

### `CekSiapRekapResponse`
```dart
class CekSiapRekapResponse {
  final bool siap;
  final List<ProdukBelumHabis> belumHabis;
  final String pesan;
}

class ProdukBelumHabis {
  final String kodeBd;
  final String namaProduk;
  final String? ukuran;
  final double stokSisa;
}
```

---

## API Endpoints

**Auth:** `Authorization: Bearer {token}`

---

### 0. Siap Direkap ← tombol satu klik di AppBar/FAB RekapListPage
```
GET /api/rekap/siap-direkap

Response 200:
{
  "success": true,
  "data": [
    {
      "supplier_id": 1,
      "supplier_nama": "Pak Hendra Wijaya",
      "tanggal": "2026-03-07",
      "total_letter": 3,
      "kode_bd_list": ["BD-20260307-0001", "BD-20260307-0002"]
    }
  ]
}
// Array kosong jika tidak ada yang siap direkap
```

---

### 1. List Rekap
```
GET /api/rekap
Query: page, per_page, supplier_id, tanggal_dari, tanggal_sampai, status

Response 200:
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [ Rekap... ],
    "total": 10,
    "last_page": 1,
    "per_page": 20
  }
}
```

---

### 2. Detail Rekap
```
GET /api/rekap/{id}

Response 200:
{
  "success": true,
  "data": Rekap (dengan supplier, details, komplain, dibuatOleh)
}
```

---

### 3. Cek Siap Rekap ← WAJIB dipanggil sebelum form create
```
GET /api/rekap/cek-siap/{supplier_id}/{tanggal}

Response 200 (siap):
{
  "success": true,
  "data": {
    "siap": true,
    "belum_habis": [],
    "pesan": "Semua produk sudah habis. Rekap bisa dibuat."
  }
}

Response 200 (belum siap):
{
  "success": true,
  "data": {
    "siap": false,
    "belum_habis": [
      {
        "kode_bd": "BD-20260307-0001",
        "nama_produk": "Apel Fuji",
        "ukuran": "A",
        "stok_sisa": 25.5
      }
    ],
    "pesan": "Ada 2 produk yang belum habis. Rekap belum bisa dibuat."
  }
}
```

---

### 3b. Suggestion Detail Rekap ← 1 baris per transaksi, sesuai format nota
```
GET /api/rekap/suggestion/{supplier_id}/{tanggal}

Response 200:
{
  "success": true,
  "data": {
    "suggestions": [
      // Anggur A — 3 baris (3 transaksi terpisah, bisa beda harga)
      {
        "nama_produk": "Anggur",
        "ukuran": "A",
        "harga_per_kg": 13500,
        "jumlah_peti": 33,
        "total_berat_kotor": 1077,
        "total_berat_peti": 142,
        "total_berat_bersih": 913,
        "subtotal": 12325500,
        "_kode_bd": "BD-20260205-0001",
        "_item_transaksi_id": 101
      },
      {
        "nama_produk": "Anggur",
        "ukuran": "A",
        "harga_per_kg": 12000,
        "jumlah_peti": 4,
        "total_berat_kotor": 120,
        "total_berat_peti": 16,
        "total_berat_bersih": 104,
        "subtotal": 1248000,
        "_kode_bd": "BD-20260205-0001",
        "_item_transaksi_id": 102
      }
    ],
    "total_letter": 2  // total baris
  }
}
```

**Catatan penting:**
- **1 baris = 1 item_transaksi** — cocok dengan format nota rekap (lihat contoh nota)
- Produk yang sama bisa punya multiple baris jika dijual dalam beberapa transaksi berbeda
- `_kode_bd` dan `_item_transaksi_id` hanya referensi tampilan, **JANGAN kirim ke store**
- Di UI, **kelompokkan rows berdasarkan (nama_produk + ukuran)** dengan subtotal per kelompok

---

### 4. Buat Rekap
```
POST /api/rekap
Content-Type: application/json

{
  "supplier_id": 1,
  "tanggal": "2026-03-07",
  "total_ongkos": 50000,               // optional, default 0
  "keterangan_ongkos": "Ongkos angkut", // optional
  "details": [                          // WAJIB diisi dari hasil suggestion (sudah diedit user)
    {
      "nama_produk": "Apel Fuji",
      "ukuran": "A",
      "jumlah_peti": 10,
      "total_berat_kotor": 275.0,
      "total_berat_peti": 25.0,
      "total_berat_bersih": 250.0,
      "harga_per_kg": 15000
    }
  ],
  "komplain": [                         // optional
    {
      "nama_produk": "Apel Fuji",
      "jumlah_bs": 5,
      "harga_ganti": 15000,
      "keterangan": "Buah busuk saat tiba"
    }
  ]
}

// Catatan: jika 'details' tidak dikirim sama sekali (omit, bukan array kosong),
// backend akan auto-generate dari data barang datang (berat = jumlah stok, peti = 0).
// Namun DIANJURKAN selalu kirim details yang sudah diisi user dari suggestion.

Response 201: { "success": true, "message": "Rekap berhasil dibuat.", "data": Rekap }
Response 422 (belum semua habis):
{
  "success": false,
  "message": "Rekap belum bisa dibuat. Produk berikut belum habis: ...",
  "data": { "belum_habis": [...] }
}
Response 422 (sudah ada):
{ "success": false, "message": "Rekap untuk supplier dan tanggal ini sudah ada (ID: 3)." }
```

---

### 5. Update Rekap (hanya draft)
```
PUT /api/rekap/{id}

Body sama seperti store, semua field optional.
Jika 'details' dikirim → seluruh detail DIGANTI.
Jika 'komplain' dikirim → seluruh komplain DIGANTI.

Response 200: { "success": true, "message": "Rekap berhasil diupdate.", "data": Rekap }
Response 422: "Rekap yang sudah final tidak bisa diubah."
```

---

### 6. Finalisasi Rekap (draft → final, tidak bisa diubah)
```
POST /api/rekap/{id}/final

Response 200: { "success": true, "message": "Rekap berhasil difinalisasi.", "data": Rekap }
Response 422: "Rekap sudah final."
```

---

### 7. Hapus Rekap (hanya draft)
```
DELETE /api/rekap/{id}

Response 200: { "success": true, "message": "Rekap berhasil dihapus." }
Response 422: "Rekap yang sudah final tidak bisa dihapus."
```

---

### 8. Nota Rekap (untuk print/share)
```
GET /api/nota/rekap/{id}

Response 200:
{
  "success": true,
  "data": {
    "usaha": { "nama": "...", "alamat": "...", "telepon": "..." },
    "rekap": {
      "kode": "RKP-20260307-0001",
      "tanggal": "07/03/2026",
      "supplier": "PT Buah Segar",
      "status": "final"
    },
    "details": [ DetailRekap... ],
    "komplain": [ KomplainRekap... ],
    "summary": {
      "total_peti": 25,
      "total_kotor": 3750000,
      "komisi_persen": 10,
      "total_komisi": 375000,
      "kuli_per_peti": 2000,
      "total_kuli": 50000,
      "total_ongkos": 50000,
      "total_busuk": 75000,
      "pendapatan_bersih": 3275000,
      "sisa": 3200000
    }
  }
}
```

---

## Tampilan Stok FIFO di BarangDatang Detail

Di `DetailBarangDatang`, tambahkan field berikut ke model Dart:

```dart
class DetailBarangDatang {
  // ... field yang sudah ada (namaProduk, ukuran, jumlah, hargaBeli, dll)

  // Field FIFO tracking (diisi setelah confirm):
  final double? stokAwal;       // = jumlah saat confirm
  final double? stokTerjual;    // berapa yang sudah terjual
  final double? stokSisa;       // sisa stok sekarang
  final String? statusStok;     // 'available' | 'habis'

  bool get isHabis => statusStok == 'habis';
}
```

Di `BarangDatangDetailPage`, pada card tiap letter yang sudah confirmed, tampilkan:
```
┌─────────────────────────────────────────────┐
│ Apel Fuji - Grade A                         │
│ Jumlah: 100 kg · Rp 15.000/kg               │
│ ─────────────────────────────────────────── │
│ Stok Awal    : 100 kg                       │
│ Terjual      : 75 kg                        │
│ Sisa         : 25 kg    ● AVAILABLE         │  ← chip hijau
│                                             │
│ Kode Produk  : PRD-0001                     │
└─────────────────────────────────────────────┘

Jika habis:
│ Sisa         : 0 kg     ✓ HABIS             │  ← chip abu-abu
```

---

## Halaman UI yang Perlu Dibuat

---

### `RekapListPage`

**Layout:**
```
AppBar: "Rekap Harian"    [+ Rekap Baru]
──────────────────────────────────────────
Filter row:
  [Semua] [Draft] [Final]
  [Date Range]  [Supplier ▾]
──────────────────────────────────────────
Card list:
┌──────────────────────────────────┐
│ RKP-20260307-0001   [DRAFT●]     │
│ PT Buah Segar                    │
│ 07 Mar 2026                      │
│ Pendapatan Bersih: Rp 3.275.000  │
│ 25 peti · Sisa: Rp 3.200.000    │
│                  [Finalisasi ▶]  │
└──────────────────────────────────┘
```

- Badge status: chip kuning = draft, chip hijau = final
- Tombol "Finalisasi" hanya tampil jika draft (tidak perlu masuk detail)
- FAB / tombol "+ Rekap Baru" → navigasi ke `RekapFormPage` (mode create, Step 1)

---

### `RekapDetailPage`

**Layout:**
```
AppBar: "RKP-20260307-0001"   [Edit ✎]  ← hanya jika draft
──────────────────────────────────────────
Section: Info Rekap
  Supplier       : PT Buah Segar
  Tanggal        : 07 Maret 2026
  Status         : ● DRAFT / ✓ FINAL
  Dibuat oleh    : Admin
  Final pada     : — / 07 Mar 2026 14:30

──────────────────────────────────────────
Section: Ringkasan Keuangan
  ┌────────────────────────────────────┐
  │ Total Kotor       Rp  3.750.000    │
  │ Komisi (10%)    - Rp    375.000    │
  │ Kuli (25 peti)  - Rp     50.000    │
  │ Ongkos          - Rp     50.000    │
  │ ─────────────────────────────────  │
  │ Pendapatan Bersih  Rp  3.275.000   │
  │ Komplain (busuk) - Rp     75.000   │
  │ ─────────────────────────────────  │
  │ SISA               Rp  3.200.000   │
  └────────────────────────────────────┘

──────────────────────────────────────────
Section: Detail Produk
  ┌──────────────────────────────────────────────────────┐
  │ Anggur (A)                                           │
  │ Peti  B.Kotor  B.Peti  B.Bersih  Jumlah              │
  │  33    1.077    142      913   Rp 12.325.500          │
  │   4      120     16      104   Rp  1.248.000          │
  │   3      102     12       90   Rp    855.000          │
  │ ────────────────────────────────────────────────────  │
  │ Total: 40 peti               Rp 14.428.500           │
  └──────────────────────────────────────────────────────┘

──────────────────────────────────────────
Section: Komplain/Busuk (jika ada)
  ┌──────────────────────────────────────┐
  │ Apel Fuji  · 5 buah busuk            │
  │ Rp 15.000/buah → Rp 75.000           │
  │ Catatan: Buah busuk saat tiba        │
  └──────────────────────────────────────┘

──────────────────────────────────────────
[🖨️ Cetak Nota]    [Hapus]   [Finalisasi ▶]
← tombol Hapus & Finalisasi hanya jika draft
```

**Jika final:**
- Tombol Edit & Hapus hilang
- Hanya tampil tombol "Cetak Nota"

---

### `RekapFormPage` (Create) — 2 Step

Page ini memiliki **2 tahap** dalam 1 halaman.

---

#### STEP 1 — Cari

```
AppBar: "Buat Rekap"
─────────────────────────────────────────
  Supplier *
  ┌────────────────────────────────────┐
  │ 🔍  Pak Hendra Wijaya           ▾  │
  └────────────────────────────────────┘

  Tanggal  (opsional — kosong = semua)
  ┌────────────────────────────────────┐
  │ 📅  Pilih tanggal...               │
  └────────────────────────────────────┘

  ┌────────────────────────────────────┐
  │          Cari Rekap Tersedia       │  ← tombol hijau, aktif jika supplier diisi
  └────────────────────────────────────┘
─────────────────────────────────────────
```

**Setelah Cari — jika kosong:**
```
  ┌──────────────────────────────────────────┐
  │  ℹ️  Tidak ada rekap tersedia.             │
  │  Pastikan semua produk supplier ini       │
  │  sudah habis terjual.                     │
  └──────────────────────────────────────────┘
```

**Setelah Cari — jika ada hasil (Step 2 expand di bawah):**
```
─────────────────────────────────────────────────
  Tersedia 2 rekap · Pilih untuk generate

  ┌───────────────────────────────────────────┐
  │  07 Maret 2026                     [>]    │
  │  3 produk · BD-0001, BD-0002              │
  └───────────────────────────────────────────┘
  ┌───────────────────────────────────────────┐
  │  05 Maret 2026                     [>]    │
  │  2 produk · BD-0003                       │
  └───────────────────────────────────────────┘
─────────────────────────────────────────────────
```

**Tap salah satu card** → `AlertDialog` konfirmasi:
```
┌──────────────────────────────────────────┐
│  Buat Rekap                              │
│  ──────────────────────────────────────  │
│  Pak Hendra Wijaya                       │
│  07 Maret 2026  ·  3 produk              │
│                                          │
│  Rekap akan dibuat dari data transaksi   │
│  nyata. Anda bisa review sebelum simpan. │
│                                          │
│  [Batal]            [Generate →]         │
└──────────────────────────────────────────┘
```

Tap **"Generate →"** → panggil `GET /rekap/suggestion/{id}/{tgl}` → navigate ke **`RekapPreviewPage`**

---

#### `RekapPreviewPage` — Review & Simpan

Page terpisah. Layout mengikuti format nota rekap yang sebenarnya.

```
AppBar: "Review Rekap"              [Edit ✎]
─────────────────────────────────────────────

  Pak Hendra Wijaya
  05 Februari 2026

─────────────────────────────────────────────
  Detail Produk

  ┌─────────────────────────────────────────────────────────┐
  │  Anggur (A)                                             │
  │  ─────────────────────────────────────────────────────  │
  │  Peti   B.Kotor   B.Peti   B.Bersih   Jumlah            │
  │   33     1.077     142       913    Rp 12.325.500  [✕]  │
  │    4       120      16       104    Rp  1.248.000  [✕]  │
  │    3       102      12        90    Rp    855.000  [✕]  │
  │  ─────────────────────────────────────────────────────  │
  │  Total: 40 peti                   Rp 14.428.500         │
  └─────────────────────────────────────────────────────────┘

  ┌─────────────────────────────────────────────────────────┐
  │  Jeruk (B)                                              │
  │  Peti   B.Kotor   B.Peti   B.Bersih   Jumlah            │
  │   10       250      20       230    Rp  2.760.000  [✕]  │
  │  ─────────────────────────────────────────────────────  │
  │  Total: 10 peti                   Rp  2.760.000         │
  └─────────────────────────────────────────────────────────┘

  [+ Tambah Baris Manual]

─────────────────────────────────────────────
  Komplain / Busuk  (opsional)

  ┌──────────────────────────────────────────┐
  │  Produk    Qty    Harga Ganti   Total    │
  │  Anggur A  22 BS  Rp 13.500  Rp 297.000 [✕]│
  └──────────────────────────────────────────┘
  [+ Tambah Komplain]

─────────────────────────────────────────────
  Ongkos Tambahan

  Total Ongkos    [760.000          ]
  Keterangan      [Ongkos angkut + biaya lain]

─────────────────────────────────────────────
  Ringkasan

  ┌──────────────────────────────────────────┐
  │  Total Kotor              Rp 14.428.500  │
  │  Komisi (7%)            - Rp  1.010.000  │
  │  Kuli (2.000 × 40 peti) - Rp     80.000  │
  │  Ongkos                 - Rp    760.000  │
  │  ──────────────────────────────────────  │
  │  Pendapatan Bersih        Rp 12.578.500  │
  │  Busuk / Komplain       - Rp    297.000  │
  │  ──────────────────────────────────────  │
  │  SISA                     Rp 12.281.500  │
  └──────────────────────────────────────────┘

─────────────────────────────────────────────
             [💾  Simpan sebagai Draft]
```

**Layout detail produk:**
- Rows dikelompokkan by `(nama_produk, ukuran)` → tampil sebagai satu card/section
- Tiap baris dalam group = satu transaksi (bisa beda harga per kg)
- Kolom tabel: Peti | B.Kotor | B.Peti | B.Bersih | Jumlah (subtotal)
- Di bawah group: total peti + total jumlah group
- `[✕]` per baris untuk hapus satu transaksi

**Tombol "Edit ✎" di AppBar:**
- Aktifkan mode edit: semua baris bisa diedit inline (angka bisa diketik ulang)
- Bisa hapus baris `[✕]` atau tambah baris manual `[+ Tambah Baris Manual]`
- Selesai edit → `[✓ Selesai]`
- Ringkasan Keuangan auto-update real-time

---

#### Edit Rekap (dari RekapDetailPage — hanya draft)

Tap "Edit ✎" → langsung ke `RekapPreviewPage` dengan data dari `rekap.details` (grouped by produk), mode edit aktif.
Simpan → `PUT /rekap/{id}`

---

## Logika UI Penting

### A. Cari rekap tersedia (RekapFormPage)
```dart
Future<void> _cariRekap() async {
  if (_selectedSupplierId == null) return;
  setState(() => _isSearching = true);
  try {
    final result = await apiService.getSiapDirekap(
      supplierId: _selectedSupplierId!,
      tanggal: _selectedTanggal,  // nullable — opsional
    );
    setState(() => _siapDirekapList = result);
  } catch (e) {
    showSnackbar('Gagal mencari rekap: $e');
  } finally {
    setState(() => _isSearching = false);
  }
}
```

### B. Generate suggestion → navigate ke preview (RekapFormPage)
```dart
Future<void> _generateDanNavigate(SiapDirekapItem item) async {
  // Tutup dialog konfirmasi sudah dipanggil sebelumnya
  setState(() => _isGenerating = true);
  try {
    final res = await apiService.getSuggestionRekap(
      supplierId: item.supplierId,
      tanggal: item.tanggal,
    );
    // Suggestion sudah berisi data LENGKAP dari transaksi nyata
    Navigator.push(context, MaterialPageRoute(
      builder: (_) => RekapPreviewPage(
        supplierId: item.supplierId,
        supplierNama: item.supplierNama,
        tanggal: item.tanggal,
        details: res.suggestions,  // data siap pakai
      ),
    ));
  } catch (e) {
    showSnackbar('Gagal generate rekap: $e');
  } finally {
    setState(() => _isGenerating = false);
  }
}
```

### C. Auto-hitung ringkasan keuangan (RekapPreviewPage)
```dart
// Dipanggil setiap kali details, ongkos, atau komplain berubah
void _recalcSummary() {
  final totalKotor = _details.fold(0.0, (s, d) => s + d.subtotal);
  final totalPeti  = _details.fold(0, (s, d) => s + d.jumlahPeti);
  final komisi     = totalKotor * (_komisiPersen / 100);
  final kuli       = totalPeti * _kuliPerPeti;
  final busuk      = _komplain.fold(0.0, (s, k) => s + k.total);
  final bersih     = totalKotor - komisi - kuli - _totalOngkos;

  setState(() {
    _summary = RekapSummary(
      totalKotor: totalKotor,
      totalKomisi: komisi,
      totalKuli: kuli,
      totalBusuk: busuk,
      pendapatanBersih: bersih,
      sisa: bersih - busuk,
    );
  });
}
```

### D. Submit (RekapPreviewPage)
```dart
Future<void> _submit() async {
  final payload = {
    'supplier_id'       : widget.supplierId,
    'tanggal'           : widget.tanggal,
    'total_ongkos'      : _totalOngkos,
    'keterangan_ongkos' : _keteranganCtrl.text.trim(),
    // _kode_bd TIDAK ikut dikirim
    'details': _details.map((d) => {
      'nama_produk'        : d.namaProduk,
      'ukuran'             : d.ukuran,
      'jumlah_peti'        : d.jumlahPeti,
      'total_berat_kotor'  : d.totalBeratKotor,
      'total_berat_peti'   : d.totalBeratPeti,
      'total_berat_bersih' : d.totalBeratBersih,
      'harga_per_kg'       : d.hargaPerKg,
    }).toList(),
    if (_komplain.isNotEmpty) 'komplain': _komplain.map((k) => {
      'nama_produk'  : k.namaProduk,
      'jumlah_bs'    : k.jumlahBs,
      'harga_ganti'  : k.hargaGanti,
      'keterangan'   : k.keterangan,
    }).toList(),
  };

  // POST /rekap (create) atau PUT /rekap/{id} (edit)
  // Setelah sukses → Navigator.pushReplacement ke RekapDetailPage
}
```

### D. Dialog Finalisasi
```dart
void _showFinalisasiDialog(Rekap rekap) {
  showDialog(
    context: context,
    builder: (ctx) => AlertDialog(
      title: const Text('Finalisasi Rekap'),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text('Rekap: ${rekap.kodeRekap}'),
          Text('Supplier: ${rekap.supplier?.namaSupplier}'),
          Text('Tanggal: ${rekap.tanggal}'),
          const Divider(),
          Text(
            'Sisa (ke supplier): ${_formatCurrency(rekap.sisa)}',
            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
          ),
          const SizedBox(height: 8),
          const Text(
            '⚠️ Rekap yang sudah final tidak bisa diubah atau dihapus.',
            style: TextStyle(fontSize: 12, color: Colors.orange),
          ),
        ],
      ),
      actions: [
        TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Batal')),
        ElevatedButton(
          style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
          onPressed: () {
            Navigator.pop(ctx);
            _finalisasi(rekap.id);
          },
          child: const Text('Final'),
        ),
      ],
    ),
  );
}
```

---

## Alur Penggunaan Lengkap

```
BUAT REKAP BARU
───────────────
1. RekapListPage → tap "+ Rekap Baru" → RekapFormPage
2. Pilih supplier  (required)
3. Pilih tanggal   (optional)
4. Tap "Cari Rekap Tersedia"
   → GET /rekap/siap-direkap?supplier_id={id}&tanggal={tgl}

   Jika kosong:
   → Pesan inline: "Tidak ada rekap tersedia"

   Jika ada hasil:
   → Daftar card tanggal muncul di bawah

5. Tap card tanggal yang diinginkan
   → AlertDialog konfirmasi (supplier, tanggal, jumlah produk)
6. Tap "Generate →"
   → GET /rekap/suggestion/{supplier_id}/{tanggal}
   → Navigate ke RekapPreviewPage

REVIEW & SIMPAN (RekapPreviewPage)
───────────────────────────────────
7. Halaman terbuka: semua data sudah lengkap dari transaksi nyata
   (peti, berat kotor/tara/bersih, harga, subtotal — semua sudah terisi)
8. Review ringkasan keuangan otomatis
9. Tambah ongkos & komplain jika perlu
10. Tap "Edit ✎" di AppBar jika ada koreksi data
11. Tap "Simpan sebagai Draft"
    → POST /rekap
    → Navigate ke RekapDetailPage

FINALISASI
──────────
12. RekapDetailPage → review final
13. Tap "Finalisasi" → dialog konfirmasi
    → POST /rekap/{id}/final
14. Tap "Cetak Nota" → GET /nota/rekap/{id} → PDF

EDIT REKAP (hanya draft)
─────────────────────────
- RekapDetailPage → tap "Edit ✎"
  → RekapPreviewPage (data dari rekap existing, mode edit aktif)
  → Simpan → PUT /rekap/{id}
```

---

## Folder Structure Tambahan

```
lib/
├── app/
│   ├── data/
│   │   ├── models/
│   │   │   ├── rekap_model.dart              ← Rekap, DetailRekap, KomplainRekap, RekapSummary
│   │   │   ├── siap_direkap_model.dart       ← SiapDirekapItem (supplier_id, tanggal, total_letter)
│   │   │   ├── suggestion_rekap_model.dart   ← SuggestionRekap (data lengkap siap pakai)
│   │   │   └── detail_barang_datang_model.dart  ← update: tambah stok fields
│   │   └── repositories/
│   │       └── rekap_repository.dart
│   └── modules/
│       └── rekap/
│           ├── bindings/
│           │   └── rekap_binding.dart
│           ├── controllers/
│           │   └── rekap_controller.dart
│           └── views/
│               ├── rekap_list_page.dart
│               ├── rekap_detail_page.dart
│               ├── rekap_form_page.dart      ← Step 1: Cari + Step 2: Pilih
│               └── rekap_preview_page.dart   ← Review data lengkap + Simpan
```

### `SuggestionRekap` model (1 instance = 1 baris transaksi)
```dart
class SuggestionRekap {
  final String namaProduk;
  final String? ukuran;
  final double hargaPerKg;
  final int jumlahPeti;
  final double totalBeratKotor;
  final double totalBeratPeti;
  final double totalBeratBersih;
  final double subtotal;
  // Hanya untuk tampilan — JANGAN kirim ke API
  final String? kodeBd;
  final int? itemTransaksiId;

  SuggestionRekap.fromJson(Map<String, dynamic> j) :
    namaProduk = j['nama_produk'],
    ukuran = j['ukuran'],
    hargaPerKg = (j['harga_per_kg'] as num).toDouble(),
    jumlahPeti = j['jumlah_peti'] as int,
    totalBeratKotor = (j['total_berat_kotor'] as num).toDouble(),
    totalBeratPeti = (j['total_berat_peti'] as num).toDouble(),
    totalBeratBersih = (j['total_berat_bersih'] as num).toDouble(),
    subtotal = (j['subtotal'] as num).toDouble(),
    kodeBd = j['_kode_bd'],
    itemTransaksiId = j['_item_transaksi_id'];

  // Group key untuk tampilan tabel (grouped by produk)
  String get groupKey => '$namaProduk||${ukuran ?? ''}';

  Map<String, dynamic> toDetailRekapJson() => {
    'nama_produk'        : namaProduk,
    'ukuran'             : ukuran,
    'jumlah_peti'        : jumlahPeti,
    'total_berat_kotor'  : totalBeratKotor,
    'total_berat_peti'   : totalBeratPeti,
    'total_berat_bersih' : totalBeratBersih,
    'harga_per_kg'       : hargaPerKg,
    // subtotal tidak perlu dikirim (backend recalculate)
  };
}
```

### Grouping rows untuk tampilan tabel
```dart
// Ubah flat list rows menjadi grouped map untuk tampil per produk
Map<String, List<SuggestionRekap>> groupSuggestions(List<SuggestionRekap> rows) {
  final map = <String, List<SuggestionRekap>>{};
  for (final row in rows) {
    map.putIfAbsent(row.groupKey, () => []).add(row);
  }
  return map;
}

// Total peti per group
int totalPetiGroup(List<SuggestionRekap> rows) =>
    rows.fold(0, (s, r) => s + r.jumlahPeti);

// Total subtotal per group
double totalSubtotalGroup(List<SuggestionRekap> rows) =>
    rows.fold(0.0, (s, r) => s + r.subtotal);
```

---

## Update Model DetailBarangDatang (tambahan field FIFO)

Pada `detail_barang_datang_model.dart`, pastikan field berikut ada:

```dart
class DetailBarangDatang {
  // ... field existing ...

  // FIFO tracking (ada setelah status barang datang = confirmed)
  final double? stokAwal;     // stok_awal: jumlah saat dikonfirmasi
  final double? stokTerjual;  // stok_terjual: berapa yang sudah keluar via transaksi
  final double? stokSisa;     // stok_sisa: sisa sekarang
  final String? statusStok;   // status_stok: 'available' | 'habis'

  bool get isAvailable => statusStok == 'available';
  bool get isHabis => statusStok == 'habis';

  // Progress sold percentage
  double get pctTerjual {
    if (stokAwal == null || stokAwal == 0) return 0;
    return ((stokTerjual ?? 0) / stokAwal!) * 100;
  }
}
```

---

## Catatan Penting

1. **Suggestion = data LENGKAP dari transaksi nyata** — backend mengagregasi dari `item_transaksi` + `detail_peti`. Semua field (peti, berat kotor/tara/bersih, subtotal) sudah terisi. Tidak ada input manual wajib.
2. **Alur = Cari → Pilih → Review → Simpan** — tidak ada form kosong yang harus diisi dari nol.
3. **`cek-siap` tidak perlu dipanggil manual dari Flutter** — `siap-direkap` sudah menjamin data siap. Backend tetap validasi saat `POST /rekap`.
4. **Field `_kode_bd`** dari suggestion — hanya referensi tampilan, **JANGAN kirim ke API store/update**.
5. **Edit opsional** — di `RekapPreviewPage`, user bisa edit data jika ada koreksi (misal berat timbangan berbeda). Default: semua sudah benar dari transaksi.
6. **Ringkasan keuangan** — hitung client-side di `RekapPreviewPage` untuk real-time preview. Backend recalculate saat simpan.
7. **Rekap final = immutable** — semua tombol edit/hapus hilang, hanya tersisa tombol Cetak Nota.
8. **Nota Rekap** — `GET /nota/rekap/{id}` → render PDF dengan package `pdf` atau `printing`.
9. **Refresh** — saat rekap berhasil dibuat, refresh data BarangDatang & RekapList.
