<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Shift;
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

        return view('shifts.current', array_merge([
            'shift' => $shift,
            'expectedCash' => $shift->opening_cash + $totals['cashSales'],
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

        return redirect()
            ->route('dashboard')
            ->with('success', 'Shift closed successfully.');
    }

    public function history()
    {
        $query = Shift::with('user')->latest();

        if (auth()->user()->hasOperationalRole('CASHIER')) {
            $query->where('user_id', auth()->id());
        }

        return view('shifts.history', [
            'shifts' => $query->paginate(20),
        ]);
    }

    public function print(Shift $shift)
    {
        if (
            auth()->user()->hasOperationalRole('CASHIER')
            && $shift->user_id !== auth()->id()
        ) {
            abort(403);
        }

        $shift->load('user');

        return view('shifts.print', compact('shift'));
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

    private function paymentTotals(Shift $shift): array
    {
        $sales = Sale::where('shift_id', $shift->id)
            ->where('sale_status', 'COMPLETED')
            ->get();

        return [
            'totalSales' => $sales->sum('grand_total'),
            'cashSales' => $sales->where('payment_method', 'CASH')->sum('grand_total'),
            'momoSales' => $sales->where('payment_method', 'MOMO')->sum('grand_total'),
            'airtelSales' => $sales->where('payment_method', 'AIRTEL_MONEY')->sum('grand_total'),
            'visaSales' => $sales->where('payment_method', 'VISA')->sum('grand_total'),
            'mastercardSales' => $sales->where('payment_method', 'MASTER_CARD')->sum('grand_total'),
            'bankSales' => $sales->where('payment_method', 'BANK_TRANSFER')->sum('grand_total'),
        ];
    }
}
