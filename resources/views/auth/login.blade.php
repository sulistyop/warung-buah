<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Warung Buah</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-green-50 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-lg p-8 w-full max-w-sm">
        <div class="text-center mb-6">
            <div class="bg-green-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-3">
                <i class="fa-solid fa-store text-green-600 text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Warung Buah</h1>
            <p class="text-gray-500 text-sm mt-1">Silakan masuk untuk lanjut</p>
        </div>

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded mb-4">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                    placeholder="admin@warung.com">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                    placeholder="••••••••">
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="remember" id="remember" class="rounded">
                <label for="remember" class="text-sm text-gray-600">Ingat saya</label>
            </div>
            <button type="submit"
                class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 rounded-lg transition">
                <i class="fa-solid fa-right-to-bracket mr-2"></i> Masuk
            </button>
        </form>
    </div>
</body>
</html>
