<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ \App\Models\Setting::get('nama_toko', 'Warung Buah') }} - @yield('title')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
        .fade-in { animation: fadeIn .2s ease-in-out; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(-4px); } to { opacity:1; transform:translateY(0); } }
        /* Larger touch targets for boomers */
        .btn-lg { min-height: 48px; font-size: 1.1rem; }
        .input-lg { min-height: 48px; font-size: 1.1rem; }
        /* High contrast colors */
        .text-high-contrast { color: #1a1a1a; }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 50: '#f0fdf4', 100: '#dcfce7', 200: '#bbf7d0', 300: '#86efac', 400: '#4ade80', 500: '#22c55e', 600: '#16a34a', 700: '#15803d', 800: '#166534', 900: '#14532d' }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- Navbar - Larger and more visible -->
    <nav class="bg-gradient-to-r from-green-700 to-green-600 text-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-store text-2xl"></i>
                    <span class="font-bold text-xl">{{ \App\Models\Setting::get('nama_toko', 'Warung Buah') }}</span>
                </div>
                
                <!-- Navigation Menu -->
                <div class="hidden md:flex items-center gap-2">
                    <a href="{{ route('transaksi.index') }}" 
                       class="px-4 py-2 rounded-lg hover:bg-green-800 transition flex items-center gap-2 {{ request()->routeIs('transaksi.*') ? 'bg-green-800' : '' }}">
                        <i class="fa-solid fa-cash-register text-lg"></i>
                        <span class="font-medium">Transaksi</span>
                    </a>
                    <a href="{{ route('pembayaran.index') }}" 
                       class="px-4 py-2 rounded-lg hover:bg-green-800 transition flex items-center gap-2 {{ request()->routeIs('pembayaran.*') ? 'bg-green-800' : '' }}">
                        <i class="fa-solid fa-money-bill-wave text-lg"></i>
                        <span class="font-medium">Piutang</span>
                    </a>
                    <a href="{{ route('produk.index') }}" 
                       class="px-4 py-2 rounded-lg hover:bg-green-800 transition flex items-center gap-2 {{ request()->routeIs('produk.*') ? 'bg-green-800' : '' }}">
                        <i class="fa-solid fa-apple-whole text-lg"></i>
                        <span class="font-medium">Produk</span>
                    </a>
                    <a href="{{ route('supplier.index') }}" 
                       class="px-4 py-2 rounded-lg hover:bg-green-800 transition flex items-center gap-2 {{ request()->routeIs('supplier.*') ? 'bg-green-800' : '' }}">
                        <i class="fa-solid fa-truck text-lg"></i>
                        <span class="font-medium">Supplier</span>
                    </a>
                    <a href="{{ route('kategori.index') }}" 
                       class="px-4 py-2 rounded-lg hover:bg-green-800 transition flex items-center gap-2 {{ request()->routeIs('kategori.*') ? 'bg-green-800' : '' }}">
                        <i class="fa-solid fa-tags text-lg"></i>
                        <span class="font-medium">Kategori</span>
                    </a>
                    @if(auth()->user()->role === 'admin')
                    <a href="{{ route('settings.index') }}" 
                       class="px-4 py-2 rounded-lg hover:bg-green-800 transition flex items-center gap-2 {{ request()->routeIs('settings.*') ? 'bg-green-800' : '' }}">
                        <i class="fa-solid fa-gear text-lg"></i>
                    </a>
                    @endif
                </div>

                <!-- Right side -->
                <div class="flex items-center gap-3">
                    <a href="{{ route('transaksi.create') }}" 
                       class="bg-white text-green-700 font-bold px-5 py-2 rounded-lg hover:bg-green-50 transition flex items-center gap-2 shadow-md">
                        <i class="fa-solid fa-plus text-lg"></i>
                        <span class="hidden sm:inline">Transaksi Baru</span>
                    </a>
                    <div class="hidden md:flex items-center gap-2 px-3 py-1 bg-green-800 rounded-lg">
                        <i class="fa-solid fa-user"></i>
                        <span class="text-sm">{{ auth()->user()->name }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="p-2 hover:bg-green-800 rounded-lg transition" title="Logout">
                            <i class="fa-solid fa-right-from-bracket text-lg"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div class="md:hidden pb-3 flex flex-wrap gap-2">
                <a href="{{ route('transaksi.index') }}" class="flex-1 text-center py-2 bg-green-800 rounded-lg text-sm">
                    <i class="fa-solid fa-cash-register"></i> Transaksi
                </a>
                <a href="{{ route('pembayaran.index') }}" class="flex-1 text-center py-2 bg-green-800 rounded-lg text-sm">
                    <i class="fa-solid fa-money-bill-wave"></i> Piutang
                </a>
                <a href="{{ route('produk.index') }}" class="flex-1 text-center py-2 bg-green-800 rounded-lg text-sm">
                    <i class="fa-solid fa-apple-whole"></i> Produk
                </a>
                <a href="{{ route('supplier.index') }}" class="flex-1 text-center py-2 bg-green-800 rounded-lg text-sm">
                    <i class="fa-solid fa-truck"></i> Supplier
                </a>
                <a href="{{ route('kategori.index') }}" class="flex-1 text-center py-2 bg-green-800 rounded-lg text-sm">
                    <i class="fa-solid fa-tags"></i> Kategori
                </a>
            </div>
        </div>
    </nav>

    <!-- Flash messages - Larger and more visible -->
    <div class="max-w-7xl mx-auto px-4 mt-4">
        @if(session('success'))
        <div class="bg-green-100 border-2 border-green-500 text-green-800 px-5 py-4 rounded-xl flex items-center gap-3 mb-4 text-lg font-medium shadow-sm">
            <i class="fa-solid fa-circle-check text-2xl text-green-600"></i> 
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="bg-red-100 border-2 border-red-500 text-red-800 px-5 py-4 rounded-xl flex items-center gap-3 mb-4 text-lg font-medium shadow-sm">
            <i class="fa-solid fa-circle-xmark text-2xl text-red-600"></i> 
            {{ session('error') }}
        </div>
        @endif
    </div>

    <!-- Content -->
    <main class="max-w-7xl mx-auto px-4 pb-12">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t mt-auto py-4">
        <div class="max-w-7xl mx-auto px-4 text-center text-gray-500 text-sm">
            &copy; {{ date('Y') }} {{ \App\Models\Setting::get('nama_toko', 'Warung Buah') }} - Sistem Kasir POS
        </div>
    </footer>

</body>
</html>
