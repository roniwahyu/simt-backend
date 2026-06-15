<?php

namespace Modules\Keuangan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class KeuanganController extends Controller
{
    public function index(): View
    {
        return view('keuangan::index');
    }

    public function create(): View
    {
        return view('keuangan::create');
    }

    public function store()
    {
        return redirect()->route('keuangan.index')->with('success', 'Data keuangan berhasil disimpan.');
    }

    public function show($id): View
    {
        return view('keuangan::show', compact('id'));
    }

    public function edit($id): View
    {
        return view('keuangan::edit', compact('id'));
    }

    public function update($id)
    {
        return redirect()->route('keuangan.index')->with('success', 'Data keuangan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        return redirect()->route('keuangan.index')->with('success', 'Data keuangan berhasil dihapus.');
    }
}
