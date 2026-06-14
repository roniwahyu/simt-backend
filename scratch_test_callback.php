<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$response = Http::withHeaders(['X-Callback-Secret' => 'dev-callback-secret'])
    ->post('http://127.0.0.1:8000/api/v1/wa/delivery-callback', [
        'tenantId' => 1,
        'event' => 'message_received',
        'from' => '6281331711385',
        'message' => 'Halo Admin, terima kasih atas infonya.',
    ]);

echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n";
