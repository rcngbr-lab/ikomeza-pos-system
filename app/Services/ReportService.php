<?php

namespace App\Services;

use App\Models\Sale;

class ReportService
{
    public function salesSummary()
    {
        return [

            'total_sales' => Sale::revenueBearing()->sum('grand_total'),

            'transactions' => Sale::revenueBearing()->count(),

            'today_sales' => Sale::revenueBearing()->whereDate(
                'created_at',
                today()
            )->sum('grand_total'),

            'monthly_sales' => Sale::revenueBearing()->whereMonth(
                'created_at',
                now()->month
            )->sum('grand_total')

        ];
    }
}
