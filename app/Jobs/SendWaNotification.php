<?php

namespace App\Jobs;

use App\Models\WaNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWaNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120, 300]; // seconds: 30s, 2m, 5m

    public function __construct(
        public int $tenantId,
        public string $toPhone,
        public string $type,
        public array $payload,
    ) {}

    public function handle(): void
    {
        $gatewayUrl = config('app.wa_gateway_url', 'http://wa-gateway:3001');
        $apiKey = config('app.wa_gateway_api_key', '');

        // Build message text based on type
        $message = $this->buildMessage($this->type, $this->payload);

        // Create log record
        $notif = WaNotification::create([
            'tenant_id' => $this->tenantId,
            'to_phone' => $this->toPhone,
            'type' => $this->type,
            'payload' => $this->payload,
            'status' => 'retrying',
            'attempts' => $this->attempts() + 1,
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
            ])
            ->timeout(30)
            ->post("{$gatewayUrl}/send", [
                'tenantId' => $this->tenantId,
                'to' => $this->toPhone,
                'message' => $message,
            ]);

            if ($response->successful()) {
                $gatewayMsgId = $response->json('messageId');
                $updatedPayload = array_merge($this->payload, ['message_id' => $gatewayMsgId]);
                $notif->update([
                    'status' => 'sent', 
                    'sent_at' => now(),
                    'payload' => $updatedPayload
                ]);
                Log::info("WA sent to {$this->toPhone}", ['tenant' => $this->tenantId, 'type' => $this->type]);
            } else {
                $notif->update(['status' => 'retrying', 'last_error' => $response->body()]);
                throw new \Exception('Gateway returned: ' . $response->body());
            }
        } catch (\Throwable $e) {
            $notif->update(['last_error' => $e->getMessage()]);
            Log::error("WA failed to {$this->toPhone}", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function buildMessage(string $type, array $payload): string
    {
        return match ($type) {
            'attendance' => "[SIMT] Informasi Presensi\n\nAnanda: {$payload['student_name']}\nKelas: {$payload['class']}\nStatus: {$payload['status']}\nTanggal: {$payload['date']}\n\nSemoga harinya menyenangkan. 😊",
            'bill_reminder' => "[SIMT] Pengingat Pembayaran\n\nAnanda: {$payload['student_name']}\nTagihan: {$payload['component']} periode {$payload['period']}\nJumlah: Rp " . number_format($payload['amount'], 0, ',', '.') . "\n\nSilakan melakukan pembayaran ke bendahara sekolah. Terima kasih.",
            'credential' => "[SIMT] Akun Portal Orang Tua\n\nNo. HP: {$payload['phone']}\nPassword: {$payload['password']}\n\nLogin di: https://portal.simt.id\nHarap segera ganti password setelah login.",
            default => $payload['message'] ?? 'Pesan dari SIMT.',
        };
    }
}
