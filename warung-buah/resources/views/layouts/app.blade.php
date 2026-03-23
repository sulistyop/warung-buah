<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ Setting::get('nama_toko', 'Warung Buah') }} - @yield('title')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
        .fade-in { animation: fadeIn .2s ease-in-out; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(-4px); } to { opacity:1; transform:translateY(0); } }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <!-- Navbar -->
    <nav class="bg-green-700 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-14">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-store text-xl"></i>
                <span class="font-bold text-lg">{{ Setting::get('nama_toko', 'Warung Buah') }}</span>
            </div>
            <div class="flex items-center gap-4 text-sm">
                <a href="{{ route('transaksi.index') }}" class="hover:text-green-200 flex items-center gap-1">
                    <i class="fa-solid fa-list"></i> Transaksi
                </a>
                <a href="{{ route('transaksi.create') }}" class="bg-white text-green-700 font-semibold px-3 py-1 rounded hover:bg-green-100 flex items-center gap-1">
                    <i class="fa-solid fa-plus"></i> Baru
                </a>
                @if(auth()->user()->role === 'admin')
                <a href="{{ route('settings.index') }}" class="hover:text-green-200">
                    <i class="fa-solid fa-gear"></i>
                </a>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="hover:text-red-300 flex items-center gap-1">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Flash messages -->
    <div class="max-w-7xl mx-auto px-4 mt-4">
        @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded flex items-center gap-2 mb-4">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded flex items-center gap-2 mb-4">
            <i class="fa-solid fa-circle-xmark"></i> {{ session('error') }}
        </div>
        @endif
    </div>

    <!-- Content -->
    <main class="max-w-7xl mx-auto px-4 pb-12">
        @yield('content')
    </main>

</body>
</html>
