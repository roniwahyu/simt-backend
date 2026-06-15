<?php

namespace Modules\Perpustakaan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PerpustakaanController extends Controller
{
    public function index(): View
    {
        return view('perpustakaan::index');
    }

    public function create(): View
    {
        return view('perpustakaan::create');
    }

    public function store()
    {
        return redirect()->route('perpustakaan.index')->with('success', 'Data perpustakaan berhasil disimpan.');
    }

    public function show($id): View
    {
        return view('perpustakaan::show', compact('id'));
    }

    public function edit($id): View
    {
        return view('perpustakaan::edit', compact('id'));
    }

    public function update($id)
    {
        return redirect()->route('perpustakaan.index')->with('success', 'Data perpustakaan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        return redirect()->route('perpustakaan.index')->with('success', 'Data perpustakaan berhasil dihapus.');
    }
}
