# Flutter Update — Modul Pre Order (PO)

> Ini adalah prompt update terbaru. Backend sudah diupdate, tinggal implementasi Flutter.

---

## Endpoint Baru yang Tersedia

### 1. Filter Cari di List PO
```http
GET /pre-order?cari=toko maju&status=pending&page=1&per_page=20
```
Tambahkan parameter `cari` untuk search by nama pelanggan atau kode PO.

---

### 2. Suggestion Data PO untuk Form Transaksi
```http
GET /pre-order/{id}/form-transaksi
```
Response:
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

---

### 3. Proses PO → Link ke Transaksi
```http
POST /pre-order/{id}/proses
Content-Type: application/json

{
    "transaksi_id": 42
}
```
Response:
```json
{
    "success": true,
    "message": "Pre Order berhasil diproses.",
    "data": {
        "id": 1,
        "kode_po": "PO-20260314-0001",
        "status": "diproses",
        "transaksi_id": 42
    }
}
```

---

### 4. Field Baru di Detail PO
`GET /pre-order/{id}` sekarang mengembalikan `supplier_id` dan `nama_supplier` di setiap item `details`:
```json
"details": [
    {
        "id": 1,
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
```

---

## Update yang Diperlukan di Flutter

### 1. Model `DetailPreOrder` — tambah field baru
```dart
class DetailPreOrder {
  // ... field yang sudah ada ...
  final int? supplierId;       // BARU
  final String? namaSupplier;  // BARU
}
```

### 2. `PreOrderController` — tambah method baru

```dart
// Ambil suggestion data untuk pre-fill form transaksi
Future<Map<String, dynamic>?> getFormTransaksi(int poId) async {
  try {
    final response = await _api.get('/pre-order/$poId/form-transaksi');
    if (response.data['success'] == true) {
      return response.data['data'];
    }
    return null;
  } catch (e) {
    Get.snackbar('Error', 'Gagal mengambil data PO');
    return null;
  }
}

// Link PO ke transaksi yang sudah dibuat
Future<bool> prosesPO(int poId, int transaksiId) async {
  try {
    isLoading.value = true;
    final response = await _api.post('/pre-order/$poId/proses', data: {
      'transaksi_id': transaksiId,
    });
    if (response.data['success'] == true) {
      await fetchDetail(poId);
      return true;
    }
    return false;
  } catch (e) {
    Get.snackbar('Error', 'Gagal memproses PO');
    return false;
  } finally {
    isLoading.value = false;
  }
}
```

### 3. Detail PO Screen — tambah tombol `[Proses PO]`

Tambahkan tombol di bagian bawah screen, conditional berdasarkan status:

```dart
// Di PreOrderDetailView, bagian tombol aksi:
if (po.status == 'pending') ...[
  ElevatedButton(
    onPressed: _handleProsesPO,
    child: Text('Proses PO'),
  ),
  OutlinedButton(
    onPressed: _handleBatalPO,
    style: OutlinedButton.styleFrom(foregroundColor: Colors.red),
    child: Text('Batalkan PO'),
  ),
] else if (po.status == 'diproses') ...[
  ElevatedButton(
    onPressed: () => Get.toNamed('/transaksi/${po.transaksiId}'),
    child: Text('Lihat Transaksi'),
  ),
]
```

### 4. Flow `_handleProsesPO`

```dart
Future<void> _handleProsesPO() async {
  // 1. Konfirmasi
  final confirm = await Get.dialog<bool>(AlertDialog(
    title: Text('Proses Pre Order?'),
    content: Text('PO akan dikonversi ke Transaksi. Anda perlu mengisi berat peti aktual.'),
    actions: [
      TextButton(onPressed: () => Get.back(result: false), child: Text('Batal')),
      ElevatedButton(onPressed: () => Get.back(result: true), child: Text('Lanjut')),
    ],
  ));
  if (confirm != true) return;

  // 2. Ambil suggestion data dari PO
  final suggestion = await controller.getFormTransaksi(po.id);
  if (suggestion == null) return;

  // 3. Navigate ke Create Transaksi dengan data pre-filled
  final transaksiId = await Get.toNamed(
    '/transaksi/create',
    arguments: {'from_po': po.id, 'suggestion': suggestion},
  );

  // 4. Jika transaksi berhasil dibuat, link ke PO
  if (transaksiId != null) {
    await controller.prosesPO(po.id, transaksiId as int);
  }
}
```

### 5. Create Transaksi Screen — terima argument dari PO

Di `TransaksiCreateController` atau `TransaksiCreateView`, tambahkan handling untuk argument `from_po`:

```dart
@override
void onInit() {
  super.onInit();

  // Cek apakah ada data suggestion dari PO
  final args = Get.arguments as Map<String, dynamic>?;
  if (args != null && args.containsKey('suggestion')) {
    _prefillFromPO(args['suggestion']);
    fromPoId = args['from_po'] as int?;
  }
}

void _prefillFromPO(Map<String, dynamic> suggestion) {
  namaPelangganController.text = suggestion['nama_pelanggan'] ?? '';
  catatanController.text = suggestion['catatan'] ?? '';

  // Pre-fill items dari PO (peti masih kosong, user isi sendiri)
  final items = suggestion['items'] as List<dynamic>;
  for (final item in items) {
    addItem(
      supplierId: item['supplier_id'],
      namaSupplier: item['nama_supplier'],
      jenisBuah: item['jenis_buah'],
      hargaPerKg: (item['harga_per_kg'] as num).toDouble(),
    );
  }
}
```

Setelah transaksi berhasil disimpan (`POST /transaksi`), kembalikan `transaksi_id` ke screen sebelumnya:

```dart
// Di akhir method simpanTransaksi(), sebelum navigate:
final fromPoId = Get.arguments?['from_po'];
if (fromPoId != null) {
  // Kembalikan transaksi_id ke Detail PO screen
  Get.back(result: transaksi.id);
} else {
  Get.offNamed('/transaksi/${transaksi.id}');
}
```
