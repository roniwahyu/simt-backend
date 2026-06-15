<?php

namespace Modules\Berita\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class BeritaController extends Controller
{
    public function index(): View
    {
        return view('berita::index');
    }

    public function create(): View
    {
        return view('berita::create');
    }

    public function store()
    {
        return redirect()->route('berita.index')->with('success', 'Berita berhasil disimpan.');
    }

    public function show($id): View
    {
        return view('berita::show', compact('id'));
    }

    public function edit($id): View
    {
        return view('berita::edit', compact('id'));
    }

    public function update($id)
    {
        return redirect()->route('berita.index')->with('success', 'Berita berhasil diperbarui.');
    }

    public function destroy($id)
    {
        return redirect()->route('berita.index')->with('success', 'Berita berhasil dihapus.');
    }
}
