<?php

namespace Modules\Pendaftaran\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PendaftaranController extends Controller
{
    public function index(): View
    {
        return view('pendaftaran::index');
    }

    public function create(): View
    {
        return view('pendaftaran::create');
    }

    public function store()
    {
        return redirect()->route('pendaftaran.index')->with('success', 'Pendaftaran berhasil disimpan.');
    }

    public function show($id): View
    {
        return view('pendaftaran::show', compact('id'));
    }

    public function edit($id): View
    {
        return view('pendaftaran::edit', compact('id'));
    }

    public function update($id)
    {
        return redirect()->route('pendaftaran.index')->with('success', 'Pendaftaran berhasil diperbarui.');
    }

    public function destroy($id)
    {
        return redirect()->route('pendaftaran.index')->with('success', 'Pendaftaran berhasil dihapus.');
    }
}
