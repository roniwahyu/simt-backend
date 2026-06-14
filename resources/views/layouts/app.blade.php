<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIMT MVP') — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body{font-family:'Inter',sans-serif;}</style>
    @stack('styles')
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="min-h-screen flex">
        @auth
        <aside class="w-64 bg-slate-900 text-white flex flex-col fixed h-full z-20 transition-transform -translate-x-full md:translate-x-0" id="sidebar">
            <div class="p-4 font-bold text-lg border-b border-slate-700">SIMT MVP</div>
            <nav class="flex-1 overflow-y-auto p-2 space-y-1">
                <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded hover:bg-slate-700 {{ request()->routeIs('dashboard') ? 'bg-slate-700' : '' }}">Dashboard</a>

                @role('superadmin')
                <a href="{{ route('super.dashboard') }}" class="block px-3 py-2 rounded hover:bg-slate-700 {{ request()->routeIs('super.*') ? 'bg-slate-700' : '' }}">Super Admin</a>
                @endrole

                @canany(['view_students','create_students'])
                <a href="{{ route('students.index') }}" class="block px-3 py-2 rounded hover:bg-slate-700 {{ request()->routeIs('students.*') ? 'bg-slate-700' : '' }}">Kesiswaan</a>
                @endcanany

                @canany(['mark_attendance','view_attendance'])
                <a href="{{ route('attendance.index') }}" class="block px-3 py-2 rounded hover:bg-slate-700 {{ request()->routeIs('attendance.*') ? 'bg-slate-700' : '' }}">Presensi</a>
                @endcanany

                @canany(['view_bills','record_payment'])
                <a href="{{ route('finance.bills') }}" class="block px-3 py-2 rounded hover:bg-slate-700 {{ request()->routeIs('finance.*') ? 'bg-slate-700' : '' }}">Keuangan</a>
                @endcanany

                <a href="{{ route('notification.connect') }}" class="block px-3 py-2 rounded hover:bg-slate-700 {{ request()->routeIs('notification.connect') ? 'bg-slate-700' : '' }}">WA Connect</a>
                <a href="{{ route('notification.tools') }}" class="block px-3 py-2 rounded hover:bg-slate-700 {{ request()->routeIs('notification.tools') ? 'bg-slate-700' : '' }}">WA Tools</a>

                <form action="/logout" method="POST" class="mt-4">
                    @csrf
                    <button type="submit" class="w-full text-left px-3 py-2 rounded hover:bg-red-600 text-red-300 hover:text-white">Logout</button>
                </form>
            </nav>
            <div class="p-3 text-xs text-slate-400 border-t border-slate-700">
                {{ auth()->user()->name }}<br>{{ auth()->user()->role_display ?? 'User' }}
            </div>
        </aside>
        <div class="flex-1 md:ml-64">
            <header class="bg-white border-b px-4 py-3 flex items-center justify-between md:hidden">
                <button id="sidebarToggle" class="p-2 rounded hover:bg-gray-100">☰</button>
                <span class="font-semibold">SIMT MVP</span>
            </header>
        @endauth

            <main class="p-4 md:p-6 max-w-7xl mx-auto">
                @if(session('success'))
                    <div class="mb-4 rounded-md bg-emerald-50 text-emerald-700 px-4 py-3 border border-emerald-200">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-3 border border-red-200">{{ session('error') }}</div>
                @endif
                @yield('content')
            </main>

        @auth
        </div>
        @endauth
    </div>

    <script>
        document.getElementById('sidebarToggle')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        });
    </script>
    @stack('scripts')
</body>
</html>
