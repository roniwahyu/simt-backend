<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Terbatas — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(3deg); }
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.95); opacity: 0.2; }
            50% { transform: scale(1.05); opacity: 0.35; }
            100% { transform: scale(0.95); opacity: 0.2; }
        }
        @keyframes subtle-drift {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(10px, 15px); }
        }
        .float-anim {
            animation: float 6s ease-in-out infinite;
        }
        .pulse-anim {
            animation: pulse-ring 4s ease-in-out infinite;
        }
        .drift-anim-1 {
            animation: subtle-drift 12s ease-in-out infinite;
        }
        .drift-anim-2 {
            animation: subtle-drift 16s ease-in-out infinite-reverse;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-50 via-slate-50 to-blue-50 text-slate-800 min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    
    <!-- Background Decorative Elements -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-indigo-200 rounded-full mix-blend-multiply filter blur-3xl opacity-40 drift-anim-1"></div>
    <div class="absolute -bottom-45 -right-40 w-96 h-96 bg-blue-200 rounded-full mix-blend-multiply filter blur-3xl opacity-40 drift-anim-2"></div>
    
    <!-- Main Card Container -->
    <div class="bg-white/80 backdrop-blur-xl border border-slate-100 rounded-3xl shadow-xl max-w-lg w-full p-8 md:p-10 text-center relative z-10 transition-all hover:shadow-2xl">
        
        <!-- Glowing Key/Lock SVG Illustration -->
        <div class="relative flex justify-center mb-8">
            <div class="absolute w-36 h-36 bg-amber-100 rounded-full pulse-anim z-0"></div>
            <div class="relative float-anim z-10 bg-gradient-to-br from-amber-400 to-amber-500 p-6 rounded-3xl shadow-lg border border-amber-300">
                <!-- A beautifully stylized key SVG -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-14 h-14 text-white">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                </svg>
            </div>
        </div>

        <!-- Copy / Message -->
        <div class="space-y-4 mb-8">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 border border-amber-100 text-amber-800 text-xs font-semibold uppercase tracking-wider">
                Akses Terbatas
            </div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">
                Halaman Memerlukan Akses Khusus
            </h1>
            <p class="text-slate-600 leading-relaxed text-sm md:text-base">
                Halo! Akun Anda saat ini belum memiliki hak akses (izin) yang diperlukan untuk membuka halaman ini. 
                Jangan khawatir, hal ini biasa terjadi jika ada pembaruan peran atau pembatasan modul.
            </p>
            <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl text-xs text-left text-slate-500 space-y-1">
                <p class="font-medium text-slate-700">Rekomendasi tindakan:</p>
                <ul class="list-disc pl-4 space-y-1">
                    <li>Hubungi admin sekolah untuk memeriksa hak akses Anda.</li>
                    <li>Pastikan Anda telah login menggunakan akun yang benar.</li>
                </ul>
            </div>
        </div>

        <!-- Interactive Actions -->
        <div class="flex flex-col sm:flex-row gap-3 justify-center items-center">
            @auth
                <a href="{{ route('dashboard') }}" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-indigo-600 to-indigo-500 text-white rounded-xl font-semibold text-sm hover:from-indigo-700 hover:to-indigo-600 shadow-md hover:shadow-lg transition-all focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Kembali ke Dashboard
                </a>
            @else
                <a href="/" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-indigo-600 to-indigo-500 text-white rounded-xl font-semibold text-sm hover:from-indigo-700 hover:to-indigo-600 shadow-md hover:shadow-lg transition-all focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Kembali ke Beranda
                </a>
            @endauth
            
            <a href="javascript:history.back()" class="w-full sm:w-auto px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-semibold text-sm transition-all">
                Kembali Halaman Sebelumnya
            </a>
        </div>
        
        <!-- Footer Branding -->
        <div class="mt-8 pt-6 border-t border-slate-100 text-xs text-slate-400">
            SIMT MVP &bull; Kode Status: 403
        </div>

    </div>
</body>
</html>
