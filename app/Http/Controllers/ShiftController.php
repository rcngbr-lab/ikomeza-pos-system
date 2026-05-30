<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Shift;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function openForm()
    {
        $openShift = $this->activeShift();

        if ($openShift) {
            return redirect()->route('shifts.current');
        }

        return view('shifts.open');
    }

    public function open(Request $request)
    {
        $request->validate([
            'opening_cash' => ['required', 'numeric', 'min:0'],
        ]);

        if ($this->activeShift()) {
            return redirect()
                ->route('shifts.current')
                ->with('error', 'You already have an open shift.');
        }

        $shift = Shift::create([
            'user_id' => auth()->id(),
            'branch_id' => auth()->user()->branch_id,
            'shift_code' => 'SHIFT-' . now()->format('Ymd-His'),
            'opening_cash' => $request->opening_cash,
            'expected_cash' => $request->opening_cash,
            'status' => 'OPEN',
            'is_open' => true,
            'opened_at' => now(),
        ]);

        AuditLogService::record([
            'action' => 'SHIFT_OPENED',
            'module' => 'Shifts',
            'model' => Shift::class,
            'model_id' => $shift->id,
            'branch_id' => $shift->branch_id,
            'reference' => $shift->shift_code,
            'description' => 'Opened shift with opening cash ' . number_format((float) $shift->opening_cash) . ' RWF.',
            'amount' => $shift->opening_cash,
            'new_values' => [
                'opening_cash' => $shift->opening_cash,
                'status' => 'OPEN',
            ],
        ]);

        return redirect()
            ->route('shifts.current')
            ->with('success', 'Shift ' . $shift->shift_code . ' opened.');
    }

    public function current()
    {
        $shift = $this->activeShift();

        if (!$shift) {
            return redirect()
                ->route('shifts.open.form')
                ->with('error', 'Open a shift to start selling.');
        }

        $totals = $this->paymentTotals($shift);
        $recentSales = Sale::where('shift_id', $shift->id)
            ->where('sale_status', 'COMPLETED')
            ->latest()
            ->take(6)
            ->get();

        return view('shifts.current', array_merge([
            'shift' => $shift,
            'expectedCash' => $shift->opening_cash + $totals['cashSales'],
            'recentSales' => $recentSales,
        ], $totals));
    }

    public function close(Request $request)
    {
        $request->validate([
            'closing_cash' => ['required', 'numeric', 'min:0'],
        ]);

        $shift = $this->activeShift();

        if (!$shift) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'No open shift found.');
        }

        $totals = $this->paymentTotals($shift);
        $expectedCash = $shift->opening_cash + $totals['cashSales'];
        $difference = (float) $request->closing_cash - $expectedCash;

        $shift->update([
            'closing_cash' => $request->closing_cash,
            'expected_cash' => $expectedCash,
            'difference' => $difference,
            'status' => 'CLOSED',
            'is_open' => false,
            'closed_at' => now(),
            'total_sales' => $totals['totalSales'],
            'cash_sales' => $totals['cashSales'],
            'momo_sales' => $totals['momoSales'],
            'airtel_sales' => $totals['airtelSales'],
            'visa_sales' => $totals['visaSales'],
            'mastercard_sales' => $totals['mastercardSales'],
            'bank_transfer_sales' => $totals['bankSales'],
        ]);

        AuditLogService::record([
            'action' => 'SHIFT_CLOSED',
            'module' => 'Shifts',
            'model' => Shift::class,
            'model_id' => $shift->id,
            'branch_id' => $shift->branch_id,
            'reference' => $shift->shift_code,
            'description' => 'Closed shift. Expected cash ' . number_format($expectedCash) . ' RWF, closing cash ' . number_format((float) $request->closing_cash) . ' RWF.',
            'amount' => $totals['totalSales'],
            'old_values' => [
                'status' => 'OPEN',
                'expected_cash' => $expectedCash,
            ],
            'new_values' => [
                'status' => 'CLOSED',
                'closing_cash' => $request->closing_cash,
                'difference' => $difference,
            ],
            'severity' => abs($difference) > 0 ? 'WARNING' : 'INFO',
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Shift closed successfully.');
    }

    public function history(Request $request)
    {
        $canReviewAll = $this->canReviewAllShifts($request->user());
        $query = $this->shiftHistoryQuery($request, $canReviewAll);
        $summaryQuery = clone $query;

        $summary = $this->shiftSummary($summaryQuery);
        $users = $canReviewAll
            ? User::query()->orderBy('name')->get(['id', 'name', 'email', 'role'])
            : collect();

        return view('shifts.history', [
            'shifts' => $query->paginate((int) $request->input('per_page', 20))->withQueryString(),
            'summary' => $summary,
            'users' => $users,
            'canReviewAll' => $canReviewAll,
            'periodLabel' => $this->shiftPeriodLabel($request),
        ]);
    }

    public function print(Shift $shift)
    {
        if (
            !$this->canReviewAllShifts(auth()->user())
            && $shift->user_id !== auth()->id()
        ) {
            abort(403);
        }

        $shift->load('user', 'branch');

        return view('shifts.print', compact('shift'));
    }

    public function printHistory(Request $request)
    {
        $canReviewAll = $this->canReviewAllShifts($request->user());
        $query = $this->shiftHistoryQuery($request, $canReviewAll);
        $summary = $this->shiftSummary(clone $query);
        $shifts = $query->get();

        return view('shifts.print-history', [
            'shifts' => $shifts,
            'summary' => $summary,
            'periodLabel' => $this->shiftPeriodLabel($request),
            'canReviewAll' => $canReviewAll,
        ]);
    }

    private function activeShift(): ?Shift
    {
        return Shift::where('user_id', auth()->id())
            ->where(function ($query) {
                $query->where('is_open', true)
                    ->orWhere('status', 'OPEN');
            })
            ->latest()
            ->first();
    }

    private function shiftHistoryQuery(Request $request, bool $canReviewAll)
    {
        $query = Shift::with('user', 'branch')
            ->latest('opened_at')
            ->latest('id');

        if (!$canReviewAll) {
            $query->where('user_id', $request->user()->id);
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', strtoupper((string) $request->status));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function ($shift) use ($search) {
                $shift->where('shift_code', 'like', '%' . $search . '%')
                    ->orWhere('id', $search)
                    ->orWhereHas('user', function ($user) use ($search) {
                        $user->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
            });
        }

        $this->applyShiftDateFilter($query, $request);

        return $query;
    }

    private function applyShiftDateFilter($query, Request $request): void
    {
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('opened_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59',
            ]);

            return;
        }

        match ($request->input('filter')) {
            'today' => $query->whereDate('opened_at', today()),
            'yesterday' => $query->whereDate('opened_at', today()->subDay()),
            'week' => $query->whereBetween('opened_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'last_week' => $query->whereBetween('opened_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()]),
            'month' => $query->whereMonth('opened_at', now()->month)->whereYear('opened_at', now()->year),
            'last_month' => $query->whereMonth('opened_at', now()->subMonth()->month)->whereYear('opened_at', now()->subMonth()->year),
            'year' => $query->whereYear('opened_at', now()->year),
            default => null,
        };
    }

    private function shiftSummary($query): array
    {
        return [
            'count' => (clone $query)->count(),
            'open' => (clone $query)->where('status', 'OPEN')->count(),
            'closed' => (clone $query)->where('status', 'CLOSED')->count(),
            'sales' => (clone $query)->sum('total_sales'),
            'cash' => (clone $query)->sum('cash_sales'),
            'expected' => (clone $query)->sum('expected_cash'),
            'closing' => (clone $query)->sum('closing_cash'),
            'difference' => (clone $query)->sum('difference'),
            'shortage' => abs((clone $query)->where('difference', '<', 0)->sum('difference')),
            'overage' => (clone $query)->where('difference', '>', 0)->sum('difference'),
        ];
    }

    private function shiftPeriodLabel(Request $request): string
    {
        if ($request->filled('start_date') && $request->filled('end_date')) {
            return $request->start_date . ' to ' . $request->end_date;
        }

        return match ($request->input('filter')) {
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'week' => 'This Week',
            'last_week' => 'Last Week',
            'month' => 'This Month',
            'last_month' => 'Last Month',
            'year' => 'This Year',
            default => 'All Time',
        };
    }

    private function canReviewAllShifts($user): bool
    {
        return $user->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER');
    }

    private function paymentTotals(Shift $shift): array
    {
        $sales = Sale::where('shift_id', $shift->id)
            ->where('sale_status', 'COMPLETED')
            ->get();

        $paymentBreakdown = [
            'CASH' => [
                'label' => 'Cash',
                'amount' => $sales->where('payment_method', 'CASH')->sum('grand_total'),
            ],
            'MOMO' => [
                'label' => 'MOMO',
                'amount' => $sales->where('payment_method', 'MOMO')->sum('grand_total'),
            ],
            'AIRTEL_MONEY' => [
                'label' => 'Airtel Money',
                'amount' => $sales->where('payment_method', 'AIRTEL_MONEY')->sum('grand_total'),
            ],
            'VISA' => [
                'label' => 'VISA',
                'amount' => $sales->where('payment_method', 'VISA')->sum('grand_total'),
            ],
            'MASTER_CARD' => [
                'label' => 'Mastercard',
                'amount' => $sales->where('payment_method', 'MASTER_CARD')->sum('grand_total'),
            ],
            'BANK_TRANSFER' => [
                'label' => 'Bank Transfer',
                'amount' => $sales->where('payment_method', 'BANK_TRANSFER')->sum('grand_total'),
            ],
        ];

        return [
            'totalSales' => $sales->sum('grand_total'),
            'transactionCount' => $sales->count(),
            'cashSales' => $sales->where('payment_method', 'CASH')->sum('grand_total'),
            'momoSales' => $sales->where('payment_method', 'MOMO')->sum('grand_total'),
            'airtelSales' => $sales->where('payment_method', 'AIRTEL_MONEY')->sum('grand_total'),
            'visaSales' => $sales->where('payment_method', 'VISA')->sum('grand_total'),
            'mastercardSales' => $sales->where('payment_method', 'MASTER_CARD')->sum('grand_total'),
            'bankSales' => $sales->where('payment_method', 'BANK_TRANSFER')->sum('grand_total'),
            'paymentBreakdown' => $paymentBreakdown,
        ];
    }
}
