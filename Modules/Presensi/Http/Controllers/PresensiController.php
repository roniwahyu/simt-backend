<?php

namespace Modules\Presensi\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PresensiController extends Controller
{
    public function index(): View
    {
        return view('presensi::index');
    }

    public function create(): View
    {
        return view('presensi::create');
    }

    public function store()
    {
        return redirect()->route('presensi.index')->with('success', 'Data presensi berhasil disimpan.');
    }

    public function show($id): View
    {
        return view('presensi::show', compact('id'));
    }

    public function edit($id): View
    {
        return view('presensi::edit', compact('id'));
    }

    public function update($id)
    {
        return redirect()->route('presensi.index')->with('success', 'Data presensi berhasil diperbarui.');
    }

    public function destroy($id)
    {
        return redirect()->route('presensi.index')->with('success', 'Data presensi berhasil dihapus.');
    }
}
