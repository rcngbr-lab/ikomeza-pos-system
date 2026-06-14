<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockRequisition;
use App\Services\AuditLogService;
use App\Services\BranchAccessService;
use App\Services\DepartmentAccessService;
use App\Services\StoreStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StockRequisitionController extends Controller
{
    public function index(Request $request, DepartmentAccessService $departmentAccess)
    {
        $user = $request->user();
        $branchAccess = app(BranchAccessService::class);
        $selectedBranchId = $branchAccess->selectedBranchId($user, $request->integer('branch_id') ?: null);
        $selectedDepartmentId = $departmentAccess->selectedDepartmentId(
            $user,
            $request->integer('department_id') ?: null
        );

        $departments = $departmentAccess->visibleDepartments($user);
        $canApprove = $this->canApproveRequisitions($user);
        $canProcess = $this->canProcessRequisitions($user);

        $requisitionQuery = StockRequisition::with([
            'product.department',
            'department',
            'requester',
            'approver',
        ]);
        $branchAccess->apply($requisitionQuery, $user, $selectedBranchId);

        $requisitions = $requisitionQuery
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
            ->when($selectedBranchId, fn ($query) => $query->where('branch_id', $selectedBranchId))
            ->when($selectedDepartmentId, fn ($query) => $query->where('department_id', $selectedDepartmentId))
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $summaryQuery = StockRequisition::query();
        $branchAccess->apply($summaryQuery, $user, $selectedBranchId);
        $summaryQuery
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
            'canApprove',
            'canProcess'
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

        if (
            !$request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR')
            && $product->branch_id
            && (int) $product->branch_id !== (int) $request->user()->branch_id
        ) {
            abort(403);
        }

        $departmentAccess->authorize(
            $request->user(),
            $product->department_id
        );

        $requisition = StockRequisition::create([
            'product_id' => $product->id,
            'branch_id' => $request->user()->branch_id,
            'department_id' => $product->department_id,
            'requester_id' => $request->user()->id,
            'type' => $validated['type'],
            'quantity' => (int) $validated['quantity'],
            'status' => StockRequisition::STATUS_PENDING,
            'reason' => $validated['reason'] ?? null,
        ]);

        AuditLogService::record([
            'action' => 'REQUISITION_SUBMITTED',
            'module' => 'Requisitions',
            'model' => StockRequisition::class,
            'model_id' => $requisition->id,
            'department_id' => $requisition->department_id,
            'branch_id' => $request->user()->branch_id,
            'reference' => 'RQ-' . str_pad((string) $requisition->id, 6, '0', STR_PAD_LEFT),
            'description' => 'Submitted ' . $requisition->typeLabel() . ' requisition for ' . $product->name . ' (' . number_format($requisition->quantity) . ' units).',
            'new_values' => $requisition->only(['type', 'quantity', 'status', 'reason']),
            'quantity_changed' => $requisition->quantity,
            'metadata' => [
                'product' => $product->name,
            ],
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

                $requisition->update([
                    'status' => StockRequisition::STATUS_APPROVED,
                    'approver_id' => $request->user()->id,
                    'manager_note' => $request->input('manager_note'),
                    'approved_at' => now(),
                ]);

                AuditLogService::record([
                    'action' => 'REQUISITION_APPROVED',
                    'module' => 'Requisitions',
                    'model' => StockRequisition::class,
                    'model_id' => $requisition->id,
                    'department_id' => $requisition->department_id,
                    'branch_id' => $request->user()->branch_id,
                    'reference' => 'RQ-' . str_pad((string) $requisition->id, 6, '0', STR_PAD_LEFT),
                    'description' => 'Approved requisition #' . $requisition->id . '. Stock was not changed; receiving, store issue, or damage approval must complete the inventory movement.',
                    'old_values' => ['status' => StockRequisition::STATUS_PENDING],
                    'new_values' => [
                        'status' => StockRequisition::STATUS_APPROVED,
                        'approver_id' => $request->user()->id,
                    ],
                    'quantity_changed' => $requisition->quantity,
                ]);
            });
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Requisition approved. Stock will change only after receiving, store issue, or approved damage processing.');
    }

    public function process(Request $request, StockRequisition $requisition, StoreStockService $storeStockService)
    {
        $this->authorizeProcessing($request, $requisition);

        $request->validate([
            'processing_note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            DB::transaction(function () use ($request, $requisition, $storeStockService) {
                $requisition = StockRequisition::with('product.department')
                    ->whereKey($requisition->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!$requisition->isApproved()) {
                    throw new \RuntimeException('Only approved requisitions can be processed.');
                }

                $product = Product::whereKey($requisition->product_id)
                    ->lockForUpdate()
                    ->firstOrFail()
                    ->load('department');

                $store = $storeStockService->defaultStoreFor($product);

                if (!$store) {
                    throw new \RuntimeException('No default store is configured for ' . $product->name . '.');
                }

                $quantity = (float) $requisition->quantity;
                $note = $request->input('processing_note')
                    ?: 'Processed requisition RQ-' . str_pad((string) $requisition->id, 6, '0', STR_PAD_LEFT);

                if ($requisition->type === StockRequisition::TYPE_STOCK_IN) {
                    $snapshot = $storeStockService->receiveIntoStore(
                        product: $product,
                        store: $store,
                        quantity: $quantity,
                        user: $request->user(),
                        referenceType: StockRequisition::class,
                        referenceId: $requisition->id,
                        unitCost: (float) ($product->buy_price ?? 0),
                        note: $note,
                        movementType: 'STOCK_IN'
                    );

                    Stock::create([
                        'product_id' => $product->id,
                        'department_id' => $product->department_id,
                        'type' => 'stock_in',
                        'quantity' => $quantity,
                        'before_stock' => $snapshot['product_before'],
                        'after_stock' => $snapshot['product_after'],
                        'note' => $note,
                        'user_id' => $request->user()->id,
                    ]);

                    $newStatus = StockRequisition::STATUS_RECEIVED;
                    $action = 'REQUISITION_RECEIVED';
                    $description = 'Received approved stock-in requisition and increased live stock.';
                    $quantityChanged = abs($quantity);
                } else {
                    $movementType = $requisition->type === StockRequisition::TYPE_DAMAGED
                        ? 'DAMAGE'
                        : 'STOCK_OUT';

                    $snapshot = $storeStockService->removeFromStore(
                        product: $product,
                        store: $store,
                        quantity: $quantity,
                        user: $request->user(),
                        movementType: $movementType,
                        referenceType: StockRequisition::class,
                        referenceId: $requisition->id,
                        unitCost: (float) ($product->buy_price ?? 0),
                        note: $note,
                        approvedBy: $requisition->approver_id
                    );

                    Stock::create([
                        'product_id' => $product->id,
                        'department_id' => $product->department_id,
                        'type' => $requisition->type === StockRequisition::TYPE_DAMAGED ? 'damage' : 'stock_out',
                        'quantity' => $quantity,
                        'before_stock' => $snapshot['product_before'],
                        'after_stock' => $snapshot['product_after'],
                        'note' => $note,
                        'user_id' => $request->user()->id,
                    ]);

                    $newStatus = StockRequisition::STATUS_PROCESSED;
                    $action = $requisition->type === StockRequisition::TYPE_DAMAGED
                        ? 'DAMAGE_PROCESSED'
                        : 'STOCK_OUT_PROCESSED';
                    $description = $requisition->type === StockRequisition::TYPE_DAMAGED
                        ? 'Processed approved damaged-stock requisition and deducted live stock.'
                        : 'Processed approved stock-out requisition and deducted live stock.';
                    $quantityChanged = -abs($quantity);
                }

                $oldStatus = $requisition->status;

                $requisition->update([
                    'status' => $newStatus,
                    'manager_note' => trim(($requisition->manager_note ? $requisition->manager_note . "\n" : '') . $note),
                ]);

                AuditLogService::record([
                    'action' => $action,
                    'module' => 'Inventory',
                    'model' => StockRequisition::class,
                    'model_id' => $requisition->id,
                    'department_id' => $requisition->department_id,
                    'branch_id' => $request->user()->branch_id,
                    'reference' => 'RQ-' . str_pad((string) $requisition->id, 6, '0', STR_PAD_LEFT),
                    'description' => $description,
                    'old_values' => ['status' => $oldStatus],
                    'new_values' => [
                        'status' => $newStatus,
                        'processed_by' => $request->user()->id,
                        'store_id' => $store->id,
                    ],
                    'quantity_before' => $snapshot['product_before'] ?? null,
                    'quantity_changed' => $quantityChanged,
                    'quantity_after' => $snapshot['product_after'] ?? null,
                    'severity' => $quantityChanged < 0 ? 'WARNING' : 'INFO',
                ]);
            });
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Approved requisition processed and stock ledger updated.');
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

                AuditLogService::record([
                    'action' => 'REQUISITION_REJECTED',
                    'module' => 'Requisitions',
                    'model' => StockRequisition::class,
                    'model_id' => $requisition->id,
                    'department_id' => $requisition->department_id,
                    'branch_id' => $request->user()->branch_id,
                    'reference' => 'RQ-' . str_pad((string) $requisition->id, 6, '0', STR_PAD_LEFT),
                    'description' => 'Rejected requisition #' . $requisition->id . '.',
                    'old_values' => ['status' => StockRequisition::STATUS_PENDING],
                    'new_values' => [
                        'status' => StockRequisition::STATUS_REJECTED,
                        'manager_note' => $request->input('manager_note') ?: 'Rejected by manager',
                    ],
                    'severity' => 'WARNING',
                ]);
            });
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Requisition rejected.');
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
        $this->authorizeRequisitionBranch($request, $requisition);

        if (
            !$request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR')
            && (int) $requisition->requester_id === (int) $request->user()->id
        ) {
            abort(403, 'Separation of duties: requester cannot approve their own requisition.');
        }
    }

    private function canApproveRequisitions($user): bool
    {
        return $user->hasOperationalRole(
            'ADMIN',
            'ADMINISTRATOR',
            'MANAGER'
        );
    }

    private function authorizeProcessing(Request $request, StockRequisition $requisition): void
    {
        abort_unless(
            $this->canProcessRequisitions($request->user()),
            403
        );

        app(DepartmentAccessService::class)->authorize(
            $request->user(),
            $requisition->department_id
        );
        $this->authorizeRequisitionBranch($request, $requisition);

        if (
            !$request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR')
            && in_array((int) $request->user()->id, [(int) $requisition->requester_id, (int) $requisition->approver_id], true)
        ) {
            abort(403, 'Separation of duties: requester/approver cannot process the same requisition.');
        }
    }

    private function canProcessRequisitions($user): bool
    {
        return $user->hasOperationalRole(
            'ADMIN',
            'ADMINISTRATOR',
            'MANAGER',
            'STORE_KEEPER'
        );
    }

    private function authorizeRequisitionBranch(Request $request, StockRequisition $requisition): void
    {
        if ($request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR')) {
            return;
        }

        abort_unless((int) $requisition->branch_id === (int) $request->user()->branch_id, 403);
    }
}
