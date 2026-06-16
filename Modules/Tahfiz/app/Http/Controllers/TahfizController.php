<?php

namespace Modules\Tahfiz\Http\Controllers;

use Illuminate\Routing\Controller;

class TahfizController extends Controller
{
    public function index()
    {
        $tenant = app(\App\Support\Tenancy::class)->tenant();
        return view('tahfiz::index', compact('tenant'));
    }
}
