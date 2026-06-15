<?php

namespace Modules\Ppdb\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PpdbController extends Controller
{
    public function index(): View
    {
        return view('ppdb::index');
    }

    public function create(): View
    {
        return view('ppdb::create');
    }

    public function store()
    {
        return redirect()->route('ppdb.index')->with('success', 'Data PPDB berhasil disimpan.');
    }

    public function show($id): View
    {
        return view('ppdb::show', compact('id'));
    }

    public function edit($id): View
    {
        return view('ppdb::edit', compact('id'));
    }

    public function update($id)
    {
        return redirect()->route('ppdb.index')->with('success', 'Data PPDB berhasil diperbarui.');
    }

    public function destroy($id)
    {
        return redirect()->route('ppdb.index')->with('success', 'Data PPDB berhasil dihapus.');
    }
}
