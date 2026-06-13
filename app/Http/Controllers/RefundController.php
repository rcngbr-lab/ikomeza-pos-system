<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Refund;
use App\Models\RefundRequest;
use App\Services\RefundWorkflowService;

class RefundController extends Controller
{
    public function index()
    {
        $refundRequests = RefundRequest::with(['sale.user', 'requester'])
            ->latest()
            ->paginate(10, ['*'], 'requests_page');

        $refunds = Refund::with([
            'sale',
            'user'
        ])
        ->latest()
        ->paginate(10, ['*'], 'history_page');

        return view(
            'refunds.index',
            compact('refunds', 'refundRequests')
        );
    }

    public function approve(Request $request, RefundRequest $refundRequest, RefundWorkflowService $refundWorkflow)
    {
        $request->validate([
            'approval_note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $refundWorkflow->approveAndExecute(
                $refundRequest,
                $request->user(),
                $request->approval_note
            );
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', $exception->getMessage() ?: 'Refund approval failed.');
        }

        return back()->with('success', 'Refund approved, stock restored, and revenue reversed.');
    }

    public function reject(Request $request, RefundRequest $refundRequest, RefundWorkflowService $refundWorkflow)
    {
        $request->validate([
            'approval_note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $refundWorkflow->reject(
                $refundRequest,
                $request->user(),
                $request->approval_note
            );
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', $exception->getMessage() ?: 'Refund rejection failed.');
        }

        return back()->with('success', 'Refund request rejected.');
    }
}
