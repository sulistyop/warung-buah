# Flutter Update Prompt — Fitur Barang Datang (Revisi: Input Letter Baru via Repeater)

## Konteks & Perubahan Utama

- **"Letter"** = Produk. Setiap kali supplier datang, letter-nya adalah **produk baru** yang diinput langsung saat itu.
- **Tidak ada lagi pencarian dari master produk.** Semua letter diinput via repeater (form baris per baris).
- Setelah dikonfirmasi, sistem otomatis membuat entry produk di master (atau increment stok jika nama+ukuran sudah ada dari supplier yang sama).
- Backend: Laravel + Bearer token auth.

---

## Data Model Dart

### `BarangDatang`
```dart
class BarangDatang {
  final int id;
  final String kodeBd;          // "BD-20260307-0001"
  final int supplierId;
  final DateTime tanggal;
  final int urutanHari;         // kiriman ke-N dari supplier ini hari ini
  final String? catatan;
  final String status;          // 'draft' | 'confirmed'
  final DateTime? dikonfirmasiAt;
  final int? dikonfirmasiOleh;
  final Supplier? supplier;
  final List<DetailBarangDatang> details;
  final int? detailsCount;
  final User? dikonfirmasiOlehUser;

  bool get isDraft => status == 'draft';
  bool get isConfirmed => status == 'confirmed';
}
```

### `DetailBarangDatang`  *(field berubah dari versi lama)*
```dart
class DetailBarangDatang {
  final int id;
  final int barangDatangId;
  final int? produkId;         // null saat draft, terisi setelah confirm
  final String namaProduk;     // diinput user
  final String? ukuran;        // Grade A / B / C, atau null
  final int? kategoriId;
  final String satuan;         // kg, pcs, box, dll
  final double hargaBeli;
  final double hargaJual;
  final double jumlah;
  final String? keterangan;
  final Kategori? kategori;
  final Produk? produk;        // null saat draft
}
```

### `LetterTerpakaiItem`
```dart
class LetterTerpakaiItem {
  final String namaProduk;
  final String? ukuran;
  final String key; // lowercase: "apel fuji|a" — untuk pencocokan cepat
}

class LetterTerpakaiResponse {
  final List<LetterTerpakaiItem> terpakai;
  final int jumlahKiriman;
}
```

### `DetailBarangDatangInput` *(untuk form / request)*
```dart
class DetailBarangDatangInput {
  String namaProduk;
  String? ukuran;
  int? kategoriId;
  String satuan;
  double hargaBeli;
  double hargaJual;
  double jumlah;
  String? keterangan;

  // Helper untuk pencocokan uniqueness
  String get key => '${namaProduk.toLowerCase().trim()}|${(ukuran ?? '').toLowerCase().trim()}';
}
```

---

## API Endpoints

**Auth:** `Authorization: Bearer {token}`

---

### 1. List Barang Datang
```
GET /api/barang-datang
Query: page, per_page, supplier_id, tanggal_dari, tanggal_sampai, status

Response 200:
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [ BarangDatang... ],
    "total": 10,
    "last_page": 1,
    "per_page": 20
  }
}
```

---

### 2. Detail Barang Datang
```
GET /api/barang-datang/{id}

Response 200: { "success": true, "data": BarangDatang }
Response 404: { "success": false, "message": "..." }
```

---

### 3. Buat Barang Datang  *(FIELD BERUBAH)*
```
POST /api/barang-datang
Content-Type: application/json

{
  "supplier_id": 1,
  "tanggal": "2026-03-07",
  "catatan": "Kiriman pagi",         // optional
  "details": [
    {
      "nama_produk": "Apel Fuji",    // required
      "ukuran": "A",                  // optional (Grade A, B, C, dsb)
      "kategori_id": 1,               // optional
      "satuan": "kg",                 // required
      "harga_beli": 15000,            // required
      "harga_jual": 20000,            // optional (default 0)
      "jumlah": 100,                   // required, min 0.01
      "keterangan": null              // optional
    },
    {
      "nama_produk": "Apel Fuji",
      "ukuran": "B",
      "satuan": "kg",
      "harga_beli": 12000,
      "jumlah": 80
    }
  ]
}

Response 201: { "success": true, "message": "Barang datang berhasil dicatat", "data": BarangDatang }

Error 422 (duplikat dalam kiriman):
{ "success": false, "message": "Terdapat letter duplikat (nama + ukuran sama) dalam satu kiriman" }

Error 422 (konflik harian):
{ "success": false, "message": "Letter \"Apel Fuji (A)\" sudah ada dalam kiriman hari ini dari supplier yang sama" }
```

---

### 4. Update Barang Datang (hanya draft)
```
PUT /api/barang-datang/{id}

Body sama seperti store, tapi semua field optional.
Jika 'details' dikirim → seluruh detail DIGANTI.

Response 200: { "success": true, "message": "...", "data": BarangDatang }
Response 403: sudah dikonfirmasi
Response 404: tidak ditemukan
```

---

### 5. Hapus Barang Datang (hanya draft)
```
DELETE /api/barang-datang/{id}

Response 200: { "success": true, "message": "Barang datang berhasil dihapus" }
Response 403: sudah dikonfirmasi
```

---

### 6. Konfirmasi → Produk & Stok Otomatis
```
POST /api/barang-datang/{id}/confirm

Response 200:
{
  "success": true,
  "message": "Barang datang dikonfirmasi. Produk dan stok telah diperbarui.",
  "data": BarangDatang   // status: 'confirmed', details[].produk terisi
}

Logika backend:
- Tiap letter: cek apakah produk (supplier + nama + ukuran) sudah ada di master
  → Ada: increment stok, update harga
  → Belum: buat produk baru, stok = jumlah
- detail.produk_id diisi
```

---

### 7. Cek Letter Terpakai  *(response berubah)*
```
GET /api/barang-datang/letter-terpakai?supplier_id=1&tanggal=2026-03-07
GET /api/barang-datang/letter-terpakai?supplier_id=1&tanggal=2026-03-07&exclude_bd_id=3

Response 200:
{
  "success": true,
  "data": {
    "terpakai": [
      {
        "nama_produk": "Apel Fuji",
        "ukuran": "A",
        "key": "apel fuji|a"     // gunakan ini untuk pencocokan di frontend
      }
    ],
    "jumlah_kiriman": 1
  }
}
```

---

## Halaman UI yang Perlu Dibuat / Diupdate

---

### `BarangDatangListPage`

**Layout:**
```
AppBar: "Barang Datang"    [+ FAB]
──────────────────────────────────
Filter row:
  [Semua] [Draft] [Confirmed]
  [Date Range]  [Supplier ▾]
──────────────────────────────────
Card list:
┌──────────────────────────────┐
│ BD-20260307-0001  [DRAFT●]   │
│ PT Buah Segar                │
│ 07 Mar 2026 · Kiriman ke-1   │
│ 3 letter                     │
│              [Konfirmasi ▶]  │
└──────────────────────────────┘
```

**Detail tiap card:**
- Kode BD + badge status (chip kuning = draft, chip hijau = confirmed)
- Nama supplier
- Tanggal + "Kiriman ke-N"
- Jumlah letter
- Tombol "Konfirmasi" (hanya tampil jika draft, tidak perlu masuk detail)

---

### `BarangDatangDetailPage`

**Layout:**
```
AppBar: "BD-20260307-0001"   [Edit ✎]
──────────────────────────────────────
Section: Info Kiriman
  Supplier  : PT Buah Segar
  Tanggal   : 07 Maret 2026
  Kiriman   : Ke-1
  Status    : ● DRAFT / ● CONFIRMED
  Catatan   : —
──────────────────────────────────────
Section: Daftar Letter (3)
  ┌─────────────────────────────────┐
  │ Apel Fuji - Grade A             │
  │ Jumlah: 100 kg  ·  Rp 15.000   │
  │ Harga Jual: Rp 20.000          │
  └─────────────────────────────────┘
  ┌─────────────────────────────────┐
  │ Apel Fuji - Grade B             │
  │ ...                             │
  └─────────────────────────────────┘
──────────────────────────────────────
[Hapus]              [Konfirmasi ▶]   ← hanya jika draft
```

**Jika confirmed:**
- Tombol Edit & Hapus hilang
- Tampilkan: "Dikonfirmasi oleh {nama} · {waktu}"
- Tiap letter tampilkan "Kode Produk: PRD-XXXX" (dari detail.produk)

---

### `BarangDatangFormPage` (Create & Edit)

**Layout keseluruhan:**
```
AppBar: "Barang Datang Baru" / "Edit Barang Datang"
──────────────────────────────────────────────────
[Section: Informasi Kiriman]

  Supplier *
  ┌─────────────────────────────────┐
  │ 🔍 Cari supplier...              │
  └─────────────────────────────────┘

  Tanggal *
  ┌─────────────────────────────────┐
  │ 📅 07 Maret 2026                │
  └─────────────────────────────────┘

  Info chip (muncul setelah supplier + tanggal dipilih):
  ℹ️  Supplier ini sudah 1x kirim hari ini

  Catatan
  ┌─────────────────────────────────┐
  │ (optional)                      │
  └─────────────────────────────────┘

──────────────────────────────────────────────────
[Section: Daftar Letter]  — repeater

  ┌─────────────────────────────────────────────┐
  │  Letter #1                          [✕ Hapus]│
  │  Nama Produk *  ┌──────────────────────────┐│
  │                  │ Apel Fuji               ││
  │                  └──────────────────────────┘│
  │  Ukuran         ┌──────────────────────────┐│
  │                  │ A (Grade A)             ││
  │                  └──────────────────────────┘│
  │  Kategori       ┌──────────────────────────┐│
  │                  │ Buah Import ▾           ││
  │                  └──────────────────────────┘│
  │  Satuan *       ┌──────────────────────────┐│
  │                  │ kg                      ││
  │                  └──────────────────────────┘│
  │  Harga Beli *   ┌──────────────────────────┐│
  │                  │ Rp 15.000               ││
  │                  └──────────────────────────┘│
  │  Harga Jual     ┌──────────────────────────┐│
  │                  │ Rp 20.000               ││
  │                  └──────────────────────────┘│
  │  Jumlah *       ┌──────────────────────────┐│
  │                  │ 100 kg                  ││
  │                  └──────────────────────────┘│
  │  ⚠️  [error jika letter sudah terpakai]      │
  └─────────────────────────────────────────────┘

  ┌─────────────────────────────────────────────┐
  │  Letter #2                          [✕ Hapus]│
  │  ...                                         │
  └─────────────────────────────────────────────┘

  [+ Tambah Letter]

──────────────────────────────────────────────────
                              [Simpan sebagai Draft]
```

---

## Logika UI yang Penting

### A. Repeater Letter

```dart
// State
List<DetailBarangDatangInput> _letters = [];
Set<String> _lettersTerpakai = {}; // Set of 'key' strings dari API

void _tambahLetter() {
  setState(() {
    _letters.add(DetailBarangDatangInput(
      satuan: 'kg',
      hargaBeli: 0,
      hargaJual: 0,
      jumlah: 0,
    ));
  });
}

void _hapusLetter(int index) {
  setState(() => _letters.removeAt(index));
}
```

### B. Validasi Duplikat Lokal (realtime dalam form)

```dart
bool _isLetterDuplicateInForm(int currentIndex) {
  final current = _letters[currentIndex];
  if (current.namaProduk.isEmpty) return false;
  for (int i = 0; i < _letters.length; i++) {
    if (i == currentIndex) continue;
    if (_letters[i].key == current.key) return true;
  }
  return false;
}

bool _isLetterTerpakaiHariIni(int index) {
  return _lettersTerpakai.contains(_letters[index].key);
}
```

Tampilkan warning di bawah field tiap letter:
- `⚠️ Letter ini sudah ada dalam kiriman sebelumnya hari ini` (jika terpakai dari API)
- `⚠️ Letter ini duplikat dalam kiriman ini` (jika duplikat dalam form)

### C. Fetch `letter-terpakai` ketika supplier / tanggal berubah

```dart
Future<void> _fetchLetterTerpakai() async {
  if (_selectedSupplierId == null || _selectedTanggal == null) return;
  final res = await apiService.getLetterTerpakai(
    supplierId: _selectedSupplierId!,
    tanggal: _selectedTanggal!,
    excludeBdId: _editingBdId, // null jika create
  );
  setState(() {
    _lettersTerpakai = res.terpakai.map((e) => e.key).toSet();
    _jumlahKirimanHariIni = res.jumlahKiriman;
  });
}
```

### D. Tombol "Simpan"

```dart
Future<void> _submit() async {
  // 1. Validasi form dasar
  if (!_formKey.currentState!.validate()) return;

  // 2. Pastikan ada minimal 1 letter
  if (_letters.isEmpty) {
    showSnackbar('Tambahkan minimal 1 letter');
    return;
  }

  // 3. Cek ada letter yang bermasalah
  bool adaError = false;
  for (int i = 0; i < _letters.length; i++) {
    if (_isLetterDuplicateInForm(i) || _isLetterTerpakaiHariIni(i)) {
      adaError = true;
      break;
    }
  }
  if (adaError) {
    showSnackbar('Ada letter yang duplikat atau sudah terpakai hari ini');
    return;
  }

  // 4. Submit ke API
  final payload = {
    'supplier_id': _selectedSupplierId,
    'tanggal': _selectedTanggal,
    'catatan': _catatanController.text,
    'details': _letters.map((l) => l.toJson()).toList(),
  };
  // POST /barang-datang atau PUT /barang-datang/{id}
}
```

### E. Dialog Konfirmasi

```dart
void _showKonfirmasiDialog(BarangDatang bd) {
  showDialog(
    context: context,
    builder: (ctx) => AlertDialog(
      title: const Text('Konfirmasi Kiriman'),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Kiriman: ${bd.kodeBd}'),
          Text('Supplier: ${bd.supplier?.namaSupplier}'),
          const SizedBox(height: 8),
          Text('${bd.detailsCount} letter akan diproses:'),
          const SizedBox(height: 4),
          ...bd.details.take(3).map((d) => Text(
            '• ${d.namaProduk}${d.ukuran != null ? " (${d.ukuran})" : ""} — ${d.jumlah} ${d.satuan}',
            style: const TextStyle(fontSize: 13),
          )),
          if ((bd.detailsCount ?? 0) > 3)
            Text('... dan ${(bd.detailsCount ?? 0) - 3} lainnya'),
          const Divider(),
          const Text(
            'Setiap letter akan dibuatkan produk baru atau menambah stok produk yang sudah ada.',
            style: TextStyle(fontSize: 12, color: Colors.grey),
          ),
        ],
      ),
      actions: [
        TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Batal')),
        ElevatedButton(
          onPressed: () {
            Navigator.pop(ctx);
            _konfirmasi(bd.id);
          },
          child: const Text('Konfirmasi'),
        ),
      ],
    ),
  );
}
```

---

## Alur Penggunaan (Updated)

```
1. BarangDatangListPage → FAB "+"
2. Pilih Supplier (dropdown/search dari GET /supplier?status=aktif)
3. Pilih Tanggal (default: hari ini)
   → Otomatis fetch letter-terpakai untuk supplier+tanggal tsb
   → Tampilkan info chip: "Supplier ini sudah X kali kirim hari ini"
4. Tambah letter satu per satu via "+ Tambah Letter":
   - Isi: nama produk, ukuran (opsional), kategori (opsional), satuan, harga beli, harga jual, jumlah
   - Realtime warning jika nama+ukuran sudah ada di form lain atau sudah terpakai hari ini
5. Tap "Simpan sebagai Draft"
   → POST /barang-datang → status: draft
6. Di detail page → Tap "Konfirmasi" → dialog konfirmasi
   → POST /barang-datang/{id}/confirm
   → Stok & produk otomatis dibuat/diupdate
7. Detail page menampilkan status CONFIRMED + kode produk tiap letter
```

---

## Catatan Penting

1. **UX tip:** Untuk field `ukuran`, sediakan preset chips yang bisa dipilih cepat: `A`, `B`, `C`, `Super`, atau ketik manual.
2. **UX tip:** Untuk field `satuan`, sediakan preset: `kg`, `pcs`, `box`, `ikat`, atau ketik manual.
3. Saat konfirmasi berhasil, **refresh list produk** jika page produk ada di navigation stack (stok sudah berubah).
4. Saat error 422 dari API (letter konflik), tampilkan `SnackBar` dengan pesan dari `response.message`.
5. Mode **edit** hanya tersedia saat status `draft`. Tombol edit di detail page langsung navigasi ke `BarangDatangFormPage` dengan data yang sudah terisi.
6. Saat edit, kirim `exclude_bd_id` ke endpoint `letter-terpakai` agar letter dari kiriman ini tidak dianggap konflik dengan dirinya sendiri.
