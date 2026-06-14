<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Models\Bill;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * FinanceApiController — REST API untuk Portal Ortu (Next.js)
 *
 * Endpoint: GET /api/v1/students/{student}/bills
 *
 * Responsibilities:
 * 1. List tagihan siswa + riwayat pembayaran
 * 2. Ownership check: wali hanya bisa akses data anak sendiri
 * 3. Tenant isolation via global scope + check.tenant.access middleware
 *
 * @see \App\Http\Middleware\CheckTenantAccess
 * @see \App\Http\Middleware\IdentifyTenant
 * @see \App\Traits\BelongsToTenant
 */
class FinanceApiController extends Controller
{
    /**
     * GET /api/v1/students/{student}/bills
     *
     * Response format (Doc 22 §1.1 ApiResponseHelpers convention):
     * {
     *   "success": true,
     *   "message": "Berhasil memuat data tagihan",
     *   "data": {
     *     "student": { id, nis, name },
     *     "bills": [
     *       { id, period, component, amount, paid_amount, discount, status, due_date, payments: [...] }
     *     ],
     *     "summary": { total_tagihan, total_dibayar, total_tunggakan, jumlah_belum_lunas }
     *   }
     * }
     */
    public function index(Request $request, Student $student): JsonResponse
    {
        // Ownership check: wali hanya boleh akses data anak yang ditugaskan
        $user = $request->user();
        if ($user->hasRole('wali') && ! $user->guardianStudents()->where('student_id', $student->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke data tagihan siswa ini.',
                'code' => 'FORBIDDEN_OWNERSHIP',
            ], 403);
        }

        // Ambil semua bills siswa ini (sudah ter-filter global scope tenant)
        $bills = Bill::where('student_id', $student->id)
            ->with(['payments' => function ($q) {
                $q->orderBy('payment_date', 'desc');
            }])
            ->orderBy('period', 'desc')
            ->get();

        // Hitung ringkasan
        $totalTagihan = (float) $bills->sum('amount');
        $totalDibayar = (float) $bills->sum('paid_amount');
        $totalTunggakan = (float) $bills->sum(function ($bill) {
            return $bill->remaining();
        });
        $jumlahBelumLunas = $bills->whereIn('status', ['unpaid', 'partial'])->count();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil memuat data tagihan',
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'nis' => $student->nis,
                    'nisn' => $student->nisn,
                    'name' => $student->name,
                    'class' => $student->classes()->first()?->name ?? null,
                ],
                'bills' => $bills->map(function ($bill) {
                    return [
                        'id' => $bill->id,
                        'period' => $bill->period,
                        'component' => $bill->component,
                        'amount' => (float) $bill->amount,
                        'paid_amount' => (float) $bill->paid_amount,
                        'discount' => (float) $bill->discount,
                        'remaining' => $bill->remaining(),
                        'status' => $bill->status,
                        'status_label' => match ($bill->status) {
                            'paid' => 'Lunas',
                            'partial' => 'Sebagian',
                            'unpaid' => 'Belum Bayar',
                            default => $bill->status,
                        },
                        'due_date' => $bill->due_date?->toDateString(),
                        'is_overdue' => $bill->due_date && $bill->due_date->isPast() && $bill->status !== 'paid',
                        'payments' => $bill->payments->map(function ($payment) {
                            return [
                                'id' => $payment->id,
                                'amount' => (float) $payment->amount,
                                'payment_date' => $payment->payment_date?->toDateString(),
                                'method' => $payment->method,
                                'receipt_no' => $payment->receipt_no,
                            ];
                        }),
                    ];
                }),
                'summary' => [
                    'total_tagihan' => $totalTagihan,
                    'total_dibayar' => $totalDibayar,
                    'total_tunggakan' => $totalTunggakan,
                    'jumlah_belum_lunas' => $jumlahBelumLunas,
                ],
            ],
        ], 200);
    }
}
