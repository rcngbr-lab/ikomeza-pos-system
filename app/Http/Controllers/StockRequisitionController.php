<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockRequisition;
use App\Services\DepartmentAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StockRequisitionController extends Controller
{
    public function index(Request $request, DepartmentAccessService $departmentAccess)
    {
        $user = $request->user();
        $selectedDepartmentId = $departmentAccess->selectedDepartmentId(
            $user,
            $request->integer('department_id') ?: null
        );

        $departments = $departmentAccess->visibleDepartments($user);
        $canApprove = $this->canApproveRequisitions($user);

        $requisitions = StockRequisition::with([
            'product.department',
            'department',
            'requester',
            'approver',
        ])
            ->when($selectedDepartmentId, fn ($query) => $query->where('department_id', $selectedDepartmentId))
            ->when(
                $user->hasOperationalRole('CASHIER', 'WAITER', 'SERVER') && !$canApprove,
                fn ($query) => $query->where('requester_id', $user->id)
            )
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->type))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $products = Product::with('department')
            ->when($selectedDepartmentId, fn ($query) => $query->where('department_id', $selectedDepartmentId))
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $summaryQuery = StockRequisition::query()
            ->when($selectedDepartmentId, fn ($query) => $query->where('department_id', $selectedDepartmentId))
            ->when(
                $user->hasOperationalRole('CASHIER', 'WAITER', 'SERVER') && !$canApprove,
                fn ($query) => $query->where('requester_id', $user->id)
            );

        $summary = [
            'pending' => (clone $summaryQuery)->where('status', StockRequisition::STATUS_PENDING)->count(),
            'approved' => (clone $summaryQuery)->where('status', StockRequisition::STATUS_APPROVED)->count(),
            'rejected' => (clone $summaryQuery)->where('status', StockRequisition::STATUS_REJECTED)->count(),
            'quantity' => (clone $summaryQuery)->sum('quantity'),
        ];

        return view('requisitions.index', compact(
            'requisitions',
            'products',
            'departments',
            'selectedDepartmentId',
            'summary',
            'canApprove'
        ));
    }

    public function store(Request $request, DepartmentAccessService $departmentAccess)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'type' => ['required', Rule::in([
                StockRequisition::TYPE_STOCK_IN,
                StockRequisition::TYPE_DAMAGED,
                StockRequisition::TYPE_STOCK_OUT,
            ])],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $product = Product::findOrFail($validated['product_id']);

        $departmentAccess->authorize(
            $request->user(),
            $product->department_id
        );

        StockRequisition::create([
            'product_id' => $product->id,
            'department_id' => $product->department_id,
            'requester_id' => $request->user()->id,
            'type' => $validated['type'],
            'quantity' => (int) $validated['quantity'],
            'status' => StockRequisition::STATUS_PENDING,
            'reason' => $validated['reason'] ?? null,
        ]);

        return back()->with('success', 'Requisition submitted for approval.');
    }

    public function approve(Request $request, StockRequisition $requisition)
    {
        $this->authorizeApproval($request, $requisition);

        try {
            DB::transaction(function () use ($request, $requisition) {
                $requisition = StockRequisition::whereKey($requisition->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!$requisition->isPending()) {
                    throw new \RuntimeException('This requisition has already been processed.');
                }

                $this->applyApprovedStockChange($request, $requisition);

                $requisition->update([
                    'status' => StockRequisition::STATUS_APPROVED,
                    'approver_id' => $request->user()->id,
                    'manager_note' => $request->input('manager_note'),
                    'approved_at' => now(),
                ]);
            });
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Requisition approved and stock updated.');
    }

    public function reject(Request $request, StockRequisition $requisition)
    {
        $this->authorizeApproval($request, $requisition);

        $request->validate([
            'manager_note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            DB::transaction(function () use ($request, $requisition) {
                $requisition = StockRequisition::whereKey($requisition->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!$requisition->isPending()) {
                    throw new \RuntimeException('This requisition has already been processed.');
                }

                $requisition->update([
                    'status' => StockRequisition::STATUS_REJECTED,
                    'approver_id' => $request->user()->id,
                    'manager_note' => $request->input('manager_note') ?: 'Rejected by manager',
                    'approved_at' => now(),
                ]);
            });
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Requisition rejected.');
    }

    private function applyApprovedStockChange(Request $request, StockRequisition $requisition): void
    {
        $product = Product::whereKey($requisition->product_id)
            ->lockForUpdate()
            ->firstOrFail();

        $quantity = (int) $requisition->quantity;
        $beforeStock = (int) $product->stock;
        $stockRecordType = 'stock_in';
        $movementType = 'STOCK_IN';

        if ($requisition->type === StockRequisition::TYPE_STOCK_IN) {
            $product->increment('stock', $quantity);
        } else {
            if ($beforeStock < $quantity) {
                throw new \RuntimeException('Not enough stock to approve this request.');
            }

            $product->decrement('stock', $quantity);
            $stockRecordType = $requisition->type === StockRequisition::TYPE_DAMAGED ? 'damage' : 'stock_out';
            $movementType = $requisition->type === StockRequisition::TYPE_DAMAGED ? 'DAMAGE' : 'STOCK_OUT';
        }

        $product->refresh();
        $afterStock = (int) $product->stock;
        $note = trim('Approved requisition #' . $requisition->id . ' ' . ($requisition->reason ?? ''));

        Stock::create([
            'product_id' => $product->id,
            'department_id' => $product->department_id,
            'type' => $stockRecordType,
            'quantity' => $quantity,
            'before_stock' => $beforeStock,
            'after_stock' => $afterStock,
            'note' => $note,
            'user_id' => $request->user()->id,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'department_id' => $product->department_id,
            'branch_id' => $request->user()->branch_id,
            'user_id' => $request->user()->id,
            'type' => $movementType,
            'quantity' => $quantity,
            'before_stock' => $beforeStock,
            'after_stock' => $afterStock,
            'reference_type' => StockRequisition::class,
            'reference_id' => $requisition->id,
            'reason' => $note,
        ]);
    }

    private function authorizeApproval(Request $request, StockRequisition $requisition): void
    {
        abort_unless(
            $this->canApproveRequisitions($request->user()),
            403
        );

        app(DepartmentAccessService::class)->authorize(
            $request->user(),
            $requisition->department_id
        );
    }

    private function canApproveRequisitions($user): bool
    {
        return $user->hasOperationalRole(
            'ADMIN',
            'ADMINISTRATOR',
            'MANAGER',
            'KITCHEN_MANAGER',
            'KITCHEN_CHIEF',
            'BAR_MANAGER',
            'BAR_CHIEF',
            'BARTENDER'
        );
    }
}
