# Warung Buah API Documentation

## Overview

API lengkap untuk aplikasi Warung Buah yang siap diintegrasikan dengan Flutter atau aplikasi mobile lainnya.

## Base URL

```
http://your-domain.com/api
```

## Swagger Documentation

Akses dokumentasi interaktif Swagger UI di:

```
http://your-domain.com/api/documentation
```

## Authentication

API menggunakan **Laravel Sanctum** untuk autentikasi berbasis token.

### Mendapatkan Token

```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "user@example.com",
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

### Menggunakan Token

Untuk semua endpoint yang memerlukan autentikasi, tambahkan header:

```http
Authorization: Bearer {token}
```

## Endpoints

### Auth
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/auth/login` | Login user |
| POST | `/auth/register` | Register user baru |
| POST | `/auth/logout` | Logout (hapus token) |
| GET | `/auth/me` | Get user yang sedang login |
| PUT | `/auth/profile` | Update profile user |
| PUT | `/auth/password` | Ganti password |

### Transaksi
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/transaksi` | List transaksi dengan filter & pagination |
| GET | `/transaksi/form-data` | Data untuk form transaksi (supplier, produk, komisi) |
| GET | `/transaksi/statistics` | Statistik transaksi |
| POST | `/transaksi` | Buat transaksi baru |
| GET | `/transaksi/{id}` | Detail transaksi |
| DELETE | `/transaksi/{id}` | Hapus transaksi |

### Produk
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/produk` | List produk dengan filter & pagination |
| GET | `/produk/search` | Search produk (autocomplete) |
| GET | `/produk/stok-rendah` | Produk dengan stok rendah |
| POST | `/produk` | Tambah produk baru |
| GET | `/produk/{id}` | Detail produk |
| PUT | `/produk/{id}` | Update produk |
| DELETE | `/produk/{id}` | Hapus produk |

### Supplier
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/supplier` | List supplier dengan filter & pagination |
| GET | `/supplier/search` | Search supplier (autocomplete) |
| POST | `/supplier` | Tambah supplier baru |
| GET | `/supplier/{id}` | Detail supplier |
| PUT | `/supplier/{id}` | Update supplier |
| DELETE | `/supplier/{id}` | Hapus supplier |

### Kategori
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/kategori` | List kategori dengan filter & pagination |
| GET | `/kategori/all` | Semua kategori aktif (untuk dropdown) |
| GET | `/kategori/search` | Search kategori (autocomplete) |
| GET | `/kategori/warna-options` | Opsi warna kategori |
| POST | `/kategori` | Tambah kategori baru |
| GET | `/kategori/{id}` | Detail kategori |
| PUT | `/kategori/{id}` | Update kategori |
| DELETE | `/kategori/{id}` | Hapus kategori |

### Pembayaran
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/pembayaran` | List piutang (transaksi belum lunas) |
| GET | `/pembayaran/summary` | Ringkasan piutang |
| GET | `/pembayaran/metode-options` | Opsi metode pembayaran |
| GET | `/pembayaran/transaksi/{id}` | Riwayat pembayaran transaksi |
| POST | `/pembayaran/transaksi/{id}` | Catat pembayaran baru |
| DELETE | `/pembayaran/{id}` | Hapus pembayaran |

### Settings
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/settings` | Get semua pengaturan |
| GET | `/settings/app-info` | Info aplikasi (public) |
| GET | `/settings/{key}` | Get pengaturan by key |
| PUT | `/settings` | Update pengaturan (admin only) |

## Response Format

Semua response API menggunakan format yang konsisten:

### Success Response
```json
{
    "success": true,
    "message": "Success",
    "data": { ... }
}
```

### Error Response
```json
{
    "success": false,
    "message": "Error message",
    "errors": { ... }
}
```

### Pagination Response
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [ ... ],
        "first_page_url": "...",
        "from": 1,
        "last_page": 5,
        "last_page_url": "...",
        "next_page_url": "...",
        "path": "...",
        "per_page": 20,
        "prev_page_url": null,
        "to": 20,
        "total": 100
    }
}
```

## Contoh Penggunaan di Flutter

### Setup HTTP Client

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

class ApiService {
  static const String baseUrl = 'http://your-domain.com/api';
  String? _token;

  void setToken(String token) {
    _token = token;
  }

  Map<String, String> get _headers => {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    if (_token != null) 'Authorization': 'Bearer $_token',
  };

  Future<Map<String, dynamic>> get(String endpoint) async {
    final response = await http.get(
      Uri.parse('$baseUrl$endpoint'),
      headers: _headers,
    );
    return json.decode(response.body);
  }

  Future<Map<String, dynamic>> post(String endpoint, Map<String, dynamic> data) async {
    final response = await http.post(
      Uri.parse('$baseUrl$endpoint'),
      headers: _headers,
      body: json.encode(data),
    );
    return json.decode(response.body);
  }
}
```

### Contoh Login

```dart
Future<void> login(String email, String password) async {
  final response = await apiService.post('/auth/login', {
    'email': email,
    'password': password,
    'device_name': 'flutter_app',
  });

  if (response['success']) {
    apiService.setToken(response['data']['token']);
    // Save token to secure storage
  }
}
```

### Contoh Get Transaksi

```dart
Future<List<Transaksi>> getTransaksi({int page = 1, String? search}) async {
  String endpoint = '/transaksi?page=$page';
  if (search != null) endpoint += '&cari=$search';
  
  final response = await apiService.get(endpoint);
  
  if (response['success']) {
    return (response['data']['data'] as List)
        .map((item) => Transaksi.fromJson(item))
        .toList();
  }
  throw Exception(response['message']);
}
```

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
| 500 | Server Error - Kesalahan server |

## Rate Limiting

API menggunakan rate limiting default Laravel. Jika terkena limit, akan mendapat response:

```json
{
    "message": "Too Many Attempts."
}
```

## File Structure

```
app/Http/Controllers/Api/
├── Controller.php           # Base controller dengan helper methods
├── AuthController.php       # Authentication endpoints
├── TransaksiController.php  # Transaksi CRUD
├── ProdukController.php     # Produk CRUD
├── SupplierController.php   # Supplier CRUD
├── KategoriController.php   # Kategori CRUD
├── PembayaranController.php # Pembayaran/Piutang
├── SettingController.php    # App settings
└── Schemas/
    └── SwaggerSchemas.php   # OpenAPI schema definitions

routes/
└── api.php                  # API routes definition

config/
├── l5-swagger.php           # Swagger configuration
└── sanctum.php              # Sanctum configuration
```

## Development

### Generate Swagger Documentation

```bash
php artisan l5-swagger:generate
```

### Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
```

### Run Migrations

```bash
php artisan migrate
```
