<?php

namespace Modules\Finance\Exports;

use App\Models\Bill;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

/**
 * BillsRecapExport — Export Excel rekap tagihan per siswa
 *
 * Penggunaan:
 *   return Excel::download(new BillsRecapExport($filters), 'rekap_tagihan.xlsx');
 *
 * Filter opsional:
 *   - period (YYYY-MM)
 *   - status (unpaid/partial/paid)
 *   - student_id
 */
class BillsRecapExport implements FromView, WithTitle, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function view(): View
    {
        $query = Bill::with(['student', 'payments']);

        // Filter by period (YYYY-MM)
        if (! empty($this->filters['period'])) {
            $query->where('period', $this->filters['period']);
        }

        // Filter by status
        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        // Filter by student
        if (! empty($this->filters['student_id'])) {
            $query->where('student_id', $this->filters['student_id']);
        }

        $bills = $query->orderBy('period', 'desc')
            ->orderBy('student_id')
            ->get();

        // Hitung ringkasan
        $totalTagihan = (float) $bills->sum('amount');
        $totalDibayar = (float) $bills->sum('paid_amount');
        $totalTunggakan = (float) $bills->sum(function ($bill) {
            return $bill->remaining();
        });
        $jumlahLunas = $bills->where('status', 'paid')->count();
        $jumlahSebagian = $bills->where('status', 'partial')->count();
        $jumlahBelumBayar = $bills->where('status', 'unpaid')->count();

        return view('finance::exports.bills_excel', [
            'bills' => $bills,
            'filters' => $this->filters,
            'totalTagihan' => $totalTagihan,
            'totalDibayar' => $totalDibayar,
            'totalTunggakan' => $totalTunggakan,
            'jumlahLunas' => $jumlahLunas,
            'jumlahSebagian' => $jumlahSebagian,
            'jumlahBelumBayar' => $jumlahBelumBayar,
        ]);
    }

    public function title(): string
    {
        $parts = ['Rekap Tagihan'];
        if (! empty($this->filters['period'])) {
            $parts[] = $this->filters['period'];
        }
        if (! empty($this->filters['status'])) {
            $parts[] = $this->filters['status'];
        }

        return implode(' - ', $parts);
    }
}
