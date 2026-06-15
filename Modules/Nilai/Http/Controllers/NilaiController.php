<?php

namespace Modules\Nilai\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class NilaiController extends Controller
{
    public function index(): View
    {
        return view('nilai::index');
    }

    public function create(): View
    {
        return view('nilai::create');
    }

    public function store()
    {
        return redirect()->route('nilai.index')->with('success', 'Data nilai berhasil disimpan.');
    }

    public function show($id): View
    {
        return view('nilai::show', compact('id'));
    }

    public function edit($id): View
    {
        return view('nilai::edit', compact('id'));
    }

    public function update($id)
    {
        return redirect()->route('nilai.index')->with('success', 'Data nilai berhasil diperbarui.');
    }

    public function destroy($id)
    {
        return redirect()->route('nilai.index')->with('success', 'Data nilai berhasil dihapus.');
    }
}
