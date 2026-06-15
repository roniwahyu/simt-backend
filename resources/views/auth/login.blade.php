<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SIMT MVP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0f7ff',
                            100: '#e0effe',
                            500: '#0284c7',
                            600: '#0369a1',
                            700: '#075985',
                            900: '#0c4a6e',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
        }
        .animate-pulse-slow {
            animation: pulse 8s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body class="bg-[#0b0f19] min-h-screen flex items-center justify-center p-4 md:p-8 relative overflow-x-hidden antialiased">
    <!-- Mesh Glow Background Effects -->
    <div class="absolute top-[-20%] left-[-10%] w-[500px] h-[500px] rounded-full bg-blue-500/10 blur-[100px] pointer-events-none animate-pulse-slow"></div>
    <div class="absolute bottom-[-20%] right-[-10%] w-[600px] h-[600px] rounded-full bg-violet-500/10 blur-[130px] pointer-events-none animate-pulse-slow" style="animation-delay: 2s;"></div>
    <div class="absolute top-[30%] right-[20%] w-[350px] h-[350px] rounded-full bg-sky-500/5 blur-[90px] pointer-events-none"></div>

    <div class="w-full max-w-5xl grid grid-cols-1 lg:grid-cols-12 gap-8 relative z-10 items-stretch">
        
        <!-- Left Side: Brand & Login Form Card -->
        <div class="lg:col-span-5 bg-slate-900/60 backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-800/80 p-8 flex flex-col justify-between transition-all duration-300 hover:border-slate-700/50">
            <div>
                <!-- Brand Header -->
                <div class="mb-8">
                    <div class="flex items-center space-x-2">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-sky-400 flex items-center justify-center shadow-lg shadow-blue-500/20">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-xl font-bold bg-gradient-to-r from-white via-slate-100 to-slate-400 bg-clip-text text-transparent">SIMT MVP</div>
                            <div class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold">Manajemen Terpadu</div>
                        </div>
                    </div>
                </div>

                <!-- Form Section Title -->
                <div class="space-y-1.5 mb-6">
                    <h2 class="text-2xl font-bold text-white tracking-tight">Selamat Datang</h2>
                    <p class="text-sm text-slate-400">Silakan masuk untuk mengelola portal sekolah Anda.</p>
                </div>

                @if($errors->any())
                <div class="mb-5 bg-rose-500/10 border border-rose-500/20 text-rose-300 px-4 py-3 rounded-xl text-sm flex items-center space-x-3 transition-all duration-300">
                    <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
                @endif

                <!-- Login Form -->
                <form action="/login" method="POST" class="space-y-4">
                    @csrf
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">No. HP / Email</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-slate-500 group-focus-within:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <input type="text" name="login" id="login-input" required 
                                class="w-full bg-slate-950/60 border border-slate-800 rounded-xl pl-10 pr-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition duration-300 text-sm" 
                                placeholder="0852xxxxxxxx / email@simt.id">
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Password</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-slate-500 group-focus-within:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input type="password" name="password" id="password-input" required 
                                class="w-full bg-slate-950/60 border border-slate-800 rounded-xl pl-10 pr-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition duration-300 text-sm" 
                                placeholder="••••••••">
                        </div>
                    </div>

                    <button type="submit" id="submit-btn" 
                        class="w-full bg-gradient-to-r from-blue-600 to-sky-500 hover:from-blue-500 hover:to-sky-400 text-white font-semibold py-3 rounded-xl transition duration-300 shadow-lg shadow-blue-600/20 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-blue-500/50 text-sm">
                        Masuk Sistem
                    </button>
                </form>
            </div>

            <!-- Footer info -->
            <div class="text-[11px] text-center text-slate-600 mt-8">
                SIMT Platform &copy; 2026. Hak Cipta Dilindungi.
            </div>
        </div>

        <!-- Right Side: Interactive Quick-Fill Demo Dashboard -->
        <div class="lg:col-span-7 bg-slate-900/40 backdrop-blur-md rounded-2xl border border-slate-800/80 p-8 flex flex-col justify-between transition-all duration-300 hover:border-slate-700/50">
            <div>
                <div class="mb-6">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-500/10 border border-blue-500/25 text-blue-400 mb-3">
                        Demo Sandbox Environment
                    </span>
                    <h3 class="text-xl font-bold text-white tracking-tight">Akses Cepat Demo Akun</h3>
                    <p class="text-sm text-slate-400 mt-1">Klik salah satu profil akun di bawah ini untuk mengisi kredensial login secara otomatis.</p>
                </div>

                <!-- Demo Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <!-- Card 1: Superadmin -->
                    <div onclick="selectAccount('vendor@simt.id', 'password', this)" 
                        class="demo-card group cursor-pointer transition-all duration-300 border border-slate-800/80 hover:border-red-500 bg-slate-950/40 hover:bg-slate-900/60 p-4 rounded-xl flex flex-col justify-between space-y-3">
                        <div class="flex items-start justify-between">
                            <div class="w-10 h-10 rounded-lg bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-400 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-semibold bg-red-500/10 border border-red-500/20 text-red-400">
                                Superadmin
                            </span>
                        </div>
                        <div>
                            <div class="font-bold text-slate-200 group-hover:text-white transition-colors text-sm">Superadmin Lintas Tenant</div>
                            <div class="text-xs text-slate-400 mt-1 truncate">vendor@simt.id</div>
                            <div class="text-[10px] text-slate-500 mt-2 font-medium">Akses seluruh tenant dan konfigurasi global</div>
                        </div>
                    </div>

                    <!-- Card 2: Kepala Madrasah -->
                    <div onclick="selectAccount('hasan@mts-alhikmah.sch.id', 'password', this)" 
                        class="demo-card group cursor-pointer transition-all duration-300 border border-slate-800/80 hover:border-purple-500 bg-slate-950/40 hover:bg-slate-900/60 p-4 rounded-xl flex flex-col justify-between space-y-3">
                        <div class="flex items-start justify-between">
                            <div class="w-10 h-10 rounded-lg bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
                                </svg>
                            </div>
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-semibold bg-purple-500/10 border border-purple-500/20 text-purple-400">
                                Kepsek
                            </span>
                        </div>
                        <div>
                            <div class="font-bold text-slate-200 group-hover:text-white transition-colors text-sm">Kepala Madrasah (T1)</div>
                            <div class="text-xs text-slate-400 mt-1 truncate">hasan@mts-alhikmah.sch.id</div>
                            <div class="text-[10px] text-slate-500 mt-2 font-medium">Rekap presensi, laporan, & keuangan</div>
                        </div>
                    </div>

                    <!-- Card 3: Admin Sekolah -->
                    <div onclick="selectAccount('ahmad@mts-alhikmah.sch.id', 'password', this)" 
                        class="demo-card group cursor-pointer transition-all duration-300 border border-slate-800/80 hover:border-blue-500 bg-slate-950/40 hover:bg-slate-900/60 p-4 rounded-xl flex flex-col justify-between space-y-3">
                        <div class="flex items-start justify-between">
                            <div class="w-10 h-10 rounded-lg bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-semibold bg-blue-500/10 border border-blue-500/20 text-blue-400">
                                Admin Sekolah
                            </span>
                        </div>
                        <div>
                            <div class="font-bold text-slate-200 group-hover:text-white transition-colors text-sm">Admin Sekolah (T1)</div>
                            <div class="text-xs text-slate-400 mt-1 truncate">ahmad@mts-alhikmah.sch.id</div>
                            <div class="text-[10px] text-slate-500 mt-2 font-medium">MTs Al-Hikmah — Akses Penuh Modul</div>
                        </div>
                    </div>

                    <!-- Card 4: Tata Usaha (TU) -->
                    <div onclick="selectAccount('budi@mts-alhikmah.sch.id', 'password', this)" 
                        class="demo-card group cursor-pointer transition-all duration-300 border border-slate-800/80 hover:border-cyan-500 bg-slate-950/40 hover:bg-slate-900/60 p-4 rounded-xl flex flex-col justify-between space-y-3">
                        <div class="flex items-start justify-between">
                            <div class="w-10 h-10 rounded-lg bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                </svg>
                            </div>
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-semibold bg-cyan-500/10 border border-cyan-500/20 text-cyan-400">
                                Tata Usaha
                            </span>
                        </div>
                        <div>
                            <div class="font-bold text-slate-200 group-hover:text-white transition-colors text-sm">Tata Usaha (T1)</div>
                            <div class="text-xs text-slate-400 mt-1 truncate">budi@mts-alhikmah.sch.id</div>
                            <div class="text-[10px] text-slate-500 mt-2 font-medium">Kelola siswa, absensi, & WhatsApp</div>
                        </div>
                    </div>

                    <!-- Card 5: Bendahara -->
                    <div onclick="selectAccount('farhan@mts-alhikmah.sch.id', 'password', this)" 
                        class="demo-card group cursor-pointer transition-all duration-300 border border-slate-800/80 hover:border-indigo-500 bg-slate-950/40 hover:bg-slate-900/60 p-4 rounded-xl flex flex-col justify-between space-y-3">
                        <div class="flex items-start justify-between">
                            <div class="w-10 h-10 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-semibold bg-indigo-500/10 border border-indigo-500/20 text-indigo-400">
                                Bendahara
                            </span>
                        </div>
                        <div>
                            <div class="font-bold text-slate-200 group-hover:text-white transition-colors text-sm">Bendahara (T1)</div>
                            <div class="text-xs text-slate-400 mt-1 truncate">farhan@mts-alhikmah.sch.id</div>
                            <div class="text-[10px] text-slate-500 mt-2 font-medium">Kelola SPP, pembayaran, & invoice</div>
                        </div>
                    </div>

                    <!-- Card 6: Guru -->
                    <div onclick="selectAccount('siti@mts-alhikmah.sch.id', 'password', this)" 
                        class="demo-card group cursor-pointer transition-all duration-300 border border-slate-800/80 hover:border-emerald-500 bg-slate-950/40 hover:bg-slate-900/60 p-4 rounded-xl flex flex-col justify-between space-y-3">
                        <div class="flex items-start justify-between">
                            <div class="w-10 h-10 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
                                Guru
                            </span>
                        </div>
                        <div>
                            <div class="font-bold text-slate-200 group-hover:text-white transition-colors text-sm">Guru / Wali Kelas (T1)</div>
                            <div class="text-xs text-slate-400 mt-1 truncate">siti@mts-alhikmah.sch.id</div>
                            <div class="text-[10px] text-slate-500 mt-2 font-medium">Input presensi kelas & lihat siswa</div>
                        </div>
                    </div>

                    <!-- Card 7: Wali Murid -->
                    <div onclick="selectAccount('628520000001', 'password', this)" 
                        class="demo-card group cursor-pointer transition-all duration-300 border border-slate-800/80 hover:border-amber-500 bg-slate-950/40 hover:bg-slate-900/60 p-4 rounded-xl flex flex-col justify-between space-y-3 md:col-span-2">
                        <div class="flex items-start justify-between">
                            <div class="w-10 h-10 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-semibold bg-amber-500/10 border border-amber-500/20 text-amber-400">
                                Wali Murid
                            </span>
                        </div>
                        <div>
                            <div class="font-bold text-slate-200 group-hover:text-white transition-colors text-sm">Wali Murid (T1)</div>
                            <div class="text-xs text-slate-400 mt-1 truncate">No. HP: 628520000001</div>
                            <div class="text-[10px] text-slate-500 mt-2 font-medium">Wali dari Muhammad Rizki (MTs Al-Hikmah)</div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Hint indicator -->
            <div class="mt-6 flex items-center space-x-2 text-xs text-slate-500 bg-slate-950/30 border border-slate-800/50 p-3 rounded-lg">
                <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Password semua akun demo adalah <strong class="text-slate-300">password</strong>. Klik akun untuk langsung mengisi form.</span>
            </div>
        </div>

    </div>

    <!-- Quick Autofill Handler -->
    <script>
        function selectAccount(username, password, cardElement) {
            // Fill inputs
            const loginInput = document.getElementById('login-input');
            const passwordInput = document.getElementById('password-input');
            loginInput.value = username;
            passwordInput.value = password;

            // Reset styles on all cards
            document.querySelectorAll('.demo-card').forEach(c => {
                c.classList.remove('border-blue-500', 'bg-blue-950/20', 'ring-1', 'ring-blue-500/30');
                c.classList.add('border-slate-800/80', 'bg-slate-950/40');
            });

            // Add active styles to selected card
            cardElement.classList.remove('border-slate-800/80', 'bg-slate-950/40');
            cardElement.classList.add('border-blue-500', 'bg-blue-950/20', 'ring-1', 'ring-blue-500/30');

            // Add visual scale animation on click
            cardElement.classList.add('scale-[1.02]');
            setTimeout(() => {
                cardElement.classList.remove('scale-[1.02]');
            }, 150);

            // Add a visual flash effect to submit button to invite user to submit
            const submitBtn = document.getElementById('submit-btn');
            submitBtn.classList.remove('from-blue-600', 'to-sky-500');
            submitBtn.classList.add('from-blue-500', 'to-sky-400', 'ring-4', 'ring-blue-500/30');
            setTimeout(() => {
                submitBtn.classList.remove('ring-4', 'ring-blue-500/30');
                submitBtn.classList.add('from-blue-600', 'to-sky-500');
            }, 600);
        }
    </script>
</body>
</html>
