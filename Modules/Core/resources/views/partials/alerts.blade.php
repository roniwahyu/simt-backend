@if(session('success'))
    <div class="mb-5 flex items-center p-4 rounded-xl border border-emerald-100 bg-emerald-50/50 backdrop-blur-sm text-emerald-800 shadow-sm transition-all duration-300 animate-fade-in" role="alert">
        <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="ml-3 flex-1 font-medium text-sm">
            {{ session('success') }}
        </div>
    </div>
@endif

@if(session('error'))
    <div class="mb-5 flex items-center p-4 rounded-xl border border-red-100 bg-red-50/50 backdrop-blur-sm text-red-800 shadow-sm transition-all duration-300 animate-fade-in" role="alert">
        <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center text-red-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <div class="ml-3 flex-1 font-medium text-sm">
            {{ session('error') }}
        </div>
    </div>
@endif
