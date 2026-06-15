<?php

namespace Modules\Persuratan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PersuratanController extends Controller
{
    public function index(): View
    {
        return view('persuratan::index');
    }

    public function create(): View
    {
        return view('persuratan::create');
    }

    public function store()
    {
        return redirect()->route('persuratan.index')->with('success', 'Surat berhasil disimpan.');
    }

    public function show($id): View
    {
        return view('persuratan::show', compact('id'));
    }

    public function edit($id): View
    {
        return view('persuratan::edit', compact('id'));
    }

    public function update($id)
    {
        return redirect()->route('persuratan.index')->with('success', 'Surat berhasil diperbarui.');
    }

    public function destroy($id)
    {
        return redirect()->route('persuratan.index')->with('success', 'Surat berhasil dihapus.');
    }
}
