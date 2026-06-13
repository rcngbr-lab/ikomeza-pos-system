<?php

namespace App\Services;

use App\Models\OrderTicket;
use App\Models\Sale;
use Illuminate\Support\Facades\Schema;

class OrderTicketService
{
    public function createForSale(Sale $sale): void
    {
        if (!Schema::hasTable('order_tickets') || !Schema::hasTable('order_ticket_items')) {
            return;
        }

        $sale->loadMissing('items.product.department', 'items.department');

        $sale->items
            ->groupBy(fn ($item) => (int) ($item->department_id ?: 0))
            ->each(function ($items, $departmentId) use ($sale) {
                if ($items->isEmpty()) {
                    return;
                }

                $department = $items->first()->department ?: $items->first()->product?->department;
                $departmentCode = strtoupper((string) ($department?->code ?? $department?->name ?? 'KITCHEN'));
                $ticketType = str_contains($departmentCode, 'BAR')
                    ? OrderTicket::TYPE_BAR
                    : OrderTicket::TYPE_KITCHEN;

                $ticket = OrderTicket::create([
                    'ticket_number' => $ticketType . '-' . now()->format('Ymd-His') . '-' . random_int(100, 999),
                    'sale_id' => $sale->id,
                    'department_id' => $departmentId ?: null,
                    'table_id' => $sale->table_id,
                    'created_by' => $sale->user_id,
                    'ticket_type' => $ticketType,
                    'status' => OrderTicket::STATUS_PENDING,
                    'sent_at' => now(),
                    'notes' => 'Auto-generated from receipt ' . $sale->receipt_no,
                ]);

                foreach ($items as $item) {
                    $ticket->items()->create([
                        'sale_item_id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product_name ?: $item->product?->name,
                        'quantity' => $item->quantity,
                        'unit' => $item->product?->unit,
                        'status' => OrderTicket::STATUS_PENDING,
                    ]);

                    $item->forceFill(['ticket_status' => OrderTicket::STATUS_PENDING])->save();
                }
            });
    }
}

