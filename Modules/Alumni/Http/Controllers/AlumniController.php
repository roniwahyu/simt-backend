<?php

namespace Modules\Alumni\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AlumniController extends Controller
{
    public function index(): View
    {
        return view('alumni::index');
    }

    public function create(): View
    {
        return view('alumni::create');
    }

    public function store()
    {
        return redirect()->route('alumni.index')->with('success', 'Data alumni berhasil disimpan.');
    }

    public function show($id): View
    {
        return view('alumni::show', compact('id'));
    }

    public function edit($id): View
    {
        return view('alumni::edit', compact('id'));
    }

    public function update($id)
    {
        return redirect()->route('alumni.index')->with('success', 'Data alumni berhasil diperbarui.');
    }

    public function destroy($id)
    {
        return redirect()->route('alumni.index')->with('success', 'Data alumni berhasil dihapus.');
    }
}
