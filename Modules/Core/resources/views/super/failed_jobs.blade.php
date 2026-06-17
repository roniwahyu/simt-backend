@extends('layouts.app')
@section('title', 'Failed Queue Jobs — Super Admin')
@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Failed Queue Jobs</h1>
        <div class="flex space-x-2">
            <a href="{{ route('super.dashboard') }}" class="px-4 py-2 rounded border text-sm bg-white hover:bg-gray-50 text-gray-700">Kembali ke Dashboard</a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded text-sm font-medium">
            {{ session('error') }}
        </div>
    @endif

    <!-- Jobs Table -->
    <div class="bg-white rounded-lg shadow border border-gray-100">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase font-medium text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left w-12">ID</th>
                        <th class="px-4 py-3 text-left w-32">Queue</th>
                        <th class="px-4 py-3 text-left">Job / Payload</th>
                        <th class="px-4 py-3 text-left w-48">Failed At</th>
                        <th class="px-4 py-3 text-left">Error Message</th>
                        <th class="px-4 py-3 text-center w-40">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($failedJobs as $job)
                        @php
                            $payload = json_decode($job->payload, true);
                            $jobName = $payload['displayName'] ?? ($payload['data']['commandName'] ?? 'Unknown Job');
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-400">#{{ $job->id }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs font-semibold">{{ $job->queue }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-gray-800 block truncate max-w-[250px]" title="{{ $jobName }}">{{ class_basename($jobName) }}</span>
                                <span class="text-xs text-gray-400 font-mono block truncate max-w-[250px]">{{ $jobName }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-500">
                                {{ \Carbon\Carbon::parse($job->failed_at)->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s') }} WIB
                            </td>
                            <td class="px-4 py-3 max-w-md">
                                <div class="text-xs text-red-600 font-mono line-clamp-2 hover:line-clamp-none cursor-pointer bg-red-50 p-1 rounded border border-red-100" title="Klik untuk meluaskan">
                                    {{ Str::limit($job->exception, 300) }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center space-x-2">
                                    <!-- Retry Button -->
                                    <form action="{{ route('super.failed-jobs.retry', $job->id) }}" method="POST" onsubmit="return confirm('Retry job ini?')">
                                        @csrf
                                        <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded text-xs font-medium hover:bg-blue-700 transition">
                                            Retry
                                        </button>
                                    </form>
                                    
                                    <!-- Delete Button -->
                                    <form action="{{ route('super.failed-jobs.delete', $job->id) }}" method="POST" onsubmit="return confirm('Hapus record job ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1 bg-red-50 text-red-600 rounded text-xs font-medium hover:bg-red-100 border border-red-200 transition">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-emerald-600 bg-emerald-50/30">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <svg class="w-8 h-8 text-emerald-500 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="font-medium text-emerald-800">Semua sistem berjalan lancar!</span>
                                    <span class="text-xs text-emerald-600">Tidak ada failed queue jobs yang tercatat saat ini.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($failedJobs->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $failedJobs->links() }}</div>
        @endif
    </div>
</div>
@endsection
