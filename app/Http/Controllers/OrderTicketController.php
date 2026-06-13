<?php

namespace App\Http\Controllers;

use App\Models\OrderTicket;
use Illuminate\Http\Request;

class OrderTicketController extends Controller
{
    public function index(Request $request)
    {
        $query = OrderTicket::with(['sale.user', 'department', 'items'])
            ->latest();

        $user = $request->user();

        if ($user->hasOperationalRole('KITCHEN_MANAGER', 'KITCHEN_CHIEF')) {
            $query->where('ticket_type', OrderTicket::TYPE_KITCHEN);
        } elseif ($user->hasOperationalRole('BAR_MANAGER', 'BAR_CHIEF', 'BARTENDER')) {
            $query->where('ticket_type', OrderTicket::TYPE_BAR);
        } elseif ($request->filled('type')) {
            $query->where('ticket_type', strtoupper($request->type));
        }

        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->status));
        }

        $tickets = $query->paginate(18)->withQueryString();

        return view('tickets.index', compact('tickets'));
    }

    public function updateStatus(Request $request, OrderTicket $ticket)
    {
        if (
            $request->user()->hasOperationalRole('KITCHEN_MANAGER', 'KITCHEN_CHIEF')
            && $ticket->ticket_type !== OrderTicket::TYPE_KITCHEN
        ) {
            abort(403);
        }

        if (
            $request->user()->hasOperationalRole('BAR_MANAGER', 'BAR_CHIEF', 'BARTENDER')
            && $ticket->ticket_type !== OrderTicket::TYPE_BAR
        ) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:PENDING,ACCEPTED,READY,SERVED,CANCELLED'],
        ]);

        $status = strtoupper($validated['status']);
        $updates = ['status' => $status];

        if ($status === 'ACCEPTED') {
            $updates['accepted_at'] = now();
            $updates['assigned_to'] = $request->user()->id;
        } elseif ($status === 'READY') {
            $updates['ready_at'] = now();
        } elseif ($status === 'SERVED') {
            $updates['served_at'] = now();
        }

        $ticket->update($updates);
        $ticket->items()->update(['status' => $status]);

        return back()->with('success', 'Ticket ' . $ticket->ticket_number . ' updated.');
    }
}
