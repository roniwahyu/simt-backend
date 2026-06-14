<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SIMT MVP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body{font-family:'Inter',sans-serif;}</style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm bg-white rounded-xl shadow-lg border border-gray-100 p-6 space-y-4">
        <div class="text-center">
            <div class="text-2xl font-bold text-blue-700">SIMT MVP</div>
            <div class="text-sm text-gray-500">Sistem Informasi Manajemen Terpadu</div>
        </div>
        @if($errors->any())
        <div class="bg-red-50 text-red-700 px-3 py-2 rounded text-sm">{{ $errors->first() }}</div>
        @endif
        <form action="/login" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">No. HP / Email</label>
                <input type="text" name="login" required class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0812xxxx / email@domain.id">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="••••••••">
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded-lg transition">Masuk</button>
        </form>
        <div class="text-xs text-center text-gray-400">
            Demo: vendor@simt.id / password (Super Admin)<br>
            628123456010 / password (Admin Sekolah MTs Al-Hikmah)
        </div>
    </div>
</body>
</html>
