# Prompt untuk Membuat UI Flutter - Warung Buah

## Overview Aplikasi

Buatkan aplikasi Flutter **Warung Buah** - Sistem manajemen transaksi buah dengan fitur lengkap untuk supplier, produk, kategori, transaksi, dan pembayaran.

## Tech Stack yang Digunakan
- Flutter (versi terbaru)
- GetX untuk state management dan routing
- Dio untuk HTTP client
- SharedPreferences / Flutter Secure Storage untuk token storage
- UI Modern dengan Material Design 3

## Base URL API
```
http://your-domain.com/api
```

## Fitur Utama yang Harus Dibuat

### 1. Authentication
- Login Screen
- Register Screen (opsional)
- Logout
- Auto login jika token masih valid

### 2. Dashboard/Home
- Statistik transaksi
- Shortcut ke fitur utama
- Produk stok rendah alert

### 3. Master Data
- **Supplier** (CRUD + List dengan produk count)
- **Produk** (CRUD + Filter by supplier & kategori)
- **Kategori** (CRUD)

### 4. Transaksi
- List transaksi dengan filter & pagination
- Create transaksi dengan:
  - Nama pelanggan
  - Status bayar (lunas/tempo/cicil)
  - Multiple item buah per supplier
  - Detail peti per item (berat kotor & kemasan)
  - Biaya operasional
  - Kalkulasi otomatis komisi
- Detail transaksi
- Delete transaksi

### 5. Pembayaran/Piutang
- List piutang (transaksi belum lunas)
- Ringkasan total piutang
- Catat pembayaran baru
- Riwayat pembayaran per transaksi

### 6. Settings
- Edit profile
- Ganti password
- Pengaturan toko (admin only)

---

## API Documentation

### Authentication
Menggunakan Bearer Token (Laravel Sanctum)

```http
Authorization: Bearer {token}
```

### Response Format Standard
```json
{
    "success": true,
    "message": "Success",
    "data": { ... }
}
```

### Pagination Response
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [ ... ],
        "per_page": 20,
        "total": 100,
        "last_page": 5
    }
}
```

---

## API Endpoints Detail

### 1. AUTH

#### Login
```http
POST /auth/login
Content-Type: application/json

{
    "email": "admin@example.com",
    "password": "password",
    "device_name": "flutter_app"
}
```
Response:
```json
{
    "success": true,
    "message": "Login berhasil",
    "data": {
        "token": "1|abcdef123456...",
        "user": {
            "id": 1,
            "name": "Admin",
            "email": "admin@example.com",
            "role": "admin"
        }
    }
}
```

#### Register
```http
POST /auth/register
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

#### Logout
```http
POST /auth/logout
Authorization: Bearer {token}
```

#### Get Current User
```http
GET /auth/me
Authorization: Bearer {token}
```
Response:
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Admin",
        "email": "admin@example.com",
        "role": "admin"
    }
}
```

#### Update Profile
```http
PUT /auth/profile
{
    "name": "John Updated",
    "email": "john.updated@example.com"
}
```

#### Change Password
```http
PUT /auth/password
{
    "current_password": "oldpassword",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

---

### 2. SUPPLIER

#### List Supplier (with pagination)
```http
GET /supplier?page=1&per_page=20&cari=keyword&status=aktif
```
Response:
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "kode_supplier": "SUP-0001",
                "nama_supplier": "PT Buah Segar",
                "telepon": "08123456789",
                "email": "supplier@example.com",
                "alamat": "Jl. Pasar No. 123",
                "kota": "Jakarta",
                "kontak_person": "Budi",
                "catatan": null,
                "aktif": true,
                "produk_count": 5,
                "created_at": "2026-03-05T10:00:00Z"
            }
        ],
        "total": 50
    }
}
```

#### Search Supplier (autocomplete)
```http
GET /supplier/search?term=buah&limit=10
```

#### Get Supplier Detail (with products)
```http
GET /supplier/{id}
```
Response includes `produk` array with all products of this supplier.

#### Create Supplier
```http
POST /supplier
{
    "nama_supplier": "PT Buah Segar",
    "telepon": "08123456789",
    "email": "supplier@example.com",
    "alamat": "Jl. Pasar Buah No. 123",
    "kota": "Jakarta",
    "kontak_person": "Budi",
    "catatan": "Supplier terpercaya"
}
```

#### Update Supplier
```http
PUT /supplier/{id}
{
    "nama_supplier": "PT Buah Segar Updated",
    "telepon": "08123456789",
    "email": "supplier@example.com",
    "alamat": "Jl. Pasar Buah No. 456",
    "kota": "Bandung",
    "kontak_person": "Ani",
    "catatan": "Updated notes",
    "aktif": true
}
```

#### Delete Supplier
```http
DELETE /supplier/{id}
```

---

### 3. PRODUK

#### List Produk (with pagination & filter)
```http
GET /produk?page=1&per_page=20&cari=apel&kategori=1&supplier=2&status=aktif
```
Response:
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "kode_produk": "PRD-0001",
                "nama_produk": "Apel Fuji",
                "supplier_id": 1,
                "ukuran": "A",
                "kategori_id": 1,
                "satuan": "kg",
                "harga_beli": 20000,
                "harga_jual": 25000,
                "stok": 100,
                "stok_minimum": 10,
                "keterangan": null,
                "aktif": true,
                "nama_kategori": "Buah Import",
                "nama_supplier": "PT Buah Segar",
                "kategori_relasi": { ... },
                "supplier": { ... }
            }
        ],
        "total": 100
    }
}
```

#### Get Produk Stok Rendah
```http
GET /produk/stok-rendah
```

#### Search Produk (autocomplete)
```http
GET /produk/search?term=apel&limit=10
```

#### Create Produk
```http
POST /produk
{
    "nama_produk": "Apel Fuji",
    "supplier_id": 1,
    "ukuran": "A",
    "kategori_id": 1,
    "satuan": "kg",
    "harga_beli": 20000,
    "harga_jual": 25000,
    "stok": 100,
    "stok_minimum": 10,
    "keterangan": "Import dari Jepang"
}
```

#### Update Produk
```http
PUT /produk/{id}
{
    "nama_produk": "Apel Fuji Updated",
    "supplier_id": 2,
    "ukuran": "B",
    "kategori_id": 1,
    "satuan": "kg",
    "harga_beli": 22000,
    "harga_jual": 28000,
    "stok": 150,
    "stok_minimum": 15,
    "keterangan": "Updated",
    "aktif": true
}
```

#### Delete Produk
```http
DELETE /produk/{id}
```

---

### 4. KATEGORI

#### List Kategori
```http
GET /kategori?page=1&per_page=20&cari=buah&status=aktif
```

#### Get All Kategori Aktif (for dropdown)
```http
GET /kategori/all
```

#### Get Warna Options
```http
GET /kategori/warna-options
```
Response:
```json
{
    "success": true,
    "data": {
        "#4CAF50": "Hijau",
        "#2196F3": "Biru",
        "#FF9800": "Orange",
        "#E91E63": "Pink",
        "#9C27B0": "Ungu"
    }
}
```

#### Create Kategori
```http
POST /kategori
{
    "nama_kategori": "Buah Lokal",
    "deskripsi": "Buah-buahan lokal Indonesia",
    "warna": "#4CAF50",
    "aktif": true
}
```

#### Update Kategori
```http
PUT /kategori/{id}
{
    "nama_kategori": "Buah Lokal Updated",
    "deskripsi": "Updated description",
    "warna": "#2196F3",
    "aktif": true
}
```

#### Delete Kategori
```http
DELETE /kategori/{id}
```

---

### 5. TRANSAKSI

#### List Transaksi
```http
GET /transaksi?page=1&per_page=20&cari=toko&status_bayar=tempo&tanggal_dari=2026-03-01&tanggal_sampai=2026-03-31
```
Response:
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "kode_transaksi": "TRX-20260305-0001",
                "nama_pelanggan": "Toko Buah Segar",
                "status_bayar": "tempo",
                "tanggal_jatuh_tempo": "2026-04-01",
                "catatan": null,
                "komisi_persen": 10,
                "total_kotor": 1000000,
                "total_komisi": 100000,
                "total_biaya_operasional": 50000,
                "total_bersih": 850000,
                "total_tagihan": 850000,
                "total_dibayar": 500000,
                "sisa_tagihan": 350000,
                "status": "selesai",
                "user_id": 1,
                "created_at": "2026-03-05T10:00:00Z"
            }
        ],
        "total": 150
    }
}
```

#### Get Statistik Transaksi
```http
GET /transaksi/statistics?tanggal_dari=2026-03-01&tanggal_sampai=2026-03-31
```
Response:
```json
{
    "success": true,
    "data": {
        "total_transaksi": 150,
        "total_pendapatan": 15000000,
        "total_piutang": 5000000,
        "transaksi_lunas": 100,
        "transaksi_tempo": 30,
        "transaksi_cicil": 20
    }
}
```

#### Get Form Data (for create transaction)
```http
GET /transaksi/form-data
```
Response:
```json
{
    "success": true,
    "data": {
        "komisi_default": 10,
        "suppliers": [...],
        "produks": [...]
    }
}
```

#### Get Transaksi Detail
```http
GET /transaksi/{id}
```
Response includes: `user`, `item_transaksi` (with `detail_peti`), `biaya_operasional`, `pembayaran`

#### Create Transaksi
```http
POST /transaksi
{
    "nama_pelanggan": "Toko Buah Segar",
    "status_bayar": "tempo",
    "tanggal_jatuh_tempo": "2026-04-01",
    "komisi_persen": 10,
    "catatan": "Kirim pagi hari",
    "uang_diterima": 500000,
    "items": [
        {
            "supplier_id": 1,
            "nama_supplier": "Supplier ABC",
            "jenis_buah": "Apel Fuji",
            "harga_per_kg": 25000,
            "peti": [
                { "berat_kotor": 27.5, "berat_kemasan": 2.5 },
                { "berat_kotor": 26.0, "berat_kemasan": 2.5 },
                { "berat_kotor": 28.0, "berat_kemasan": 2.5 }
            ]
        },
        {
            "supplier_id": 2,
            "nama_supplier": "Supplier XYZ",
            "jenis_buah": "Jeruk Pontianak",
            "harga_per_kg": 18000,
            "peti": [
                { "berat_kotor": 30.0, "berat_kemasan": 3.0 },
                { "berat_kotor": 29.5, "berat_kemasan": 3.0 }
            ]
        }
    ],
    "biaya": [
        { "nama_biaya": "Ongkos kirim", "nominal": 50000 },
        { "nama_biaya": "Bongkar muat", "nominal": 25000 }
    ]
}
```

#### Delete Transaksi
```http
DELETE /transaksi/{id}
```

---

### 6. PEMBAYARAN

#### List Piutang (Transaksi belum lunas)
```http
GET /pembayaran?page=1&per_page=20&cari=toko&status=jatuh_tempo
```

#### Get Summary Piutang
```http
GET /pembayaran/summary
```
Response:
```json
{
    "success": true,
    "data": {
        "total_piutang": 5000000,
        "jumlah_transaksi": 15,
        "jatuh_tempo": 5,
        "belum_jatuh_tempo": 10
    }
}
```

#### Get Metode Pembayaran Options
```http
GET /pembayaran/metode-options
```
Response:
```json
{
    "success": true,
    "data": {
        "tunai": "Tunai",
        "transfer": "Transfer Bank",
        "qris": "QRIS",
        "lainnya": "Lainnya"
    }
}
```

#### Get Riwayat Pembayaran Transaksi
```http
GET /pembayaran/transaksi/{transaksi_id}
```

#### Catat Pembayaran Baru
```http
POST /pembayaran/transaksi/{transaksi_id}
{
    "nominal": 500000,
    "metode": "transfer",
    "referensi": "BCA-123456",
    "catatan": "Pembayaran cicilan 1"
}
```

#### Delete Pembayaran
```http
DELETE /pembayaran/{id}
```

---

### 7. SETTINGS

#### Get All Settings
```http
GET /settings
```

#### Get App Info (Public - no auth required)
```http
GET /settings/app-info
```
Response:
```json
{
    "success": true,
    "data": {
        "nama_toko": "Warung Buah Segar",
        "alamat_toko": "Jl. Pasar No. 123",
        "api_version": "1.0.0"
    }
}
```

#### Update Settings (Admin only)
```http
PUT /settings
{
    "komisi_persen": 10,
    "nama_toko": "Warung Buah Segar",
    "alamat_toko": "Jl. Pasar No. 123"
}
```

---

## Data Models

### User
```dart
class User {
  int id;
  String name;
  String email;
  String role; // admin, kasir
}
```

### Supplier
```dart
class Supplier {
  int id;
  String kodeSupplier;
  String namaSupplier;
  String? telepon;
  String? email;
  String? alamat;
  String? kota;
  String? kontakPerson;
  String? catatan;
  bool aktif;
  int produkCount;
  List<Produk>? produk;
}
```

### Produk
```dart
class Produk {
  int id;
  String kodeProduk;
  String namaProduk;
  int? supplierId;
  String? ukuran; // A, B, C, Super, etc
  int? kategoriId;
  String satuan; // kg, pcs, box, ikat
  double hargaBeli;
  double hargaJual;
  double stok;
  double stokMinimum;
  String? keterangan;
  bool aktif;
  String? namaKategori;
  String? namaSupplier;
  Kategori? kategoriRelasi;
  Supplier? supplier;
}
```

### Kategori
```dart
class Kategori {
  int id;
  String kodeKategori;
  String namaKategori;
  String? deskripsi;
  String warna; // Hex color
  bool aktif;
  int produkCount;
}
```

### Transaksi
```dart
class Transaksi {
  int id;
  String kodeTransaksi;
  String namaPelanggan;
  String statusBayar; // lunas, tempo, cicil
  String? tanggalJatuhTempo;
  String? catatan;
  double komisiPersen;
  double totalKotor;
  double totalKomisi;
  double totalBiayaOperasional;
  double totalBersih;
  double totalTagihan;
  double totalDibayar;
  double sisaTagihan;
  double? uangDiterima;
  double? kembalian;
  String status;
  int userId;
  User? user;
  List<ItemTransaksi>? itemTransaksi;
  List<BiayaOperasional>? biayaOperasional;
  List<Pembayaran>? pembayaran;
}
```

### ItemTransaksi
```dart
class ItemTransaksi {
  int id;
  int transaksiId;
  int? supplierId;
  String namaSupplier;
  String jenisBuah;
  double hargaPerKg;
  int jumlahPeti;
  double totalBeratBersih;
  double subtotal;
  List<DetailPeti>? detailPeti;
}
```

### DetailPeti
```dart
class DetailPeti {
  int id;
  int itemTransaksiId;
  int noPeti;
  double beratKotor;
  double beratKemasan;
  double beratBersih;
}
```

### BiayaOperasional
```dart
class BiayaOperasional {
  int id;
  int transaksiId;
  String namaBiaya;
  double nominal;
}
```

### Pembayaran
```dart
class Pembayaran {
  int id;
  int transaksiId;
  String kodePembayaran;
  double nominal;
  String metode; // tunai, transfer, qris, lainnya
  String? referensi;
  String? catatan;
  double sisaTagihan;
  int userId;
  User? user;
}
```

---

## UI Design Guidelines

### Warna Tema
- Primary: Green (#4CAF50)
- Accent: Orange (#FF9800)
- Background: Light Gray (#F5F5F5)
- Error: Red (#F44336)
- Success: Green (#4CAF50)

### Komponen UI yang Harus Ada

1. **Bottom Navigation**
   - Dashboard
   - Transaksi
   - Piutang
   - Master (Products, Suppliers, Categories)
   - Settings

2. **Common Components**
   - Loading indicator
   - Error handling dengan Snackbar
   - Pull to refresh
   - Infinite scroll pagination
   - Search bar dengan debounce
   - Filter bottom sheet
   - Confirmation dialog
   - Empty state widget

3. **Form Components**
   - Text field dengan validation
   - Dropdown field
   - Date picker
   - Number field dengan format currency
   - Switch/Toggle

4. **Card Components**
   - Transaction card (dengan status badge)
   - Product card
   - Supplier card (dengan badge produk count)
   - Payment history card

---

## Folder Structure Rekomendasi

```
lib/
├── main.dart
├── app/
│   ├── data/
│   │   ├── models/
│   │   │   ├── user_model.dart
│   │   │   ├── supplier_model.dart
│   │   │   ├── produk_model.dart
│   │   │   ├── kategori_model.dart
│   │   │   ├── transaksi_model.dart
│   │   │   ├── pembayaran_model.dart
│   │   │   └── ...
│   │   ├── providers/
│   │   │   └── api_provider.dart
│   │   └── repositories/
│   │       ├── auth_repository.dart
│   │       ├── supplier_repository.dart
│   │       ├── produk_repository.dart
│   │       └── ...
│   ├── modules/
│   │   ├── auth/
│   │   │   ├── bindings/
│   │   │   ├── controllers/
│   │   │   └── views/
│   │   ├── dashboard/
│   │   ├── transaksi/
│   │   ├── produk/
│   │   ├── supplier/
│   │   ├── kategori/
│   │   ├── pembayaran/
│   │   └── settings/
│   ├── routes/
│   │   ├── app_pages.dart
│   │   └── app_routes.dart
│   └── core/
│       ├── theme/
│       ├── utils/
│       ├── values/
│       └── widgets/
```

---

## Contoh Kode Flutter

### API Provider (Dio)
```dart
import 'package:dio/dio.dart';
import 'package:get/get.dart' hide Response;

class ApiProvider extends GetxController {
  static const String baseUrl = 'http://your-domain.com/api';
  
  late Dio _dio;
  String? _token;

  @override
  void onInit() {
    super.onInit();
    _dio = Dio(BaseOptions(
      baseUrl: baseUrl,
      connectTimeout: Duration(seconds: 30),
      receiveTimeout: Duration(seconds: 30),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    ));
    
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) {
        if (_token != null) {
          options.headers['Authorization'] = 'Bearer $_token';
        }
        return handler.next(options);
      },
      onError: (error, handler) {
        if (error.response?.statusCode == 401) {
          // Handle token expired - logout user
        }
        return handler.next(error);
      },
    ));
  }

  void setToken(String token) {
    _token = token;
  }

  void clearToken() {
    _token = null;
  }

  Future<Response> get(String path, {Map<String, dynamic>? queryParameters}) {
    return _dio.get(path, queryParameters: queryParameters);
  }

  Future<Response> post(String path, {dynamic data}) {
    return _dio.post(path, data: data);
  }

  Future<Response> put(String path, {dynamic data}) {
    return _dio.put(path, data: data);
  }

  Future<Response> delete(String path) {
    return _dio.delete(path);
  }
}
```

### Auth Controller Example
```dart
class AuthController extends GetxController {
  final ApiProvider _api = Get.find<ApiProvider>();
  
  final Rx<User?> currentUser = Rx<User?>(null);
  final RxBool isLoading = false.obs;

  Future<bool> login(String email, String password) async {
    try {
      isLoading.value = true;
      
      final response = await _api.post('/auth/login', data: {
        'email': email,
        'password': password,
        'device_name': 'flutter_app',
      });

      if (response.data['success'] == true) {
        final token = response.data['data']['token'];
        _api.setToken(token);
        
        // Save token to secure storage
        await _saveToken(token);
        
        currentUser.value = User.fromJson(response.data['data']['user']);
        return true;
      }
      return false;
    } catch (e) {
      return false;
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> logout() async {
    try {
      await _api.post('/auth/logout');
    } finally {
      _api.clearToken();
      currentUser.value = null;
      await _clearToken();
      Get.offAllNamed('/login');
    }
  }
}
```

---

## Catatan Penting

1. **Supplier memiliki banyak Produk** - Setiap produk terikat ke satu supplier via `supplier_id`
2. **Produk memiliki field Ukuran** - Untuk grade seperti A, B, C, Super, dll
3. **Transaksi memiliki multiple Items** - Setiap item punya supplier berbeda dan multiple peti
4. **Kalkulasi otomatis**:
   - `berat_bersih = berat_kotor - berat_kemasan`
   - `subtotal_item = total_berat_bersih × harga_per_kg`
   - `total_kotor = sum(subtotal_items)`
   - `total_komisi = total_kotor × komisi_persen / 100`
   - `total_bersih = total_kotor - total_komisi - total_biaya_operasional`
   - `sisa_tagihan = total_tagihan - total_dibayar`

5. **Status Bayar**:
   - `lunas` - Pembayaran penuh saat transaksi
   - `tempo` - Bayar nanti (ada jatuh tempo)
   - `cicil` - Pembayaran bertahap

---

## HTTP Status Codes

| Code | Deskripsi |
|------|-----------|
| 200 | OK - Request berhasil |
| 201 | Created - Resource berhasil dibuat |
| 400 | Bad Request - Request tidak valid |
| 401 | Unauthorized - Token tidak valid/expired |
| 403 | Forbidden - Tidak memiliki akses |
| 404 | Not Found - Resource tidak ditemukan |
| 422 | Validation Error - Data tidak valid |
| 500 | Server Error |

---

Dengan menggunakan prompt ini, Anda dapat membuat aplikasi Flutter yang terintegrasi penuh dengan backend API Warung Buah.
