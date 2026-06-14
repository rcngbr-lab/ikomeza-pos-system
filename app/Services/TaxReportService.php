<?php

namespace App\Services;

use App\Models\Sale;
use Carbon\Carbon;

class TaxReportService
{
    public function report(?Carbon $start, ?Carbon $end, ?int $branchId = null): array
    {
        $base = Sale::query()
            ->revenueBearing()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId));

        if ($start) {
            $base->where('created_at', '>=', $start);
        }

        if ($end) {
            $base->where('created_at', '<=', $end);
        }

        $summary = (clone $base)
            ->selectRaw('COUNT(*) as receipts, COALESCE(SUM(taxable_amount),0) as taxable, COALESCE(SUM(tax),0) as vat, COALESCE(SUM(grand_total),0) as gross')
            ->first();

        $daily = (clone $base)
            ->selectRaw('DATE(created_at) as report_date, COUNT(*) as receipts, COALESCE(SUM(taxable_amount),0) as taxable, COALESCE(SUM(tax),0) as vat, COALESCE(SUM(grand_total),0) as gross')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('report_date')
            ->get();

        return [
            'summary' => [
                'receipts' => (int) ($summary->receipts ?? 0),
                'taxable' => (float) ($summary->taxable ?? 0),
                'vat' => (float) ($summary->vat ?? 0),
                'gross' => (float) ($summary->gross ?? 0),
            ],
            'daily' => $daily,
        ];
    }
}
