<?php

namespace Modules\Dapur\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DapurController extends Controller
{
    public function index(): View
    {
        return view('dapur::index');
    }

    public function create(): View
    {
        return view('dapur::create');
    }

    public function store()
    {
        return redirect()->route('dapur.index')->with('success', 'Data dapur berhasil disimpan.');
    }

    public function show($id): View
    {
        return view('dapur::show', compact('id'));
    }

    public function edit($id): View
    {
        return view('dapur::edit', compact('id'));
    }

    public function update($id)
    {
        return redirect()->route('dapur.index')->with('success', 'Data dapur berhasil diperbarui.');
    }

    public function destroy($id)
    {
        return redirect()->route('dapur.index')->with('success', 'Data dapur berhasil dihapus.');
    }
}
