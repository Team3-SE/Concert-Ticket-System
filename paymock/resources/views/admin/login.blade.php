<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayMock Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 flex items-center justify-center min-h-screen">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm p-8">
        <div class="text-center mb-6">
            <div class="text-4xl mb-2">🔐</div>
            <h1 class="text-xl font-bold text-gray-800">PayMock Admin</h1>
            <p class="text-gray-500 text-sm">Enter admin password to continue</p>
        </div>

        <form action="{{ route('admin.login.post') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="admin-password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input id="admin-password" type="password" name="password"
                       class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                       required>
                @error('password')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-lg transition">
                Login
            </button>
        </form>
    </div>
</body>
</html>
