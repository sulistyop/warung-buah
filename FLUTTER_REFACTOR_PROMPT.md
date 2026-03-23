# Flutter UI Refactor Prompt — Warung Buah "Lestari Buah"

## Konteks

Aplikasi Flutter untuk grosir buah-buahan **sudah ada dan berjalan**. Tugas ini adalah **refactor tampilan agar lebih profesional** dan **menyesuaikan alur fungsi** dengan backend terbaru (Laravel REST API). Jangan rebuild dari nol — perbaiki yang sudah ada.

---

## Masalah yang Harus Diperbaiki

1. **Tampilan kurang profesional** — perlu redesign UI yang lebih bersih, konsisten, dan terasa seperti aplikasi kasir/POS sungguhan.
2. **Alur print PDF transaksi** — saat ini belum ada atau tidak sesuai. Seharusnya: **setelah transaksi berhasil disimpan → muncul dialog opsi cetak nota → jika ya, langsung print/preview PDF.**
3. **Navigasi perlu diupdate** — menyesuaikan modul baru: Piutang, Deposit, Rekap, Pre Order, Log Aktivitas, User Management.

---

## Tech Stack (Pertahankan yang sudah ada)

- Flutter 3.x + Dart 3.x
- State management: **Riverpod**
- HTTP: **Dio** + Bearer token interceptor
- Local storage: **flutter_secure_storage** (token)
- PDF/Print: package **`pdf`** + **`printing`**
- Navigation: **go_router**
- Format Rupiah: `intl` package

---

## Design System — Yang Harus Diterapkan Konsisten

### Warna
```dart
class AppColors {
  static const primary     = Color(0xFF1B5E20); // Hijau tua
  static const primaryLight= Color(0xFF4CAF50); // Hijau terang
  static const accent      = Color(0xFFFF8F00); // Amber/kuning untuk highlight
  static const surface     = Color(0xFFF9FBF7); // Background halus kehijauan
  static const cardBg      = Color(0xFFFFFFFF);
  static const textPrimary = Color(0xFF1A1A1A);
  static const textSecond  = Color(0xFF6B7280);
  static const divider     = Color(0xFFE5E7EB);

  // Status
  static const lunas  = Color(0xFF16A34A); // hijau
  static const tempo  = Color(0xFFD97706); // kuning/amber
  static const cicil  = Color(0xFF2563EB); // biru
  static const danger = Color(0xFFDC2626); // merah
  static const draft  = Color(0xFF6B7280); // abu
}
```

### Typography
```dart
// Gunakan Google Fonts: Inter atau Poppins
// Heading: FontWeight.w700, size 18-22
// Subheading: FontWeight.w600, size 14-16
// Body: FontWeight.w400, size 13-14
// Caption: FontWeight.w400, size 11-12, color textSecond
```

### Card Style
```dart
// Semua card pakai: borderRadius 12, elevation 0, border subtle
BoxDecoration(
  color: AppColors.cardBg,
  borderRadius: BorderRadius.circular(12),
  border: Border.all(color: AppColors.divider, width: 1),
)
```

### Spacing & Padding
```dart
const kPadding    = EdgeInsets.all(16);
const kPaddingH   = EdgeInsets.symmetric(horizontal: 16);
const kCardPadding= EdgeInsets.all(14);
const kGap4       = SizedBox(height: 4);
const kGap8       = SizedBox(height: 8);
const kGap12      = SizedBox(height: 12);
const kGap16      = SizedBox(height: 16);
const kGap24      = SizedBox(height: 24);
```

---

## Komponen Global yang Harus Distandarisasi

```dart
// 1. AppButton — tombol utama dengan loading state
//    Variant: primary (hijau), secondary (outline), danger (merah)

// 2. StatusChip — chip status kecil dengan warna
//    StatusChip(label: 'Lunas', color: AppColors.lunas)
//    StatusChip(label: 'Tempo', color: AppColors.tempo)
//    StatusChip(label: 'Draft', color: AppColors.draft)
//    StatusChip(label: 'Final', color: AppColors.primaryLight)
//    StatusChip(label: 'Habis', color: AppColors.danger)
//    StatusChip(label: 'Available', color: AppColors.lunas)

// 3. RupiahText — Text Rupiah yang diformat
//    RupiahText(14510500) → "Rp 14.510.500"
//    RupiahText.large(14510500) → lebih besar, bold

// 4. SectionHeader — judul section dalam form/detail
//    SectionHeader('Detail Peti')  — dengan garis bawah tipis

// 5. InfoRow — baris label: nilai untuk detail screen
//    InfoRow(label: 'Supplier', value: 'Pak Budi')

// 6. AppSearchBar — search bar standar di bagian atas list

// 7. EmptyStateWidget — tampilan kosong dengan ikon + pesan
//    EmptyStateWidget(icon: Icons.receipt_long, message: 'Belum ada transaksi')

// 8. ConfirmBottomSheet — bottom sheet konfirmasi aksi penting
//    Judul, deskripsi, tombol Batalkan + Konfirmasi

// 9. PriceTextField — TextField khusus nominal Rupiah
//    Auto-format saat ketik, validasi angka positif

// 10. AppFilterChips — row chip filter horizontal (scrollable)
//     ['Semua', 'Lunas', 'Tempo', 'Cicil'] dengan tap handler
```

---

## Perbaikan Per Layar

---

### LOGIN SCREEN
**Perbaikan:**
- Background: gradient hijau tua ke hijau sedang (bukan putih polos)
- Logo/nama usaha di tengah atas (ambil dari settings `nama_usaha`)
- Card login dengan shadow halus, rounded corner 16
- Field email + password dengan ikon, border animated saat fokus
- Tombol Login: full-width, rounded, warna primary, dengan loading spinner
- Teks kecil versi app di bawah

---

### DASHBOARD
**Perbaikan:**
- AppBar: nama usaha di kiri, avatar/nama user di kanan, tombol logout di menu
- Header kartu selamat datang: "Selamat pagi, [nama]" + tanggal hari ini
- **4 kartu ringkasan** dalam grid 2×2:
  - Transaksi Hari Ini (biru) — jumlah + total Rp
  - Piutang Belum Lunas (merah) — total Rp + jumlah pelanggan
  - Stok Perlu Direkap (amber) — jumlah supplier+tanggal yang siap rekap
  - PO Pending (ungu) — jumlah PO menunggu
- **Shortcut menu** dalam grid ikon (bukan list):
  - Transaksi Baru, Barang Datang, Rekap, Piutang, Pre Order
- Tampilkan **5 transaksi terakhir** dengan status chip

---

### BOTTOM NAVIGATION (5 tab)
```
[Beranda] [Transaksi] [Barang Datang] [Rekap] [Lainnya]
  home      receipt      inventory     bar_chart   menu
```
- Active tab: warna primary, ikon filled
- Inactive: abu, ikon outline
- FAB (+) di tab Transaksi untuk buat transaksi baru

---

### TRANSAKSI LIST
**Perbaikan:**
- AppBar dengan search bar di bawahnya (persistent, bukan icon)
- Filter chip row: `Semua | Lunas | Tempo | Cicil`
- Setiap item transaksi tampilkan:
  - Kode transaksi (kecil, abu) + Nama pelanggan (bold)
  - Tanggal + Kasir
  - Total tagihan (kanan atas) + StatusChip (kanan bawah)
  - Jika tempo/cicil: tampilkan sisa tagihan dengan warna merah
  - Jika jatuh tempo sudah lewat: badge "⚠ Overdue" warna merah
- Pull-to-refresh
- Infinite scroll pagination

---

### TRANSAKSI FORM
**Alur (tidak berubah, perbaiki tampilan):**
```
Step 1: Pelanggan → Step 2: Item & Peti → Step 3: Biaya & Bayar → Simpan
```

**Perbaikan tampilan:**
- Gunakan `Stepper` horizontal atau progress indicator di atas untuk menunjukkan step
- **Step 1 — Pelanggan:**
  - SearchableDropdown: ketik nama → autocomplete dari `/pelanggan/all`
  - Toggle: "Pelanggan terdaftar" vs "Tamu (free text)"
  - Jika pilih pelanggan terdaftar: tampilkan kartu kecil info piutang & deposit aktif

- **Step 2 — Item & Peti:**
  - Tombol "+ Tambah Letter" → bottom sheet pilih jenis buah + supplier + harga/kg
  - Per letter: card expandable
    - Header: nama letter, jumlah peti (auto-count), subtotal
    - Isi: tabel peti (no | berat_kotor | berat_peti | berat_bersih auto)
    - Tombol "+ Tambah Peti" di bawah tabel
    - Setiap baris peti: 2 field (berat_kotor + berat_kemasan), berat_bersih tampil otomatis
  - Running total di sticky footer bawah layar

- **Step 3 — Biaya & Pembayaran:**
  - Section "Biaya Tambahan": chip/list biaya (ongkos, dll) + tombol tambah
  - Section "Summary": kartu breakdown nilai
    ```
    Total Kotor     Rp 14.510.500
    Biaya           Rp    920.000 -
    ─────────────────────────────
    Total Tagihan   Rp 13.590.500
    ```
  - Section "Status Pembayaran":
    - SegmentedButton: `Lunas | Tempo | Cicil`
    - Jika Tempo/Cicil: DatePicker jatuh tempo
    - Jika Lunas: field "Uang Diterima" → tampilkan kembalian
  - Tombol "Simpan Transaksi" di bawah

**⚡ SETELAH SIMPAN BERHASIL:**
```
1. Tampilkan SuccessBottomSheet:
   ┌─────────────────────────────┐
   │  ✅ Transaksi Berhasil!     │
   │  TRX-20260308-0001          │
   │  Mas Sidiq — Rp 14.510.500  │
   │                             │
   │  [🖨 Cetak Nota]  [Selesai] │
   └─────────────────────────────┘

2. Jika tap "Cetak Nota":
   → Panggil GET /nota/transaksi/{id}?format=json
   → Render PDF dengan package 'pdf'
   → Tampilkan preview dengan package 'printing'
   → User bisa print atau share

3. Jika tap "Selesai" atau setelah print:
   → Navigate ke TransaksiDetailScreen
```

---

### TRANSAKSI DETAIL
**Perbaikan:**
- Header: kode + status chip + tanggal
- Kartu pelanggan: nama, toko, chip piutang total
- Per letter: card dengan detail peti dalam tabel kompak
  ```
  ANGGUR A          33 peti
  ┌──────┬──────────┬──────────┬──────────┐
  │ Peti │ B.Kotor  │ B.Peti   │ B.Bersih │
  ├──────┼──────────┼──────────┼──────────┤
  │  1   │  32 kg   │   4 kg   │  28 kg   │
  │  2   │ 331 kg   │  50 kg   │ 281 kg   │
  └──────┴──────────┴──────────┴──────────┘
  913 kg × Rp 13.500 = Rp 12.325.500
  ```
- Section biaya operasional
- **Summary kartu** di bagian bawah:
  ```
  Total Kotor        Rp 14.510.500
  Total Tagihan      Rp 14.510.500
  Sudah Dibayar      Rp  0
  Sisa Tagihan       Rp 14.510.500   ← merah jika >0
  ```
- Section riwayat pembayaran (jika ada)
- **Action bar bawah:**
  - Jika belum lunas: tombol "Bayar" (buka BayarSheet)
  - Selalu ada: tombol "Cetak Nota" → langsung print PDF

---

### BARANG DATANG LIST
**Perbaikan:**
- Filter: supplier (dropdown) + tanggal + status chip
- Per item: supplier name (bold), tanggal, badge "Ke-N hari ini", status chip
- Tap → detail
- FAB: tambah barang datang baru

### BARANG DATANG DETAIL
**Perbaikan:**
- Header: kode, supplier, tanggal, status
- Per letter: card dengan info peti + stok (stok_awal | terjual | sisa)
  - Progress bar stok: hijau (sisa), merah (terjual)
  - StatusChip: "Available" (hijau) / "Habis" (merah)
- Jika masih draft: tombol "Konfirmasi Barang Datang" (menonjol, full-width)
- Jika confirmed: tampilkan waktu konfirmasi + siapa yang konfirmasi

---

### REKAP LIST
**Perbaikan:**
- Tab: `Draft | Final`
- Per item: nama supplier, tanggal, total peti, sisa Rp (besar, bold), status chip
- FAB: tombol "Cek & Buat Rekap" → buka SupplierTanggalPicker

### REKAP FORM
**Perbaikan:**
- Stepper: Detail Letter → Komplain BS → Biaya → Preview
- **Section Detail Letter:**
  - Card per letter, expandable
  - Setiap card: nama produk, jumlah peti, berat (kotor/peti/bersih input), harga/kg, subtotal
  - Subtotal auto-hitung real-time
- **Section Komplain BS:**
  - Bisa 0 atau lebih baris
  - Per baris: produk, jumlah BS, harga ganti → total auto
- **Section Biaya:**
  - Ongkos: free text input + nominal
  - Kuli: auto dari config (tampil read-only, nominal kuli_per_peti × total_peti)
- **Section Preview (mirip nota fisik):**
  ```
  ─────────────────────────
  REKAP LESTARI BUAH
  Supplier : Pak Budi
  Tanggal  : 05/02/2026
  ─────────────────────────
  A (33 peti)  913 kg × 13.500 = 12.325.500
  B ( 4 peti)  104 kg × 12.000 =  1.248.000
  C ( 3 peti)   90 kg ×  9.500 =    855.000
  ─────────────────────────
  Total Kotor          14.428.500
  Komisi  7%          - 1.010.000
  Kuli (40×2.000)     -    80.000
  Ongkos              -   760.000
  ─────────────────────────
  Pend. Bersih         12.578.500
  Busuk/BS (A×22)     -   297.000
  ═════════════════════════
  SISA                 12.281.500
  ```
- Tombol: "Simpan Draft" (outline) | "Finalisasi + Cetak" (primary)
- **Setelah finalisasi:** langsung preview PDF nota rekap

### REKAP DETAIL
- Tampilan sama dengan preview di atas (nota fisik style)
- Tombol "Cetak Ulang" → print PDF

---

### PIUTANG SCREEN
**Perbaikan:**
- Tab: `Per Transaksi | Per Pelanggan`
- **Tab Per Pelanggan:** list card per pelanggan
  - Nama pelanggan, toko, jumlah transaksi open
  - Total piutang (merah, bold)
  - Tap → expand atau ke detail pelanggan
  - Tombol "Bayar" per pelanggan → BayarPiutangSheet
- **Tab Per Transaksi:** list transaksi belum lunas
  - Badge overdue jika melewati jatuh tempo

**BayarPiutangSheet (bottom sheet):**
```
┌────────────────────────────────┐
│  Bayar Piutang — Mas Sidiq     │
│                                │
│  Piutang yang akan dibayar:    │
│  □ TRX-0001  Rp 14.510.500    │
│  □ TRX-0005  Rp  3.000.000    │
│  □ TRX-0006  Rp  4.100.000    │
│                                │
│  Total dipilih: Rp 21.610.500  │
│                                │
│  Jumlah Bayar: [____________]  │
│  Metode: [Tunai ▼]             │
│                                │
│  Preview alokasi:              │
│  TRX-0001: dibayar 14.510.500  │
│  TRX-0005: dibayar  500.000    │
│  (sisa uang: Rp 0)             │
│                                │
│  [Batalkan]    [Bayar Sekarang]│
└────────────────────────────────┘
```
- Input jumlah → realtime preview alokasi FIFO
- Metode: Tunai / Transfer / QRIS / Deposit

---

### PELANGGAN SCREEN

**PelangganListScreen:**
- Kartu per pelanggan: nama + toko, nomor HP, chip piutang (merah jika >0) + chip deposit (hijau jika >0)
- FAB: tambah pelanggan baru

**PelangganDetailScreen:**
- Avatar inisial berwarna (dari nama pelanggan)
- Info: nama, toko, HP, alamat
- 2 kartu besar:
  ```
  [  Piutang: Rp 21.610.500  ]  [  Deposit: Rp 15.000.000  ]
  ```
- Tombol aksi: "Bayar Piutang" | "Tambah Deposit"
- Tab: Riwayat Transaksi | Riwayat Deposit

---

### DEPOSIT SCREEN
- Embedded di PelangganDetailScreen (bukan screen terpisah)
- **Tambah Deposit:** bottom sheet
  - Nominal (PriceTextField)
  - Metode: Tunai / Transfer / QRIS
  - Referensi (opsional)
  - Catatan (opsional)
- List deposit aktif: tanggal, nominal, sisa, metode, chip "Aktif"

---

### PRE ORDER SCREEN

**PreOrderListScreen:**
- Filter chip: `Semua | Pending | Diproses | Selesai | Batal`
- Per item: kode PO, pelanggan, tanggal kirim, total, status chip

**PreOrderFormScreen:**
- Field pelanggan (autocomplete / free text)
- Tanggal PO + estimasi kirim (DatePicker)
- Tambah item: pilih stok dari barang datang yang masih available
  - Tampil: nama produk, sisa stok, harga jual
  - Input: jumlah peti + estimasi berat bersih → subtotal auto
- Catatan
- Total estimasi di footer

---

### LOG AKTIVITAS (admin only)

- Timeline vertikal
- Per item: ikon modul (berbeda per modul), deskripsi aksi, nama user, waktu relatif ("2 jam lalu")
- Warna ikon per modul: transaksi=biru, rekap=hijau, user=abu, deposit=amber, dll
- Filter tanggal di AppBar

---

### USER MANAGEMENT (admin only)

- List user: avatar inisial, nama, email, badge role, toggle aktif
- Form tambah/edit: nama, email, password, pilih role (radio button visual)
- Delete = nonaktifkan (bukan hapus permanent) dengan konfirmasi

---

### SETTINGS

- Dikelompokkan dalam section card:
  - **Usaha**: nama, alamat, telepon
  - **Operasional**: kuli_per_peti
  - **Printer**: IP, port, lebar kertas
- Setiap field bisa diedit inline (tap → aktif, save otomatis)
- Tombol "Test Print" untuk printer

---

## Alur Navigasi (Updated)

```
SplashScreen → cek token
    ├── token ada → HomeScreen
    └── token tidak ada → LoginScreen

LoginScreen → HomeScreen

HomeScreen (BottomNav 5 tab):
│
├── [Tab 1] DashboardScreen
│     ├── tap kartu piutang → PiutangScreen
│     ├── tap kartu stok → BarangDatangListScreen
│     └── tap shortcut → masing-masing screen
│
├── [Tab 2] TransaksiListScreen
│     ├── FAB (+) → TransaksiFormScreen
│     │     └── onSukses → SuccessBottomSheet
│     │           ├── Cetak Nota → PdfPreviewScreen → kembali ke detail
│     │           └── Selesai → TransaksiDetailScreen
│     └── tap item → TransaksiDetailScreen
│           ├── tombol Bayar → BayarSheet
│           └── tombol Cetak → PdfPreviewScreen
│
├── [Tab 3] BarangDatangListScreen
│     ├── FAB (+) → BarangDatangFormScreen
│     └── tap item → BarangDatangDetailScreen
│           └── tombol Konfirmasi → dialog konfirmasi → refresh
│
├── [Tab 4] RekapListScreen
│     ├── FAB "Buat Rekap" → SupplierTanggalPicker
│     │     └── cek siap → RekapFormScreen
│     │           └── finalisasi → PdfPreviewScreen (nota rekap)
│     └── tap item → RekapDetailScreen
│           └── tombol Cetak → PdfPreviewScreen
│
└── [Tab 5] LainnyaScreen (grid menu)
      ├── Pelanggan → PelangganListScreen
      │     ├── FAB (+) → PelangganFormScreen
      │     └── tap → PelangganDetailScreen
      │           ├── tombol Bayar Piutang → BayarPiutangSheet
      │           └── tombol Tambah Deposit → DepositSheet
      ├── Piutang → PiutangScreen
      │     └── tap pelanggan → BayarPiutangSheet
      ├── Pre Order → PreOrderListScreen
      │     ├── FAB (+) → PreOrderFormScreen
      │     └── tap → PreOrderDetailScreen
      ├── [admin] Pengguna → UserListScreen
      │     └── FAB (+) / tap → UserFormScreen
      ├── [admin] Log Aktivitas → LogAktivitasScreen
      └── Pengaturan → SettingsScreen
```

---

## Alur Print PDF (Detail Implementasi)

```dart
// lib/features/nota/nota_service.dart

class NotaService {
  final Dio _dio;

  // Ambil data nota dari API
  Future<Map<String, dynamic>> fetchNotaTransaksi(int id) async {
    final res = await _dio.get('/nota/transaksi/$id');
    return res.data['data'];
  }

  Future<Map<String, dynamic>> fetchNotaRekap(int id) async {
    final res = await _dio.get('/nota/rekap/$id');
    return res.data['data'];
  }

  // Render PDF transaksi (struk kasir 80mm)
  Future<Uint8List> buildPdfTransaksi(Map<String, dynamic> data) async {
    final pdf = pw.Document();
    // gunakan pw.Page dengan pageFormat: PdfPageFormat(80 * PdfPageFormat.mm, double.infinity)
    // layout mirip struk: nama usaha, pelanggan, detail peti per letter, total
    // ...
    return pdf.save();
  }

  // Render PDF rekap (A5 portrait)
  Future<Uint8List> buildPdfRekap(Map<String, dynamic> data) async {
    final pdf = pw.Document();
    // layout tabel: detail per letter, komplain, summary kalkulasi
    // ...
    return pdf.save();
  }

  // Tampilkan preview & opsi print/share
  Future<void> previewAndPrint(Uint8List pdfBytes, String filename) async {
    await Printing.layoutPdf(
      onLayout: (_) async => pdfBytes,
      name: filename,
    );
  }
}
```

**Trigger print setelah transaksi berhasil:**
```dart
// Di TransaksiFormScreen, setelah POST /transaksi sukses:

void _onTransaksiSuccess(Transaksi transaksi) {
  showModalBottomSheet(
    context: context,
    builder: (_) => SuccessBottomSheet(
      kodeTrx: transaksi.kodeTranksi,
      namaPelanggan: transaksi.namaPelanggan,
      totalTagihan: transaksi.totalTagihan,
      onCetakNota: () async {
        Navigator.pop(context);
        final data = await notaService.fetchNotaTransaksi(transaksi.id);
        final pdfBytes = await notaService.buildPdfTransaksi(data);
        await notaService.previewAndPrint(pdfBytes, 'nota-${transaksi.kodeTransaksi}.pdf');
        // setelah print → navigate ke detail
        context.go('/transaksi/${transaksi.id}');
      },
      onSelesai: () {
        Navigator.pop(context);
        context.go('/transaksi/${transaksi.id}');
      },
    ),
  );
}
```

---

## Konvensi Kode

```dart
// Penamaan file: snake_case
// Penamaan class: PascalCase
// Penamaan provider: camelCase + Provider suffix
//   contoh: transaksiListProvider, rekapDetailProvider

// Provider pattern:
final transaksiListProvider = AsyncNotifierProvider.autoDispose<
  TransaksiListNotifier, List<Transaksi>
>(() => TransaksiListNotifier());

// API response wrapper:
class ApiResponse<T> {
  final bool success;
  final String message;
  final T? data;
  // ...
}

// Error handling: semua API call di-wrap try/catch,
// error ditampilkan via SnackBar atau ErrorWidget
```

---

## Catatan Penting

1. **Jangan ganti fungsi yang sudah jalan** — hanya perbaiki tampilan (style, layout, spacing, warna).

2. **Print PDF hanya dipanggil dari 2 tempat:**
   - Setelah transaksi berhasil disimpan (via SuccessBottomSheet)
   - Dari tombol "Cetak" di TransaksiDetailScreen dan RekapDetailScreen

3. **Endpoint nota mengembalikan JSON** — render PDF di sisi Flutter menggunakan package `pdf`, bukan WebView.

4. **Rekap hanya bisa dibuat jika semua stok habis** — selalu cek dulu via `GET /rekap/cek-siap/{supplier_id}/{tanggal}`.

5. **Bayar piutang FIFO** — preview alokasi real-time saat user mengetik nominal di BayarPiutangSheet.

6. **Role visibility:**
   - `admin`: semua menu
   - `kasir`: Transaksi, Barang Datang, Rekap, Pelanggan, Piutang, Deposit, PO, Settings
   - `operator`: Barang Datang saja

7. **Format angka** selalu Rupiah tanpa desimal: `Rp 14.510.500` (bukan `Rp 14,510,500.00`).

8. **Nota struk** lebar 80mm, font monospace atau kecil, cocok untuk thermal printer.
   **Nota rekap** ukuran A5, layout tabel, bisa di-share via WhatsApp/email.
