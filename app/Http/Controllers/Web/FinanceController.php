<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\SendWaNotification;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\SchoolYear;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;

class FinanceController extends Controller
{
    public function bills(Request $request): View
    {
        $query = Bill::with('student')->orderBy('period', 'desc');

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('period')) {
            $query->where('period', $request->input('period'));
        }

        $bills = $query->paginate(50)->withQueryString();
        $students = Student::select('id', 'name')->get();

        return view('admin.finance.bills', compact('bills', 'students'));
    }

    public function generateBills(Request $request): RedirectResponse
    {
        $request->validate([
            'period' => 'required|date_format:Y-m',
            'component' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'nullable|date',
        ]);

        $tenant = app('currentTenant');
        $activeYear = SchoolYear::where('is_active', true)->first();
        if (! $activeYear) {
            return back()->with('error', 'Tidak ada tahun ajaran aktif.');
        }

        // [2026-06-14 | AG] Ambil status auto_notify dari input form
        $autoNotify = $request->boolean('auto_notify');
        
        // [2026-06-14 | AG] Eager load guardians jika auto_notify aktif untuk menghindari N+1 query
        $studentsQuery = Student::where('status', 'active');
        if ($autoNotify) {
            $studentsQuery->with('guardians');
        }
        $students = $studentsQuery->get();

        $count = 0;
        $notifCount = 0;
        foreach ($students as $student) {
            $bill = Bill::create([
                'tenant_id' => $tenant->id,
                'student_id' => $student->id,
                'period' => $request->input('period'),
                'component' => $request->input('component'),
                'amount' => $request->input('amount'),
                'due_date' => $request->input('due_date'),
            ]);
            $count++;

            // [2026-06-14 | AG] Kirim notifikasi WA ke wali murid otomatis jika diset
            if ($autoNotify) {
                foreach ($student->guardians as $guardian) {
                    if (!empty($guardian->phone)) {
                        SendWaNotification::dispatch(
                            $tenant->id,
                            $guardian->phone,
                            'bill_reminder',
                            [
                                'student_name' => $student->name,
                                'component' => $bill->component,
                                'period' => $bill->period,
                                'amount' => $bill->remaining(),
                            ]
                        )->onQueue('wa');
                        $notifCount++;
                    }
                }
            }
        }

        $msg = "Tagihan {$count} siswa berhasil dibuat.";
        if ($autoNotify) {
            $msg .= " Dan {$notifCount} notifikasi WA diantrikan.";
        }

        return redirect()->route('finance.bills')->with('success', $msg);
    }

    public function recordPayment(Request $request, Bill $bill): RedirectResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'method' => 'required|in:cash,transfer',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $tenant = app('currentTenant');

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'bill_id' => $bill->id,
            'student_id' => $bill->student_id,
            'amount' => $request->input('amount'),
            'payment_date' => $request->input('payment_date'),
            'method' => $request->input('method'),
            'reference' => $request->input('reference'),
            'receipt_no' => $this->generateReceiptNo($tenant->id),
            'recorded_by' => $request->user()->id,
            'notes' => $request->input('notes'),
        ]);

        // Update bill paid amount and status
        $bill->paid_amount += $request->input('amount');
        $bill->updateStatus();

        return redirect()->route('finance.bills')->with('success', 'Pembayaran tercatat. Kwitansi tersedia.');
    }

    public function printReceipt(Payment $payment): \Illuminate\Http\Response
    {
        $pdf = Pdf::loadView('pdf.receipt', ['payment' => $payment, 'tenant' => app('currentTenant')]);
        return $pdf->stream('Kwitansi-' . $payment->receipt_no . '.pdf');
    }

    public function sendReminders(Request $request): RedirectResponse
    {
        $request->validate([
            'bill_ids' => 'required|array',
            'bill_ids.*' => 'exists:bills,id',
        ]);

        $bills = Bill::with('student.guardians')->whereIn('id', $request->input('bill_ids'))->get();
        $tenant = app('currentTenant');
        $queued = 0;

        foreach ($bills as $bill) {
            foreach ($bill->student->guardians as $guardian) {
                SendWaNotification::dispatch(
                    $tenant->id,
                    $guardian->phone,
                    'bill_reminder',
                    [
                        'student_name' => $bill->student->name,
                        'component' => $bill->component,
                        'period' => $bill->period,
                        'amount' => $bill->remaining(),
                    ]
                )->onQueue('wa');
                $queued++;
            }
        }

        return redirect()->route('finance.bills')->with('success', "{$queued} pengingat WA diantrikan.");
    }

    private function generateReceiptNo(int $tenantId): string
    {
        $seq = Payment::forTenant($tenantId)->whereYear('created_at', now()->year)->count() + 1;
        return 'KW/' . $tenantId . '/' . now()->year . '/' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
