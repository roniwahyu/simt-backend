<?php

namespace Modules\Kantin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class KantinController extends Controller
{
    public function index(): View
    {
        return view('kantin::index');
    }

    public function create(): View
    {
        return view('kantin::create');
    }

    public function store()
    {
        return redirect()->route('kantin.index')->with('success', 'Data kantin berhasil disimpan.');
    }

    public function show($id): View
    {
        return view('kantin::show', compact('id'));
    }

    public function edit($id): View
    {
        return view('kantin::edit', compact('id'));
    }

    public function update($id)
    {
        return redirect()->route('kantin.index')->with('success', 'Data kantin berhasil diperbarui.');
    }

    public function destroy($id)
    {
        return redirect()->route('kantin.index')->with('success', 'Data kantin berhasil dihapus.');
    }
}
