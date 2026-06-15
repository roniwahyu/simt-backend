<?php

namespace Modules\Pegawai\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PegawaiController extends Controller
{
    public function index(): View
    {
        return view('pegawai::index');
    }

    public function create(): View
    {
        return view('pegawai::create');
    }

    public function store()
    {
        return redirect()->route('pegawai.index')->with('success', 'Data pegawai berhasil disimpan.');
    }

    public function show($id): View
    {
        return view('pegawai::show', compact('id'));
    }

    public function edit($id): View
    {
        return view('pegawai::edit', compact('id'));
    }

    public function update($id)
    {
        return redirect()->route('pegawai.index')->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function destroy($id)
    {
        return redirect()->route('pegawai.index')->with('success', 'Data pegawai berhasil dihapus.');
    }
}
