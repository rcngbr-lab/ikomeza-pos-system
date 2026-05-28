<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AuditLog;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with([

            'user'

        ]);

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if ($request->search) {

            $query->where(function ($q) use ($request) {

                $q->where(

                    'action',
                    'like',
                    '%' . $request->search . '%'

                )

                ->orWhere(

                    'model',
                    'like',
                    '%' . $request->search . '%'

                )

                ->orWhere(

                    'description',
                    'like',
                    '%' . $request->search . '%'

                );

            });
        }

        /*
        |--------------------------------------------------------------------------
        | FILTERS
        |--------------------------------------------------------------------------
        */

        if ($request->filter == 'today') {

            $query->whereDate(

                'created_at',
                today()

            );
        }

        elseif ($request->filter == 'weekly') {

            $query->whereBetween(

                'created_at',

                [

                    now()->startOfWeek(),
                    now()->endOfWeek()

                ]

            );
        }

        elseif ($request->filter == 'monthly') {

            $query->whereMonth(

                'created_at',
                now()->month

            )

            ->whereYear(

                'created_at',
                now()->year

            );
        }

        /*
        |--------------------------------------------------------------------------
        | DATE RANGE
        |--------------------------------------------------------------------------
        */

        if ($request->start_date) {

            $query->whereDate(

                'created_at',
                '>=',
                $request->start_date

            );
        }

        if ($request->end_date) {

            $query->whereDate(

                'created_at',
                '<=',
                $request->end_date

            );
        }

        $logs = $query

            ->latest()

            ->paginate(20)

            ->withQueryString();

        return view(

            'audit_logs.index',

            compact('logs')

        );
    }
}