<?php

namespace Modules\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\WaNotification;
use App\Support\Tenancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class NotificationController extends Controller
{
    protected string $gatewayUrl;
    protected string $apiKey;
    protected string $callbackSecret;

    public function __construct()
    {
        $this->gatewayUrl = config('app.wa_gateway_url', 'http://localhost:8081');
        $this->apiKey = config('app.wa_gateway_api_key', 'dev-api-key');
        $this->callbackSecret = config('app.wa_callback_secret', 'dev-callback-secret');
    }

    /**
     * Tampilkan halaman koneksi WhatsApp (WA Connect)
     */
    public function connect(Request $request): View
    {
        $tenantId = app(Tenancy::class)->tenantId();

        // Cek status sesi saat ini dari Node.js Gateway
        $status = 'DISCONNECTED';
        $qr = null;
        $number = null;

        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])
            ->timeout(5)
            ->get("{$this->gatewayUrl}/api/tenant/{$tenantId}/session/status");

            if ($response->successful()) {
                $status = $response->json('status', 'DISCONNECTED');
                $number = $response->json('number');
            }
        } catch (\Throwable $e) {
            Log::error("Failed to connect to WA Gateway on connect page: " . $e->getMessage());
            $status = 'GATEWAY_ERROR';
        }

        // Ambil data antrean notifikasi terakhir
        $recentNotifications = WaNotification::latest()->take(10)->get();

        return view('notification::connect', compact('status', 'qr', 'number', 'recentNotifications'));
    }

    /**
     * Jalankan sesi baru untuk menghasilkan QR Code
     */
    public function startSession(Request $request): JsonResponse
    {
        $tenantId = app(Tenancy::class)->tenantId();

        try {
            // Register tenant first if needed
            Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])
            ->post("{$this->gatewayUrl}/api/tenant", [
                'id' => (string) $tenantId,
                'name' => 'Tenant ' . $tenantId,
            ]);

            // Start session
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])
            ->post("{$this->gatewayUrl}/api/tenant/{$tenantId}/session/start");

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'status' => $response->json('status'),
                    'qr' => $response->json('qr'),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gateway error: ' . $response->body(),
            ], 500);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Exception error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Hentikan sesi WhatsApp (Disconnect)
     */
    public function stopSession(Request $request): JsonResponse
    {
        $tenantId = app(Tenancy::class)->tenantId();

        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])
            ->post("{$this->gatewayUrl}/api/tenant/{$tenantId}/session/stop");

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'status' => 'DISCONNECTED',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gateway error: ' . $response->body(),
            ], 500);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Exception error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mendapatkan status sesi secara real-time (untuk polling di frontend)
     */
    public function sessionStatus(Request $request): JsonResponse
    {
        $tenantId = app(Tenancy::class)->tenantId();

        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])
            ->timeout(5)
            ->get("{$this->gatewayUrl}/api/tenant/{$tenantId}/session/status");

            if ($response->successful()) {
                $status = $response->json('status', 'DISCONNECTED');
                $qr = null;

                // Jika statusnya QR_READY, ambil string QR code-nya
                if ($status === 'QR_READY') {
                    $qrResponse = Http::withHeaders([
                        'X-API-Key' => $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get("{$this->gatewayUrl}/api/tenant/{$tenantId}/session/qr");
                    
                    if ($qrResponse->successful()) {
                        $qr = $qrResponse->json('qr');
                    }
                }

                return response()->json([
                    'success' => true,
                    'status' => $status,
                    'qr' => $qr,
                    'number' => $response->json('number'),
                ]);
            }

            return response()->json([
                'success' => false,
                'status' => 'GATEWAY_ERROR',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'status' => 'GATEWAY_ERROR',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Webhook Callback penerima update status dari Gateway Node.js
     */
    public function deliveryCallback(Request $request): JsonResponse
    {
        // Validasi secret callback
        $secret = $request->header('X-Callback-Secret');
        if (!$secret || $secret !== $this->callbackSecret) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized Webhook Callback',
            ], 401);
        }

        $tenantId = $request->input('tenantId');
        $event = $request->input('event');

        // Handle incoming message event from Node.js Gateway
        if ($event === 'message_received') {
            $from = $request->input('from');
            $message = $request->input('message');
            $senderName = $request->input('senderName');
            $messageId = $request->input('messageId');

            $notif = WaNotification::create([
                'tenant_id' => $tenantId,
                'to_phone' => $from,
                'type' => 'incoming',
                'payload' => [
                    'message' => $message,
                    'sender_name' => $senderName,
                    'message_id' => $messageId,
                ],
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            Log::info("Webhook Callback: Incoming WA from {$from} ({$senderName}) stored: {$message}");

            return response()->json([
                'success' => true,
                'message' => "Incoming message from {$from} saved",
            ]);
        }

        $referenceId = $request->input('referenceId');
        $rawStatus = $request->input('status'); // sent, delivered, failed
        $error = $request->input('error');

        // Map status to database enum ['queued', 'sent', 'failed', 'retrying']
        $status = $rawStatus;
        if ($rawStatus === 'delivered') {
            $status = 'sent';
        }

        Log::info("Webhook Callback received for reference {$referenceId}: status={$rawStatus} (mapped to {$status})");

        // Temukan record notifikasi dan perbarui
        if ($referenceId) {
            $notif = WaNotification::withoutGlobalScopes()
                ->where('id', $referenceId)
                ->first();

            if ($notif) {
                $updateData = ['status' => $status];
                if ($rawStatus === 'delivered' || $rawStatus === 'sent') {
                    $updateData['sent_at'] = now();
                }
                if ($error) {
                    $updateData['last_error'] = $error;
                }

                // Simpan WhatsApp messageId ke payload jika ada
                $messageId = $request->input('messageId');
                if ($messageId) {
                    $currentPayload = $notif->payload ?? [];
                    $updateData['payload'] = array_merge($currentPayload, ['message_id' => $messageId]);
                }

                $notif->update($updateData);

                return response()->json([
                    'success' => true,
                    'message' => "Notification ID {$referenceId} updated to {$status}",
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Notification record not found',
        ], 404);
    }

    /**
     * Mengembalikan partial HTML dari tabel notifikasi untuk pembaruan real-time
     */
    public function notificationsTable(Request $request): View
    {
        $recentNotifications = WaNotification::latest()->take(10)->get();
        return view('notification::partials.table-rows', compact('recentNotifications'));
    }
}
