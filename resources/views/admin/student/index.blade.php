@extends('layouts.app')
@section('title', 'Kesiswaan')
@section('content')
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Data Siswa</h1>
        <div class="flex gap-2">
            <a href="{{ route('students.import.form') }}" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium">Import Excel</a>
            @can('create_students')
            <a href="{{ route('students.create') }}" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium">+ Tambah Siswa</a>
            @endcan
        </div>
    </div>

    <form method="GET" class="bg-white rounded-lg shadow border border-gray-100 p-4 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs text-gray-500 mb-1">Cari (nama / NIS / NISN)</label>
            <input type="text" name="search" value="{{ request('search') }}" class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm" placeholder="Ketik untuk mencari...">
        </div>
        <div class="min-w-[180px]">
            <label class="block text-xs text-gray-500 mb-1">Kelas</label>
            <select name="class_id" class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                <option value="">Semua Kelas</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected(request('class_id') == $class->id)>{{ $class->name }} ({{ $class->schoolYear->name ?? '-' }})</option>
                @endforeach
            </select>
        </div>
        <button class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-sm">Filter</button>
        @if(request()->hasAny(['search','class_id']))
            <a href="{{ route('students.index') }}" class="px-4 py-2 rounded-lg border text-sm text-gray-600">Reset</a>
        @endif
    </form>

    <div class="bg-white rounded-lg shadow border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500">
                    <tr>
                        <th class="px-4 py-3">NIS</th>
                        <th class="px-4 py-3">NISN</th>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">JK</th>
                        <th class="px-4 py-3">Kelas</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($students as $student)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">{{ $student->nis ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $student->nisn ?? '-' }}</td>
                            <td class="px-4 py-3 font-medium">{{ $student->name }}</td>
                            <td class="px-4 py-3">{{ $student->gender ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $student->classes->first()->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs {{ $student->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">{{ $student->status }}</span>
                            </td>
                            <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                @can('edit_students')
                                <a href="{{ route('students.edit', $student) }}" class="text-blue-600 hover:underline">Edit</a>
                                @endcan
                                @can('delete_students')
                                <form action="{{ route('students.destroy', $student) }}" method="POST" class="inline" onsubmit="return confirm('Hapus siswa ini?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline">Hapus</button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">Belum ada data siswa.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100">{{ $students->links() }}</div>
    </div>
</div>
@endsection
