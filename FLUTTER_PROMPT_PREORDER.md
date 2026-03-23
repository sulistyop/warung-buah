# Prompt Flutter - Modul Pre Order (PO)

## Overview

Tambahkan modul **Pre Order** ke aplikasi Flutter Warung Buah. Pre Order adalah fitur pemesanan buah di muka dari pelanggan, yang nantinya bisa dikonversi menjadi Transaksi penjualan.

---

## API Endpoints Pre Order

### List Pre Order
```http
GET /pre-order?status=pending&per_page=20&page=1
Authorization: Bearer {token}
```
Filter status: `pending` | `diproses` | `selesai` | `dibatalkan`

Response:
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "kode_po": "PO-20260314-0001",
                "nama_pelanggan": "Toko Maju",
                "tanggal_po": "2026-03-14",
                "tanggal_kirim": "2026-03-15",
                "total": 1500000,
                "status": "pending",
                "catatan": null,
                "pelanggan": { "id": 1, "nama_pelanggan": "Toko Maju" },
                "user": { "id": 1, "name": "Admin" },
                "created_at": "2026-03-14T08:00:00Z"
            }
        ],
        "total": 10,
        "last_page": 1
    }
}
```

### Detail Pre Order
```http
GET /pre-order/{id}
```
Response includes: `pelanggan`, `user`, `transaksi`, `details`:
```json
{
    "success": true,
    "data": {
        "id": 1,
        "kode_po": "PO-20260314-0001",
        "nama_pelanggan": "Toko Maju",
        "tanggal_po": "2026-03-14",
        "tanggal_kirim": "2026-03-15",
        "status": "pending",
        "total": 2500000,
        "catatan": "Kirim pagi",
        "transaksi_id": null,
        "transaksi": null,
        "pelanggan": { "id": 1, "nama_pelanggan": "Toko Maju" },
        "details": [
            {
                "id": 1,
                "pre_order_id": 1,
                "detail_barang_datang_id": null,
                "supplier_id": 2,
                "nama_supplier": "PT Buah Segar",
                "nama_produk": "Apel Fuji",
                "ukuran": "A",
                "jumlah_peti": 5,
                "harga_per_kg": 25000,
                "estimasi_berat_bersih": 100,
                "subtotal": 2500000
            }
        ]
    }
}
```

### Buat Pre Order
```http
POST /pre-order
Content-Type: application/json

{
    "pelanggan_id": 1,
    "nama_pelanggan": "Toko Maju",
    "tanggal_po": "2026-03-14",
    "tanggal_kirim": "2026-03-15",
    "catatan": "Kirim pagi",
    "details": [
        {
            "supplier_id": 2,
            "nama_supplier": "PT Buah Segar",
            "nama_produk": "Apel Fuji",
            "ukuran": "A",
            "jumlah_peti": 5,
            "harga_per_kg": 25000,
            "estimasi_berat_bersih": 100,
            "detail_barang_datang_id": null
        }
    ]
}
```
> `pelanggan_id` opsional. `detail_barang_datang_id` opsional (nullable).
> `supplier_id` & `nama_supplier` dikirim sebagai konteks item PO.

### Proses PO → Link ke Transaksi
```http
POST /pre-order/{id}/proses
Content-Type: application/json

{
    "transaksi_id": 42
}
```
> Dipanggil Flutter SETELAH transaksi berhasil dibuat dari data PO. BE akan update status PO menjadi `diproses` dan menyimpan `transaksi_id`.

Response:
```json
{
    "success": true,
    "message": "Pre Order berhasil diproses.",
    "data": {
        "id": 1,
        "kode_po": "PO-20260314-0001",
        "status": "diproses",
        "transaksi_id": 42,
        "transaksi": {
            "id": 42,
            "kode_transaksi": "TRX-20260314-0042"
        }
    }
}
```

### Suggestion PO untuk Form Transaksi
```http
GET /pre-order/{id}/form-transaksi
```
Response (data siap pakai untuk pre-fill form Create Transaksi):
```json
{
    "success": true,
    "data": {
        "po_id": 1,
        "kode_po": "PO-20260314-0001",
        "pelanggan_id": 1,
        "nama_pelanggan": "Toko Maju",
        "catatan": "Kirim pagi",
        "items": [
            {
                "supplier_id": 2,
                "nama_supplier": "PT Buah Segar",
                "jenis_buah": "Apel Fuji",
                "ukuran": "A",
                "harga_per_kg": 25000,
                "jumlah_peti_po": 5,
                "peti": []
            }
        ]
    }
}
```

### Batalkan Pre Order
```http
POST /pre-order/{id}/batal
```

---

## Spesifikasi UI Flutter

### Navigasi

Tambahkan navigasi ke modul Pre Order di aplikasi yang sudah ada.

---

### Screen 1: PO List Screen

**Route:** `/pre-order`

**UI:**
- AppBar: `"Pre Order"` + FAB/tombol `+` pojok kanan bawah
- Filter chip bar (horizontal scroll):
  `Semua` | `Pending` | `Diproses` | `Selesai` | `Dibatalkan`
- ListView `PreOrderCard`:

```
┌─────────────────────────────────────────┐
│ PO-20260314-0001          [PENDING]     │
│ Toko Maju                               │
│ Tgl PO: 14 Mar 2026                     │
│ Tgl Kirim: 15 Mar 2026                  │
│ Total Estimasi: Rp 2.500.000            │
│ 2 item pesanan                          │
└─────────────────────────────────────────┘
```

- **Status badge warna:**
  - `pending` → Orange
  - `diproses` → Blue
  - `selesai` → Green
  - `dibatalkan` → Red/Grey

- Pull to refresh
- Infinite scroll pagination
- Empty state jika tidak ada data

---

### Screen 2: Create PO Screen

**Route:** `/pre-order/create`

**Layout Form:**

```
AppBar: "Buat Pre Order"

━━━ Informasi Pelanggan ━━━

Nama Pelanggan *
[TextField]

Pilih dari Daftar Pelanggan (optional)
[SearchableDropdown → GET /pelanggan/all]
→ Jika dipilih, auto-fill Nama Pelanggan

Tanggal PO *
[DatePicker — default: hari ini]

Tanggal Kirim (Estimasi)
[DatePicker — optional, >= tanggal PO]

Catatan
[TextField multiline]

━━━ Item Pesanan ━━━

┌── Item 1 ────────────────────────────┐
│ Pilih Supplier *                      │
│ [SearchableDropdown → GET /supplier/search?term=]
│                                       │
│ Pilih Produk *                        │
│ [SearchableDropdown → GET /produk/search?term=&supplier={id}]
│ → Auto-fill: nama_produk, ukuran      │
│                                       │
│ Ukuran                                │
│ [TextField — auto-filled, bisa edit]  │
│                                       │
│ Harga per Kg *                        │
│ [NumberField — freetext, Rp format]   │
│                                       │
│ Jumlah Peti *                         │
│ [NumberField — integer, min: 1]       │
│                                       │
│ Estimasi Berat Bersih (kg total)      │
│ [NumberField — optional]              │
│                                       │
│ Subtotal: Rp _____ (display only)     │
│              [🗑 Hapus Item]          │
└───────────────────────────────────────┘

[+ Tambah Item]

━━━ Total Estimasi: Rp _____ ━━━

[        Simpan Pre Order        ]
```

**Logika UI:**
- **Pilih Supplier** → reset pilihan produk → filter produk by supplier
- **Pilih Produk** → auto-fill `nama_produk` dan `ukuran`
- **Harga per kg** → input manual, BISA berbeda dari harga master produk
- **Subtotal** = `estimasi_berat_bersih × harga_per_kg` (tampilkan 0 jika estimasi kosong)
- **Total** = sum semua subtotal
- Bisa tambah banyak item (multi-supplier dalam satu PO)

**Payload dikirim:**
```dart
final payload = {
  "pelanggan_id": selectedPelangganId,   // null jika tidak dipilih
  "nama_pelanggan": namaPelangganController.text,
  "tanggal_po": DateFormat('yyyy-MM-dd').format(tanggalPo),
  "tanggal_kirim": tanggalKirim != null
      ? DateFormat('yyyy-MM-dd').format(tanggalKirim!)
      : null,
  "catatan": catatanController.text.isEmpty ? null : catatanController.text,
  "details": items.map((item) => {
    "supplier_id": item.supplierId,
    "nama_supplier": item.namaSupplier,
    "nama_produk": item.namaProduk,
    "ukuran": item.ukuran,
    "jumlah_peti": item.jumlahPeti,
    "harga_per_kg": item.hargaPerKg,
    "estimasi_berat_bersih": item.estimasiBeratBersih ?? 0,
    "detail_barang_datang_id": null,
  }).toList(),
};
```

---

### Screen 3: Detail PO Screen

**Route:** `/pre-order/:id`

**UI:**
```
AppBar: "PO-20260314-0001"

┌─────────────────────────────────────┐
│ Status: [PENDING]                   │
│ Pelanggan: Toko Maju                │
│ Tanggal PO: 14 Maret 2026           │
│ Tanggal Kirim: 15 Maret 2026        │
│ Catatan: Kirim pagi                 │
│ Dibuat oleh: Admin                  │
└─────────────────────────────────────┘

━━━ Item Pesanan ━━━
┌─────────────────────────────────────┐
│ Apel Fuji (Ukuran A)                │
│ Supplier: PT Buah Segar             │
│ Jumlah: 5 peti                      │
│ Harga: Rp 25.000/kg                 │
│ Est. Berat: 100 kg                  │
│ Subtotal: Rp 2.500.000              │
└─────────────────────────────────────┘

━━━ Total Estimasi: Rp 2.500.000 ━━━

[Tombol Aksi — conditional berdasarkan status]
```

**Tombol Aksi berdasarkan status:**

| Status | Tombol |
|--------|--------|
| `pending` | **[Proses PO]** (primary) + **[Batalkan PO]** (danger outline) |
| `diproses` | **[Lihat Transaksi]** (navigate ke transaksi terkait) |
| `selesai` | Info saja: "PO ini telah selesai" |
| `dibatalkan` | Info saja: "PO ini telah dibatalkan" |

**Aksi Proses PO:**
```dart
// 1. Tampilkan konfirmasi dialog
// 2. Call POST /pre-order/{id}/proses
// 3. Response berisi transaksi_id
// 4. Navigate ke Detail Transaksi: /transaksi/{transaksi_id}
// 5. Di transaksi itu, data sudah terisi dari PO (nama pelanggan, item, harga)
```

**Aksi Batalkan PO:**
```dart
// 1. Tampilkan konfirmasi dialog
// 2. Call POST /pre-order/{id}/batal
// 3. Refresh halaman, tampilkan status "dibatalkan"
```

---

### Integrasi dengan Create Transaksi

Setelah **[Proses PO]** ditekan dan BE mengembalikan `transaksi_id`:
- Navigate langsung ke `TransaksiDetailScreen` (bukan create lagi, karena BE sudah membuat transaksi)
- Transaksi sudah berisi item dari PO dengan harga yang sudah ditentukan
- User melengkapi data aktual (berat peti saat timbang, biaya operasional, status bayar)

**Di menu Transaksi**, pada list transaksi yang berasal dari PO, tampilkan badge/label "Dari PO" untuk membedakannya.

---

## Data Models (Dart)

```dart
class PreOrder {
  final int id;
  final String kodePo;
  final int? pelangganId;
  final String namaPelanggan;
  final String tanggalPo;
  final String? tanggalKirim;
  final double total;
  final String status; // pending, diproses, selesai, dibatalkan
  final String? catatan;
  final int? transaksiId;
  final Pelanggan? pelanggan;
  final Transaksi? transaksi;
  final User? user;
  final List<DetailPreOrder>? details;

  const PreOrder({
    required this.id,
    required this.kodePo,
    this.pelangganId,
    required this.namaPelanggan,
    required this.tanggalPo,
    this.tanggalKirim,
    required this.total,
    required this.status,
    this.catatan,
    this.transaksiId,
    this.pelanggan,
    this.transaksi,
    this.user,
    this.details,
  });

  factory PreOrder.fromJson(Map<String, dynamic> json) => PreOrder(
    id: json['id'],
    kodePo: json['kode_po'],
    pelangganId: json['pelanggan_id'],
    namaPelanggan: json['nama_pelanggan'],
    tanggalPo: json['tanggal_po'],
    tanggalKirim: json['tanggal_kirim'],
    total: (json['total'] as num).toDouble(),
    status: json['status'],
    catatan: json['catatan'],
    transaksiId: json['transaksi_id'],
    pelanggan: json['pelanggan'] != null
        ? Pelanggan.fromJson(json['pelanggan'])
        : null,
    transaksi: json['transaksi'] != null
        ? Transaksi.fromJson(json['transaksi'])
        : null,
    user: json['user'] != null ? User.fromJson(json['user']) : null,
    details: json['details'] != null
        ? (json['details'] as List)
            .map((d) => DetailPreOrder.fromJson(d))
            .toList()
        : null,
  );
}

class DetailPreOrder {
  final int id;
  final int preOrderId;
  final int? detailBarangDatangId;
  final int? supplierId;
  final String? namaSupplier;
  final String namaProduk;
  final String? ukuran;
  final int jumlahPeti;
  final double hargaPerKg;
  final double estimasiBeratBersih;
  final double subtotal;

  const DetailPreOrder({
    required this.id,
    required this.preOrderId,
    this.detailBarangDatangId,
    this.supplierId,
    this.namaSupplier,
    required this.namaProduk,
    this.ukuran,
    required this.jumlahPeti,
    required this.hargaPerKg,
    required this.estimasiBeratBersih,
    required this.subtotal,
  });

  factory DetailPreOrder.fromJson(Map<String, dynamic> json) => DetailPreOrder(
    id: json['id'],
    preOrderId: json['pre_order_id'],
    detailBarangDatangId: json['detail_barang_datang_id'],
    supplierId: json['supplier_id'],
    namaSupplier: json['nama_supplier'],
    namaProduk: json['nama_produk'],
    ukuran: json['ukuran'],
    jumlahPeti: json['jumlah_peti'],
    hargaPerKg: (json['harga_per_kg'] as num).toDouble(),
    estimasiBeratBersih: (json['estimasi_berat_bersih'] as num).toDouble(),
    subtotal: (json['subtotal'] as num).toDouble(),
  );
}

// Model sementara untuk state form Create PO
class PoItemForm {
  int? supplierId;
  String namaSupplier;
  int? produkId;
  String namaProduk;
  String? ukuran;
  double hargaPerKg;
  int jumlahPeti;
  double? estimasiBeratBersih;

  PoItemForm({
    this.supplierId,
    this.namaSupplier = '',
    this.produkId,
    this.namaProduk = '',
    this.ukuran,
    this.hargaPerKg = 0,
    this.jumlahPeti = 1,
    this.estimasiBeratBersih,
  });

  double get subtotal => (estimasiBeratBersih ?? 0) * hargaPerKg;
}
```

---

## GetX Controller Pre Order

```dart
class PreOrderController extends GetxController {
  final ApiProvider _api = Get.find<ApiProvider>();

  final RxList<PreOrder> poList = <PreOrder>[].obs;
  final Rx<PreOrder?> selectedPo = Rx<PreOrder?>(null);
  final RxBool isLoading = false.obs;
  final RxString filterStatus = ''.obs;
  int _currentPage = 1;
  bool _hasMore = true;

  @override
  void onInit() {
    super.onInit();
    fetchList();
  }

  Future<void> fetchList({bool refresh = false}) async {
    if (refresh) {
      _currentPage = 1;
      _hasMore = true;
      poList.clear();
    }
    if (!_hasMore) return;

    try {
      isLoading.value = true;
      final response = await _api.get('/pre-order', queryParameters: {
        'page': _currentPage,
        'per_page': 20,
        if (filterStatus.value.isNotEmpty) 'status': filterStatus.value,
      });

      final data = response.data['data'];
      final items = (data['data'] as List)
          .map((j) => PreOrder.fromJson(j))
          .toList();

      poList.addAll(items);
      _hasMore = _currentPage < data['last_page'];
      _currentPage++;
    } catch (e) {
      Get.snackbar('Error', 'Gagal memuat data Pre Order');
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> fetchDetail(int id) async {
    try {
      isLoading.value = true;
      final response = await _api.get('/pre-order/$id');
      selectedPo.value = PreOrder.fromJson(response.data['data']);
    } catch (e) {
      Get.snackbar('Error', 'Gagal memuat detail PO');
    } finally {
      isLoading.value = false;
    }
  }

  Future<bool> createPO(Map<String, dynamic> payload) async {
    try {
      isLoading.value = true;
      final response = await _api.post('/pre-order', data: payload);
      if (response.data['success'] == true) {
        await fetchList(refresh: true);
        return true;
      }
      return false;
    } catch (e) {
      Get.snackbar('Error', 'Gagal membuat Pre Order');
      return false;
    } finally {
      isLoading.value = false;
    }
  }

  Future<int?> prosesPO(int id) async {
    try {
      isLoading.value = true;
      final response = await _api.post('/pre-order/$id/proses');
      if (response.data['success'] == true) {
        final transaksiId = response.data['data']['transaksi_id'] as int?;
        await fetchList(refresh: true);
        return transaksiId;
      }
      return null;
    } catch (e) {
      Get.snackbar('Error', 'Gagal memproses PO');
      return null;
    } finally {
      isLoading.value = false;
    }
  }

  Future<bool> batalPO(int id) async {
    try {
      isLoading.value = true;
      final response = await _api.post('/pre-order/$id/batal');
      if (response.data['success'] == true) {
        await fetchList(refresh: true);
        return true;
      }
      return false;
    } catch (e) {
      Get.snackbar('Error', 'Gagal membatalkan PO');
      return false;
    } finally {
      isLoading.value = false;
    }
  }
}
```

---

## Folder Structure Tambahan

```
lib/app/modules/pre_order/
├── bindings/
│   └── pre_order_binding.dart
├── controllers/
│   └── pre_order_controller.dart
├── views/
│   ├── pre_order_list_view.dart
│   ├── pre_order_create_view.dart
│   └── pre_order_detail_view.dart
└── widgets/
    ├── po_card.dart
    └── po_item_form_widget.dart
```

---

## Routes Tambahan

```dart
// Di app_routes.dart
abstract class Routes {
  static const PRE_ORDER = '/pre-order';
  static const PRE_ORDER_CREATE = '/pre-order/create';
  static const PRE_ORDER_DETAIL = '/pre-order/:id';
}

// Di app_pages.dart
GetPage(
  name: Routes.PRE_ORDER,
  page: () => PreOrderListView(),
  binding: PreOrderBinding(),
),
GetPage(
  name: Routes.PRE_ORDER_CREATE,
  page: () => PreOrderCreateView(),
  binding: PreOrderBinding(),
),
GetPage(
  name: Routes.PRE_ORDER_DETAIL,
  page: () => PreOrderDetailView(),
  binding: PreOrderBinding(),
),
```

---

## Status Backend — Semua Sudah Tersedia ✅

| # | Endpoint | Status |
|---|----------|--------|
| 1 | `GET /pre-order` (list + filter status + cari) | **ADA** ✓ |
| 2 | `POST /pre-order` (create, include supplier_id & nama_supplier) | **ADA** ✓ |
| 3 | `GET /pre-order/{id}` (detail) | **ADA** ✓ |
| 4 | `GET /pre-order/{id}/form-transaksi` (suggestion untuk form transaksi) | **ADA** ✓ |
| 5 | `POST /pre-order/{id}/proses` (link PO ke transaksi) | **ADA** ✓ |
| 6 | `POST /pre-order/{id}/batal` (batalkan PO) | **ADA** ✓ |
| 7 | `supplier_id` & `nama_supplier` di `detail_pre_order` | **ADA** ✓ (migrasi done) |

## Flow Flutter — Proses PO ke Transaksi

```
1. Flutter: tap [Proses PO]
2. Flutter: GET /pre-order/{id}/form-transaksi
   → dapat data pre-filled: nama_pelanggan, items (supplier, produk, harga)
3. Flutter: navigate ke CreateTransaksiScreen dengan initialData dari step 2
4. User: isi berat peti aktual, status bayar, biaya operasional
5. Flutter: POST /transaksi → dapat transaksi_id
6. Flutter: POST /pre-order/{id}/proses { "transaksi_id": xxx }
   → PO status berubah jadi "diproses"
7. Flutter: navigate ke Detail Transaksi yang baru
```
