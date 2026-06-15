<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AkademikController extends Controller
{
    public function index(): View
    {
        return view('akademik::index');
    }

    public function create(): View
    {
        return view('akademik::create');
    }

    public function store()
    {
        return redirect()->route('akademik.index')->with('success', 'Data akademik berhasil disimpan.');
    }

    public function show($id): View
    {
        return view('akademik::show', compact('id'));
    }

    public function edit($id): View
    {
        return view('akademik::edit', compact('id'));
    }

    public function update($id)
    {
        return redirect()->route('akademik.index')->with('success', 'Data akademik berhasil diperbarui.');
    }

    public function destroy($id)
    {
        return redirect()->route('akademik.index')->with('success', 'Data akademik berhasil dihapus.');
    }
}
