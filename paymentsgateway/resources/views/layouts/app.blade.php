<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayMock Gateway – @yield('title', 'Payment')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Top bar -->
    <div class="bg-indigo-700 text-white text-xs px-4 py-1 flex items-center gap-2">
        <span class="font-bold">🔒 PayMock</span>
        <span class="opacity-60">Simulated Payment Gateway – Not a real payment</span>
    </div>

    <main class="container mx-auto max-w-2xl px-4 py-8">
        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-800 rounded">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-800 rounded">{{ session('error') }}</div>
        @endif
        @if(session('info'))
            <div class="mb-4 p-3 bg-blue-100 border border-blue-300 text-blue-800 rounded">{{ session('info') }}</div>
        @endif
        @yield('content')
    </main>

    <footer class="text-center text-xs text-gray-400 py-4">
        PayMock v1.0 – For development &amp; testing only
    </footer>
</body>
</html>
