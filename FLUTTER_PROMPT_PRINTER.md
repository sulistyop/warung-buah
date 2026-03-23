# Flutter Prompt — Fitur Print Bluetooth + Pilih Template Nota

Gunakan prompt ini ke AI (Claude/GPT) saat membangun fitur print di Flutter.

---

## Konteks Project

- Aplikasi POS Flutter (warung buah) yang terhubung ke Laravel REST API
- Backend sudah menyediakan endpoint `/api/settings/printer` untuk GET dan PUT setting printer
- Printer menggunakan **Bluetooth thermal printer** (58mm / 80mm)
- Ada 3 template nota: `simple`, `detail`, `merchant`

---

## Prompt

```
Saya membangun aplikasi POS Flutter yang terhubung ke Laravel REST API.
Tambahkan fitur cetak nota via Bluetooth thermal printer dengan ketentuan berikut:

═══════════════════════════════════════════════════════
BACKEND API (sudah tersedia, jangan diubah)
═══════════════════════════════════════════════════════

Base URL: {BASE_URL}/api  (gunakan konstanta dari config yang sudah ada)
Authorization: Bearer token (sudah ada di ApiService / dio interceptor)

GET  /settings/printer
→ Response: { success, data: { printer_address, printer_nama, printer_lebar_kertas, printer_template, printer_footer, printer_auto_print, printer_copies } }

PUT  /settings/printer
→ Body (semua optional / partial update):
  {
    "printer_address": "00:11:22:33:44:55",   // MAC address, null = hapus
    "printer_nama": "RPP02N",
    "printer_lebar_kertas": 58,               // 58 atau 80
    "printer_template": "simple",             // simple | detail | merchant
    "printer_footer": "Terima kasih!",
    "printer_auto_print": true,
    "printer_copies": 1
  }

GET /nota/transaksi/{id}
→ Response: { success, data: { nota_text: "...", items: [...], total: ... } }
  Gunakan field `nota_text` untuk di-print raw ke printer thermal.

═══════════════════════════════════════════════════════
PACKAGES YANG DIGUNAKAN
═══════════════════════════════════════════════════════

Tambahkan ke pubspec.yaml:
  - blue_thermal_printer: ^1.1.1       # print ke bluetooth thermal printer
  - flutter_bluetooth_serial: ^0.4.0   # scan & pair device bluetooth
  - permission_handler: ^11.0.0        # request izin bluetooth & lokasi

═══════════════════════════════════════════════════════
FITUR YANG HARUS DIBUAT
═══════════════════════════════════════════════════════

1. MODEL: PrinterSetting
   - Field sesuai response GET /settings/printer
   - fromJson() dan toJson()
   - copyWith()

2. SERVICE: PrinterService (singleton / provider)
   - loadFromApi() → GET /settings/printer, simpan ke SharedPreferences sebagai cache lokal
   - saveToApi(PrinterSetting) → PUT /settings/printer
   - scanDevices() → return List<BluetoothDevice> dari flutter_bluetooth_serial
   - connect(String address) → konek ke printer, simpan koneksi aktif
   - disconnect()
   - printNota(String notaText) → kirim raw bytes ke printer yang terkoneksi
   - isConnected → getter bool
   - Gunakan try-catch, lempar exception yang bermakna

3. SCREEN: PrinterSettingScreen (/settings/printer)
   Tampilan:
   a) SECTION "Printer Bluetooth"
      - Tombol [Scan Printer] → tampilkan dialog/bottom sheet list BluetoothDevice
      - Card printer terpilih: tampilkan printer_nama + printer_address + status connected/disconnected
      - Tombol [Connect] / [Disconnect]
      - Tombol [Test Print] → cetak teks "TEST PRINT\nWarung Buah\n{tanggal}" ke printer

   b) SECTION "Kertas"
      - SegmentedButton atau RadioListTile pilih lebar kertas: 58mm / 80mm

   c) SECTION "Template Nota"
      - 3 pilihan dengan preview visual kecil (Card):
        * Simple   → hanya nama item, jumlah, total, footer
        * Detail   → nama item, harga satuan, jumlah, subtotal, diskon, total, footer
        * Merchant → dua salinan (customer + merchant copy), lengkap dengan tanda tangan
      - Highlight card yang dipilih dengan warna primary

   d) SECTION "Lainnya"
      - Switch: Auto Print setelah transaksi selesai
      - Slider atau DropdownButton: Jumlah salinan (1–5)
      - TextField: Teks footer nota (max 200 karakter)

   e) Tombol [Simpan Pengaturan] di bawah (sticky bottom bar)
      → panggil saveToApi(), tampilkan SnackBar sukses/error

4. INTEGRASI DI HALAMAN TRANSAKSI SELESAI (PaymentSuccessScreen / TransaksiDetailScreen)
   - Setelah pembayaran sukses, cek printer_auto_print dari PrinterSetting
   - Jika true DAN printer sudah connect → langsung panggil printNota(transaksi_id)
   - Tampilkan tombol [Print Nota] selalu (untuk print manual)
   - Tombol print: ambil dari GET /nota/transaksi/{id}, lalu print field nota_text

5. PERMISSION HANDLING
   - Di main.dart atau saat PrinterSettingScreen dibuka pertama kali:
     request BLUETOOTH_SCAN, BLUETOOTH_CONNECT, ACCESS_FINE_LOCATION
   - Tampilkan dialog penjelasan jika user menolak izin

6. STATE MANAGEMENT
   - Gunakan Provider atau Riverpod (sesuai yang sudah dipakai di project)
   - PrinterNotifier/PrinterProvider expose: printerSetting, isConnected, isLoading, error
   - Saat app dibuka, auto-load setting dari SharedPreferences dulu (offline-first),
     lalu fetch ulang dari API di background

═══════════════════════════════════════════════════════
CONTOH FORMAT NOTA (raw text untuk thermal printer)
═══════════════════════════════════════════════════════

Template SIMPLE (58mm, ~32 char per baris):
```
================================
      WARUNG BUAH SEGAR
  Jl. Pasar Buah No. 123
================================
No: TRX-20240310-001
Tgl: 10 Mar 2024  10:30

Apel Fuji          2kg
  Rp12.000 x 2  = Rp24.000
Mangga Harum       1kg
  Rp15.000 x 1  = Rp15.000
--------------------------------
Subtotal         Rp39.000
Diskon               Rp0
TOTAL            Rp39.000
--------------------------------
Bayar (Tunai)    Rp50.000
Kembalian        Rp11.000
================================
  Terima kasih sudah berbelanja!
================================


```

Template DETAIL (80mm, ~48 char per baris):
```
================================================
           WARUNG BUAH SEGAR
      Jl. Pasar Buah No. 123 | Telp: -
================================================
No Nota : TRX-20240310-001
Tanggal : 10 Maret 2024 pukul 10:30
Kasir   : Admin
Pelanggan: Umum
------------------------------------------------
Nama Produk       Qty    Harga      Subtotal
------------------------------------------------
Apel Fuji          2kg  Rp12.000   Rp24.000
Mangga Harum       1kg  Rp15.000   Rp15.000
------------------------------------------------
                         Subtotal  Rp39.000
                            Diskon     Rp0
                             TOTAL Rp39.000
------------------------------------------------
Metode Bayar : Tunai
Jumlah Bayar : Rp50.000
Kembalian    : Rp11.000
================================================
       Terima kasih sudah berbelanja!
================================================


```

Template MERCHANT: sama seperti DETAIL tapi dicetak 2x (copies=2 otomatis).
Cetakan ke-2 ada teks "-- SALINAN MERCHANT --" di header.

═══════════════════════════════════════════════════════
CATATAN PENTING
═══════════════════════════════════════════════════════

- Untuk Android 12+: wajib BLUETOOTH_SCAN + BLUETOOTH_CONNECT (bukan BLUETOOTH lama)
- Tambahkan di AndroidManifest.xml:
    <uses-permission android:name="android.permission.BLUETOOTH" android:maxSdkVersion="30"/>
    <uses-permission android:name="android.permission.BLUETOOTH_ADMIN" android:maxSdkVersion="30"/>
    <uses-permission android:name="android.permission.BLUETOOTH_SCAN" android:usesPermissionFlags="neverForLocation" tools:targetApi="s"/>
    <uses-permission android:name="android.permission.BLUETOOTH_CONNECT"/>
    <uses-permission android:name="android.permission.ACCESS_FINE_LOCATION"/>
- blue_thermal_printer menggunakan PrinterBluetoothManager untuk scan
- Gunakan BlueThermalPrinter.instance untuk print
- Metode print: printCustom(text, size, align) atau printNewLine()
- Untuk cetak raw ESC/POS bytes gunakan writeBytes(Uint8List)
- Setting printer disimpan di server (API) sebagai source of truth.
  Cache lokal di SharedPreferences hanya untuk performa (load cepat saat offline).
- Saat user ganti template atau lebar kertas, tampilkan preview teks langsung berubah
  di bawah pilihan template (bukan preview gambar, cukup teks monospace di Container abu-abu).
```

---

## Endpoint Quick Reference

| Method | Endpoint | Auth | Keterangan |
|--------|----------|------|------------|
| GET | /settings/printer | Bearer | Ambil setting printer |
| PUT | /settings/printer | Bearer | Simpan setting printer (partial update) |
| GET | /nota/transaksi/{id} | Bearer | Ambil teks nota siap print |
| GET | /nota/rekap/{id} | Bearer | Ambil teks nota rekap supplier |

## Field printer_template

| Value | Nama Tampil | Keterangan |
|-------|-------------|------------|
| `simple` | Simple | Ringkas, cocok 58mm |
| `detail` | Detail | Lengkap dengan harga satuan, cocok 80mm |
| `merchant` | Merchant Copy | Cetak 2 salinan (customer + merchant) |
