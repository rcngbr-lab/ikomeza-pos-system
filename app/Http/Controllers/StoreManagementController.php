<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Stock;
use App\Models\StockDamage;
use App\Models\StockMovement;
use App\Models\StockRequisition;
use App\Models\StockReturn;
use App\Models\Store;
use App\Models\StoreIssue;
use App\Models\StoreIssueItem;
use App\Models\StoreStock;
use App\Models\Supplier;
use App\Services\AuditLogService;
use App\Services\DepartmentAccessService;
use App\Services\StoreStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreManagementController extends Controller
{
    public function dashboard(Request $request, DepartmentAccessService $departmentAccess)
    {
        $this->authorizeStoreAccess($request);

        $context = $this->storeContext($request, $departmentAccess);

        $stockBase = StoreStock::with(['store', 'product.category', 'department'])
            ->when($context['selectedDepartmentId'], fn ($query) => $query->where('department_id', $context['selectedDepartmentId']))
            ->when($context['selectedStoreId'], fn ($query) => $query->where('store_id', $context['selectedStoreId']))
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->whereHas('product', function ($product) use ($request) {
                    $product->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('product_code', 'like', '%' . $request->search . '%')
                        ->orWhere('barcode', 'like', '%' . $request->search . '%');
                });
            });

        $storeStocks = (clone $stockBase)
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $storeValues = StoreStock::query()
            ->selectRaw('store_id, SUM(total_value) as value, SUM(quantity) as units')
            ->when($context['selectedDepartmentId'], fn ($query) => $query->where('department_id', $context['selectedDepartmentId']))
            ->groupBy('store_id')
            ->pluck('value', 'store_id');

        $summary = [
            'total_value' => (clone $stockBase)->sum('total_value'),
            'low_stock' => (clone $stockBase)->whereColumn('quantity', '<=', 'alert_stock')->where('quantity', '>', 0)->count(),
            'out_of_stock' => (clone $stockBase)->where('quantity', '<=', 0)->count(),
            'pending_requisitions' => $this->filteredRequisitions($context)->where('status', StockRequisition::STATUS_PENDING)->count(),
            'approved_awaiting_issue' => $this->filteredRequisitions($context)->where('status', StockRequisition::STATUS_APPROVED)->count(),
            'pending_deliveries' => $this->filteredPurchases($context)->whereIn('status', [
                Purchase::STATUS_APPROVED,
                Purchase::STATUS_ORDERED,
                Purchase::STATUS_PARTIALLY_RECEIVED,
            ])->count(),
            'received_today' => $this->filteredMovements($context)->whereDate('created_at', today())->whereIn('type', ['PURCHASE_RECEIVED', 'STOCK_IN'])->sum('quantity'),
            'issued_today' => $this->filteredMovements($context)->whereDate('created_at', today())->whereIn('type', ['STORE_ISSUE', 'STORE_TRANSFER', 'STOCK_OUT'])->sum('quantity'),
            'damaged_stock' => StockDamage::query()
                ->when($context['selectedDepartmentId'], fn ($query) => $query->where('department_id', $context['selectedDepartmentId']))
                ->sum('quantity'),
            'returned_stock' => StockReturn::query()
                ->when($context['selectedDepartmentId'], fn ($query) => $query->where('department_id', $context['selectedDepartmentId']))
                ->sum('quantity'),
        ];

        $recentMovements = $this->filteredMovements($context)
            ->with(['product', 'fromStore', 'toStore', 'user'])
            ->latest()
            ->take(8)
            ->get();

        $pendingPurchases = $this->filteredPurchases($context)
            ->with(['supplier', 'store', 'purchaser', 'approver'])
            ->whereIn('status', [Purchase::STATUS_PENDING_APPROVAL, Purchase::STATUS_APPROVED, Purchase::STATUS_PARTIALLY_RECEIVED])
            ->latest()
            ->take(6)
            ->get();

        return view('store.dashboard', array_merge($context, compact(
            'storeStocks',
            'storeValues',
            'summary',
            'recentMovements',
            'pendingPurchases'
        )));
    }

    public function suppliers(Request $request, DepartmentAccessService $departmentAccess)
    {
        $this->authorizeStoreAccess($request);

        $context = $this->storeContext($request, $departmentAccess);

        $suppliers = Supplier::with('department')
            ->when($context['selectedDepartmentId'], fn ($query) => $query->where('department_id', $context['selectedDepartmentId']))
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('company_name', 'like', '%' . $request->search . '%')
                    ->orWhere('phone', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('store.suppliers', array_merge($context, compact('suppliers')));
    }

    public function storeSupplier(Request $request, DepartmentAccessService $departmentAccess)
    {
        $this->authorizeStoreAccess($request);

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'tax_number' => ['nullable', 'string', 'max:120'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'supplied_categories' => ['nullable', 'string', 'max:1000'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (!empty($validated['department_id'])) {
            $departmentAccess->authorize($request->user(), (int) $validated['department_id']);
        }

        $supplier = Supplier::create(array_merge($validated, [
            'status' => Supplier::STATUS_ACTIVE,
        ]));

        AuditLogService::record([
            'action' => 'SUPPLIER_CREATED',
            'module' => 'Suppliers',
            'model' => $supplier,
            'department_id' => $supplier->department_id,
            'reference' => 'SUP-' . str_pad((string) $supplier->id, 5, '0', STR_PAD_LEFT),
            'description' => 'Created supplier ' . $supplier->company_name,
            'new_values' => $supplier->only(['company_name', 'phone', 'email', 'department_id', 'status']),
        ]);

        return back()->with('success', 'Supplier created successfully.');
    }

    public function purchases(Request $request, DepartmentAccessService $departmentAccess)
    {
        $this->authorizeStoreAccess($request);

        $context = $this->storeContext($request, $departmentAccess);

        $purchases = $this->filteredPurchases($context)
            ->with(['supplier', 'store', 'department', 'items.product', 'purchaser', 'approver', 'receiver'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('payment_status'), fn ($query) => $query->where('payment_status', $request->payment_status))
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('purchase_number', 'like', '%' . $request->search . '%')
                    ->orWhere('invoice_number', 'like', '%' . $request->search . '%')
                    ->orWhereHas('supplier', fn ($supplier) => $supplier->where('company_name', 'like', '%' . $request->search . '%'));
            });

        $this->applyDateFilter($purchases, $request);

        $purchases = $purchases
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $products = Product::with('department')
            ->when($context['selectedDepartmentId'], fn ($query) => $query->where('department_id', $context['selectedDepartmentId']))
            ->orderBy('name')
            ->get();

        $suppliers = Supplier::query()
            ->when($context['selectedDepartmentId'], fn ($query) => $query->where(function ($supplier) use ($context) {
                $supplier->whereNull('department_id')->orWhere('department_id', $context['selectedDepartmentId']);
            }))
            ->where('status', Supplier::STATUS_ACTIVE)
            ->orderBy('company_name')
            ->get();

        $approvedRequisitions = StockRequisition::with('product')
            ->when($context['selectedDepartmentId'], fn ($query) => $query->where('department_id', $context['selectedDepartmentId']))
            ->where('status', StockRequisition::STATUS_APPROVED)
            ->latest()
            ->take(50)
            ->get();

        return view('store.purchases', array_merge($context, compact(
            'purchases',
            'products',
            'suppliers',
            'approvedRequisitions'
        )));
    }

    public function storePurchase(Request $request, DepartmentAccessService $departmentAccess)
    {
        $this->authorizeStoreAccess($request);

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'store_id' => ['required', 'exists:stores,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'requisition_id' => ['nullable', 'exists:stock_requisitions,id'],
            'product_id' => ['required', 'exists:products,id'],
            'quantity_ordered' => ['required', 'numeric', 'min:0.001'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'invoice_number' => ['nullable', 'string', 'max:120'],
            'purchase_date' => ['nullable', 'date'],
            'expected_delivery_date' => ['nullable', 'date'],
            'payment_status' => ['required', Rule::in([
                Purchase::PAYMENT_UNPAID,
                Purchase::PAYMENT_PARTIALLY_PAID,
                Purchase::PAYMENT_PAID,
                Purchase::PAYMENT_CREDIT,
            ])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $store = Store::findOrFail($validated['store_id']);
        $product = Product::with('department')->findOrFail($validated['product_id']);

        $this->authorizeStore($request, $store);
        $departmentAccess->authorize($request->user(), $product->department_id);

        $subtotal = (float) $validated['quantity_ordered'] * (float) $validated['unit_cost'];
        $tax = (float) ($validated['tax'] ?? 0);
        $discount = (float) ($validated['discount'] ?? 0);
        $total = $subtotal + $tax - $discount;

        $purchase = DB::transaction(function () use ($request, $validated, $product, $store, $subtotal, $tax, $discount, $total) {
            $purchase = Purchase::create([
                'purchase_number' => $this->nextNumber('PO'),
                'supplier_id' => $validated['supplier_id'],
                'requisition_id' => $validated['requisition_id'] ?? null,
                'department_id' => $validated['department_id'] ?: $product->department_id,
                'store_id' => $store->id,
                'purchased_by' => $request->user()->id,
                'invoice_number' => $validated['invoice_number'] ?? null,
                'purchase_date' => $validated['purchase_date'] ?? today(),
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total_amount' => $total,
                'payment_status' => $validated['payment_status'],
                'status' => Purchase::STATUS_PENDING_APPROVAL,
                'notes' => $validated['notes'] ?? null,
            ]);

            PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'product_id' => $product->id,
                'quantity_ordered' => $validated['quantity_ordered'],
                'quantity_received' => 0,
                'unit_cost' => $validated['unit_cost'],
                'total_cost' => $subtotal,
            ]);

            AuditLogService::record([
                'action' => 'PURCHASE_CREATED',
                'module' => 'Purchases',
                'model' => $purchase,
                'department_id' => $purchase->department_id,
                'reference' => $purchase->purchase_number,
                'description' => 'Created purchase ' . $purchase->purchase_number . ' for approval. Stock was not increased.',
                'new_values' => [
                    'status' => $purchase->status,
                    'supplier_id' => $purchase->supplier_id,
                    'store_id' => $purchase->store_id,
                    'total_amount' => $purchase->total_amount,
                ],
                'amount' => $purchase->total_amount,
            ]);

            return $purchase;
        });

        return redirect()
            ->route('store.purchases', ['purchase' => $purchase->id])
            ->with('success', 'Purchase created and sent for approval. Stock will increase only after receiving.');
    }

    public function approvePurchase(Request $request, Purchase $purchase)
    {
        $this->authorizeApproval($request);

        if (!$request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR') && (int) $purchase->purchased_by === (int) $request->user()->id) {
            return back()->with('error', 'Separation of duties: requester cannot approve their own purchase.');
        }

        if (!in_array($purchase->status, [Purchase::STATUS_DRAFT, Purchase::STATUS_PENDING_APPROVAL], true)) {
            return back()->with('error', 'Only draft or pending purchases can be approved.');
        }

        $purchase->update([
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'status' => Purchase::STATUS_APPROVED,
        ]);

        AuditLogService::record([
            'action' => 'PURCHASE_APPROVED',
            'module' => 'Purchases',
            'model' => $purchase,
            'department_id' => $purchase->department_id,
            'reference' => $purchase->purchase_number,
            'description' => 'Approved purchase ' . $purchase->purchase_number . '. Stock still awaits physical receiving.',
            'old_values' => ['status' => Purchase::STATUS_PENDING_APPROVAL],
            'new_values' => ['status' => Purchase::STATUS_APPROVED, 'approved_by' => $request->user()->id],
            'amount' => $purchase->total_amount,
        ]);

        return back()->with('success', 'Purchase approved. Store Keeper can now receive delivered stock.');
    }

    public function receivePurchase(Request $request, Purchase $purchase, StoreStockService $storeStockService)
    {
        $this->authorizeReceiving($request);

        $purchase->load(['items.product.department', 'store', 'supplier']);

        if (!$purchase->isReceivable()) {
            return back()->with('error', 'This purchase is not ready for receiving.');
        }

        if (
            !$request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR')
            && in_array((int) $request->user()->id, [(int) $purchase->purchased_by, (int) $purchase->approved_by], true)
        ) {
            return back()->with('error', 'Separation of duties: purchaser/approver cannot receive the same purchase.');
        }

        $request->validate([
            'received' => ['required', 'array'],
            'received.*' => ['nullable', 'numeric', 'min:0'],
            'damaged' => ['nullable', 'array'],
            'damaged.*' => ['nullable', 'numeric', 'min:0'],
            'batch_number' => ['nullable', 'array'],
            'batch_number.*' => ['nullable', 'string', 'max:120'],
            'expiry_date' => ['nullable', 'array'],
            'expiry_date.*' => ['nullable', 'date'],
            'receiving_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $receivedUnits = 0;
        $damagedUnits = 0;

        DB::transaction(function () use ($request, $purchase, $storeStockService, &$receivedUnits, &$damagedUnits) {
            foreach ($purchase->items as $item) {
                $received = (float) ($request->input('received.' . $item->id, 0) ?: 0);
                $damaged = (float) ($request->input('damaged.' . $item->id, 0) ?: 0);

                if ($received <= 0 && $damaged <= 0) {
                    continue;
                }

                $item->update([
                    'quantity_received' => (float) $item->quantity_received + $received,
                    'damaged_quantity' => (float) $item->damaged_quantity + $damaged,
                    'batch_number' => $request->input('batch_number.' . $item->id) ?: $item->batch_number,
                    'expiry_date' => $request->input('expiry_date.' . $item->id) ?: $item->expiry_date,
                    'notes' => $request->input('receiving_note'),
                ]);

                if ($received > 0) {
                    $snapshot = $storeStockService->receiveIntoStore(
                        product: $item->product,
                        store: $purchase->store,
                        quantity: $received,
                        user: $request->user(),
                        referenceType: Purchase::class,
                        referenceId: $purchase->id,
                        unitCost: (float) $item->unit_cost,
                        note: 'Received ' . $purchase->purchase_number
                    );

                    Stock::create([
                        'product_id' => $item->product_id,
                        'department_id' => $item->product->department_id,
                        'type' => 'stock_in',
                        'quantity' => $received,
                        'before_stock' => $snapshot['product_before'],
                        'after_stock' => $snapshot['product_after'],
                        'note' => 'Purchase receiving ' . $purchase->purchase_number,
                        'user_id' => $request->user()->id,
                    ]);

                    $receivedUnits += $received;
                }

                if ($damaged > 0) {
                    StockDamage::create([
                        'damage_number' => $this->nextNumber('DMG'),
                        'product_id' => $item->product_id,
                        'store_id' => $purchase->store_id,
                        'department_id' => $item->product->department_id,
                        'quantity' => $damaged,
                        'reason' => 'Damaged at supplier delivery',
                        'notes' => $request->input('receiving_note'),
                        'recorded_by' => $request->user()->id,
                        'approved_by' => $request->user()->id,
                        'approved_at' => now(),
                        'status' => StockDamage::STATUS_APPROVED,
                    ]);

                    $damagedUnits += $damaged;
                }
            }

            $purchase->refresh();
            $purchase->load('items');

            $ordered = $purchase->items->sum(fn ($item) => (float) $item->quantity_ordered);
            $received = $purchase->items->sum(fn ($item) => (float) $item->quantity_received);

            $purchase->update([
                'received_by' => $request->user()->id,
                'received_date' => now(),
                'status' => $received >= $ordered
                    ? Purchase::STATUS_RECEIVED
                    : Purchase::STATUS_PARTIALLY_RECEIVED,
            ]);

            AuditLogService::record([
                'action' => 'PURCHASE_RECEIVED',
                'module' => 'Purchases',
                'model' => $purchase,
                'department_id' => $purchase->department_id,
                'reference' => $purchase->purchase_number,
                'description' => 'Received supplier delivery for ' . $purchase->purchase_number . '. Stock increased only for physically received units.',
                'new_values' => [
                    'status' => $purchase->status,
                    'received_by' => $request->user()->id,
                    'received_units' => $receivedUnits,
                    'damaged_units' => $damagedUnits,
                ],
                'quantity_changed' => $receivedUnits,
                'amount' => $purchase->total_amount,
            ]);
        });

        return back()->with('success', 'Receiving saved. ' . number_format($receivedUnits) . ' units added to stock; ' . number_format($damagedUnits) . ' damaged units recorded.');
    }

    public function issues(Request $request, DepartmentAccessService $departmentAccess)
    {
        $this->authorizeStoreAccess($request);

        $context = $this->storeContext($request, $departmentAccess);

        $issues = StoreIssue::with(['fromStore', 'toStore', 'department', 'items.product', 'issuer', 'receiver'])
            ->when($context['selectedDepartmentId'], fn ($query) => $query->where('department_id', $context['selectedDepartmentId']))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $products = Product::with('department')
            ->when($context['selectedDepartmentId'], fn ($query) => $query->where('department_id', $context['selectedDepartmentId']))
            ->orderBy('name')
            ->get();

        return view('store.issues', array_merge($context, compact('issues', 'products')));
    }

    public function storeIssue(Request $request)
    {
        $this->authorizeStoreAccess($request);

        $validated = $request->validate([
            'from_store_id' => ['required', 'exists:stores,id', 'different:to_store_id'],
            'to_store_id' => ['required', 'exists:stores,id'],
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $fromStore = Store::findOrFail($validated['from_store_id']);
        $toStore = Store::findOrFail($validated['to_store_id']);
        $product = Product::findOrFail($validated['product_id']);

        $this->authorizeStore($request, $fromStore);
        $this->authorizeStore($request, $toStore);

        $issue = StoreIssue::create([
            'issue_number' => $this->nextNumber('ISS'),
            'from_store_id' => $fromStore->id,
            'to_store_id' => $toStore->id,
            'department_id' => $toStore->department_id ?: $product->department_id,
            'issued_by' => $request->user()->id,
            'status' => StoreIssue::STATUS_PENDING_APPROVAL,
            'notes' => $validated['notes'] ?? null,
        ]);

        StoreIssueItem::create([
            'store_issue_id' => $issue->id,
            'product_id' => $product->id,
            'quantity_requested' => $validated['quantity'],
            'quantity_issued' => 0,
            'quantity_received' => 0,
        ]);

        AuditLogService::record([
            'action' => 'STORE_ISSUE_REQUESTED',
            'module' => 'Inventory',
            'model' => $issue,
            'department_id' => $issue->department_id,
            'reference' => $issue->issue_number,
            'description' => 'Requested store issue from ' . $fromStore->name . ' to ' . $toStore->name . '.',
            'quantity_changed' => $validated['quantity'],
        ]);

        return back()->with('success', 'Store issue requested for approval.');
    }

    public function approveIssue(Request $request, StoreIssue $issue, StoreStockService $storeStockService)
    {
        $this->authorizeApproval($request);

        if (!$request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR') && (int) $issue->issued_by === (int) $request->user()->id) {
            return back()->with('error', 'Separation of duties: requester cannot approve their own issue.');
        }

        if ($issue->status !== StoreIssue::STATUS_PENDING_APPROVAL) {
            return back()->with('error', 'Only pending issues can be approved.');
        }

        DB::transaction(function () use ($request, $issue, $storeStockService) {
            $issue->load(['items.product', 'fromStore', 'toStore']);

            foreach ($issue->items as $item) {
                $storeStockService->transfer(
                    product: $item->product,
                    fromStore: $issue->fromStore,
                    toStore: $issue->toStore,
                    quantity: (float) $item->quantity_requested,
                    user: $request->user(),
                    referenceType: StoreIssue::class,
                    referenceId: $issue->id,
                    note: 'Approved store issue ' . $issue->issue_number
                );

                $item->update([
                    'quantity_issued' => $item->quantity_requested,
                    'quantity_received' => $item->quantity_requested,
                ]);
            }

            $issue->update([
                'approved_by' => $request->user()->id,
                'received_by' => $request->user()->id,
                'issue_date' => now(),
                'received_date' => now(),
                'status' => StoreIssue::STATUS_RECEIVED,
            ]);

            AuditLogService::record([
                'action' => 'STORE_ISSUE_APPROVED',
                'module' => 'Inventory',
                'model' => $issue,
                'department_id' => $issue->department_id,
                'reference' => $issue->issue_number,
                'description' => 'Approved and transferred stock for issue ' . $issue->issue_number . '.',
                'quantity_changed' => $issue->items->sum('quantity_requested'),
            ]);
        });

        return back()->with('success', 'Store issue approved and transferred.');
    }

    public function damages(Request $request, DepartmentAccessService $departmentAccess)
    {
        $this->authorizeStoreAccess($request);

        $context = $this->storeContext($request, $departmentAccess);

        $damages = StockDamage::with(['product', 'store', 'department', 'recorder', 'approver'])
            ->when($context['selectedDepartmentId'], fn ($query) => $query->where('department_id', $context['selectedDepartmentId']))
            ->when($context['selectedStoreId'], fn ($query) => $query->where('store_id', $context['selectedStoreId']))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $products = Product::with('department')
            ->when($context['selectedDepartmentId'], fn ($query) => $query->where('department_id', $context['selectedDepartmentId']))
            ->orderBy('name')
            ->get();

        return view('store.damages', array_merge($context, compact('damages', 'products')));
    }

    public function storeDamage(Request $request)
    {
        $this->authorizeStoreAccess($request);

        $validated = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $store = Store::findOrFail($validated['store_id']);
        $product = Product::findOrFail($validated['product_id']);

        $this->authorizeStore($request, $store);

        $damage = StockDamage::create([
            'damage_number' => $this->nextNumber('DMG'),
            'product_id' => $product->id,
            'store_id' => $store->id,
            'department_id' => $product->department_id ?: $store->department_id,
            'quantity' => $validated['quantity'],
            'reason' => $validated['reason'],
            'notes' => $validated['notes'] ?? null,
            'recorded_by' => $request->user()->id,
            'status' => StockDamage::STATUS_PENDING,
        ]);

        AuditLogService::record([
            'action' => 'DAMAGE_RECORDED',
            'module' => 'Inventory',
            'model' => $damage,
            'department_id' => $damage->department_id,
            'reference' => $damage->damage_number,
            'description' => 'Recorded damaged stock for approval. Stock was not deducted yet.',
            'quantity_changed' => $damage->quantity,
            'severity' => 'WARNING',
        ]);

        return back()->with('success', 'Damage recorded for manager approval.');
    }

    public function approveDamage(Request $request, StockDamage $damage, StoreStockService $storeStockService)
    {
        $this->authorizeApproval($request);

        if (!$request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR') && (int) $damage->recorded_by === (int) $request->user()->id) {
            return back()->with('error', 'Separation of duties: recorder cannot approve their own damage.');
        }

        if ($damage->status !== StockDamage::STATUS_PENDING) {
            return back()->with('error', 'Only pending damage records can be approved.');
        }

        DB::transaction(function () use ($request, $damage, $storeStockService) {
            $damage->load(['product', 'store']);
            $product = Product::whereKey($damage->product_id)->lockForUpdate()->firstOrFail();
            $before = (float) $product->stock;

            if ($before < (float) $damage->quantity) {
                throw new \RuntimeException('Not enough global product stock to approve this damage.');
            }

            $storeSnapshot = $storeStockService->decreaseStoreOnly($product, $damage->store, (float) $damage->quantity);
            $product->decrement('stock', (float) $damage->quantity);
            $product->refresh();

            $storeStockService->recordMovement(
                product: $product,
                user: $request->user(),
                type: 'DAMAGE',
                quantity: (float) $damage->quantity,
                beforeStock: $before,
                afterStock: (float) $product->stock,
                referenceType: StockDamage::class,
                referenceId: $damage->id,
                fromStore: $damage->store,
                quantityBefore: $storeSnapshot['before'],
                quantityAfter: $storeSnapshot['after'],
                note: 'Approved damage ' . $damage->damage_number,
                approvedBy: $request->user()->id
            );

            Stock::create([
                'product_id' => $product->id,
                'department_id' => $product->department_id,
                'type' => 'damage',
                'quantity' => $damage->quantity,
                'before_stock' => $before,
                'after_stock' => $product->stock,
                'note' => 'Approved damage ' . $damage->damage_number,
                'user_id' => $request->user()->id,
            ]);

            $damage->update([
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'status' => StockDamage::STATUS_APPROVED,
            ]);

            AuditLogService::record([
                'action' => 'DAMAGE_APPROVED',
                'module' => 'Inventory',
                'model' => $damage,
                'department_id' => $damage->department_id,
                'reference' => $damage->damage_number,
                'description' => 'Approved damaged stock and deducted it from inventory.',
                'quantity_before' => $before,
                'quantity_changed' => -abs((float) $damage->quantity),
                'quantity_after' => $product->stock,
                'severity' => 'WARNING',
            ]);
        });

        return back()->with('success', 'Damage approved and stock deducted.');
    }

    public function returns(Request $request, DepartmentAccessService $departmentAccess)
    {
        $this->authorizeStoreAccess($request);

        $context = $this->storeContext($request, $departmentAccess);

        $returns = StockReturn::with(['product', 'fromStore', 'toStore', 'supplier', 'department'])
            ->when($context['selectedDepartmentId'], fn ($query) => $query->where('department_id', $context['selectedDepartmentId']))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $products = Product::with('department')
            ->when($context['selectedDepartmentId'], fn ($query) => $query->where('department_id', $context['selectedDepartmentId']))
            ->orderBy('name')
            ->get();

        $suppliers = Supplier::where('status', Supplier::STATUS_ACTIVE)->orderBy('company_name')->get();

        return view('store.returns', array_merge($context, compact('returns', 'products', 'suppliers')));
    }

    public function storeReturn(Request $request)
    {
        $this->authorizeStoreAccess($request);

        $validated = $request->validate([
            'return_type' => ['required', 'string', 'max:50'],
            'product_id' => ['required', 'exists:products,id'],
            'from_store_id' => ['nullable', 'exists:stores,id'],
            'to_store_id' => ['nullable', 'exists:stores,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $product = Product::findOrFail($validated['product_id']);

        $return = StockReturn::create([
            'return_number' => $this->nextNumber('RTN'),
            'return_type' => strtoupper($validated['return_type']),
            'product_id' => $product->id,
            'from_store_id' => $validated['from_store_id'] ?? null,
            'to_store_id' => $validated['to_store_id'] ?? null,
            'supplier_id' => $validated['supplier_id'] ?? null,
            'department_id' => $product->department_id,
            'quantity' => $validated['quantity'],
            'reason' => $validated['reason'] ?? null,
            'status' => StockReturn::STATUS_PENDING,
            'recorded_by' => $request->user()->id,
        ]);

        AuditLogService::record([
            'action' => 'RETURN_RECORDED',
            'module' => 'Inventory',
            'model' => $return,
            'department_id' => $return->department_id,
            'reference' => $return->return_number,
            'description' => 'Recorded stock return for approval.',
            'quantity_changed' => $return->quantity,
        ]);

        return back()->with('success', 'Return recorded for approval.');
    }

    public function approveReturn(Request $request, StockReturn $return, StoreStockService $storeStockService)
    {
        $this->authorizeApproval($request);

        if (!$request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR') && (int) $return->recorded_by === (int) $request->user()->id) {
            return back()->with('error', 'Separation of duties: recorder cannot approve their own return.');
        }

        if ($return->status !== StockReturn::STATUS_PENDING) {
            return back()->with('error', 'Only pending returns can be approved.');
        }

        DB::transaction(function () use ($request, $return, $storeStockService) {
            $return->load(['product', 'fromStore', 'toStore']);
            $product = Product::whereKey($return->product_id)->lockForUpdate()->firstOrFail();
            $quantity = (float) $return->quantity;
            $productBefore = (float) $product->stock;

            if ($return->fromStore && $return->toStore) {
                $storeStockService->transfer(
                    product: $product,
                    fromStore: $return->fromStore,
                    toStore: $return->toStore,
                    quantity: $quantity,
                    user: $request->user(),
                    referenceType: StockReturn::class,
                    referenceId: $return->id,
                    note: 'Approved return ' . $return->return_number
                );
            } elseif ($return->fromStore) {
                if ($productBefore < $quantity) {
                    throw new \RuntimeException('Not enough product stock to approve this return.');
                }

                $snapshot = $storeStockService->decreaseStoreOnly($product, $return->fromStore, $quantity);
                $product->decrement('stock', $quantity);
                $product->refresh();

                $storeStockService->recordMovement(
                    product: $product,
                    user: $request->user(),
                    type: $return->return_type === 'SUPPLIER_RETURN' ? 'SUPPLIER_RETURN' : 'RETURN',
                    quantity: $quantity,
                    beforeStock: $productBefore,
                    afterStock: (float) $product->stock,
                    referenceType: StockReturn::class,
                    referenceId: $return->id,
                    fromStore: $return->fromStore,
                    quantityBefore: $snapshot['before'],
                    quantityAfter: $snapshot['after'],
                    note: 'Approved return ' . $return->return_number,
                    approvedBy: $request->user()->id
                );
            } elseif ($return->toStore) {
                $product->increment('stock', $quantity);
                $product->refresh();
                $snapshot = $storeStockService->increaseStoreOnly($product, $return->toStore, $quantity);

                $storeStockService->recordMovement(
                    product: $product,
                    user: $request->user(),
                    type: 'RETURN',
                    quantity: $quantity,
                    beforeStock: $productBefore,
                    afterStock: (float) $product->stock,
                    referenceType: StockReturn::class,
                    referenceId: $return->id,
                    toStore: $return->toStore,
                    quantityBefore: $snapshot['before'],
                    quantityAfter: $snapshot['after'],
                    note: 'Approved return ' . $return->return_number,
                    approvedBy: $request->user()->id
                );
            }

            $return->update([
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'status' => StockReturn::STATUS_APPROVED,
            ]);

            AuditLogService::record([
                'action' => 'RETURN_APPROVED',
                'module' => 'Inventory',
                'model' => $return,
                'department_id' => $return->department_id,
                'reference' => $return->return_number,
                'description' => 'Approved stock return ' . $return->return_number . '.',
                'quantity_changed' => $return->quantity,
            ]);
        });

        return back()->with('success', 'Return approved and stock movement recorded.');
    }

    public function movements(Request $request, DepartmentAccessService $departmentAccess)
    {
        $this->authorizeStoreAccess($request);

        $context = $this->storeContext($request, $departmentAccess);

        $movements = $this->filteredMovements($context)
            ->with(['product', 'department', 'fromStore', 'toStore', 'user'])
            ->when($request->filled('movement_type'), function ($query) use ($request) {
                $query->where(function ($inner) use ($request) {
                    $inner->where('movement_type', $request->movement_type)
                        ->orWhere('type', $request->movement_type);
                });
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('reason', 'like', '%' . $request->search . '%')
                    ->orWhereHas('product', fn ($product) => $product->where('name', 'like', '%' . $request->search . '%'));
            });

        $this->applyDateFilter($movements, $request);

        $movements = $movements
            ->latest()
            ->paginate((int) $request->input('per_page', 20))
            ->withQueryString();

        return view('store.movements', array_merge($context, compact('movements')));
    }

    private function storeContext(Request $request, DepartmentAccessService $departmentAccess): array
    {
        $selectedDepartmentId = $departmentAccess->selectedDepartmentId(
            $request->user(),
            $request->integer('department_id') ?: null
        );

        $departments = $departmentAccess->visibleDepartments($request->user());
        $stores = $this->visibleStores($request, $selectedDepartmentId);
        $selectedStoreId = $request->integer('store_id') ?: null;

        if ($selectedStoreId && !$stores->pluck('id')->contains($selectedStoreId)) {
            abort(403);
        }

        return compact('departments', 'stores', 'selectedDepartmentId', 'selectedStoreId');
    }

    private function visibleStores(Request $request, ?int $selectedDepartmentId = null)
    {
        $query = Store::with('department')->where('active', true);
        $user = $request->user();

        if ($selectedDepartmentId) {
            $query->where(function ($stores) use ($selectedDepartmentId) {
                $stores->where('department_id', $selectedDepartmentId)
                    ->orWhereNull('department_id');
            });
        }

        if ($user->hasOperationalRole('KITCHEN_MANAGER', 'KITCHEN_CHIEF')) {
            $query->where('code', 'KITCHEN');
        } elseif ($user->hasOperationalRole('BAR_MANAGER', 'BAR_CHIEF', 'BARTENDER')) {
            $query->where('code', 'BAR');
        }

        return $query->orderBy('sort_order')->orderBy('name')->get();
    }

    private function filteredPurchases(array $context)
    {
        return Purchase::query()
            ->when($context['selectedDepartmentId'] ?? null, fn ($query) => $query->where('department_id', $context['selectedDepartmentId']))
            ->when($context['selectedStoreId'] ?? null, fn ($query) => $query->where('store_id', $context['selectedStoreId']));
    }

    private function filteredRequisitions(array $context)
    {
        return StockRequisition::query()
            ->when($context['selectedDepartmentId'] ?? null, fn ($query) => $query->where('department_id', $context['selectedDepartmentId']));
    }

    private function filteredMovements(array $context)
    {
        return StockMovement::query()
            ->when($context['selectedDepartmentId'] ?? null, fn ($query) => $query->where('department_id', $context['selectedDepartmentId']))
            ->when($context['selectedStoreId'] ?? null, function ($query) use ($context) {
                $query->where(function ($movement) use ($context) {
                    $movement->where('from_store_id', $context['selectedStoreId'])
                        ->orWhere('to_store_id', $context['selectedStoreId']);
                });
            });
    }

    private function applyDateFilter($query, Request $request): void
    {
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59',
            ]);

            return;
        }

        match ($request->input('filter')) {
            'today' => $query->whereDate('created_at', today()),
            'yesterday' => $query->whereDate('created_at', today()->subDay()),
            'week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'last_week' => $query->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()]),
            'month' => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
            'last_month' => $query->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year),
            'year' => $query->whereYear('created_at', now()->year),
            default => null,
        };
    }

    private function authorizeStoreAccess(Request $request): void
    {
        abort_unless(
            $request->user()->hasOperationalRole(
                'ADMIN',
                'ADMINISTRATOR',
                'MANAGER',
                'STORE_KEEPER',
                'KITCHEN_MANAGER',
                'KITCHEN_CHIEF',
                'BAR_MANAGER',
                'BAR_CHIEF',
                'BARTENDER'
            ),
            403
        );
    }

    private function authorizeApproval(Request $request): void
    {
        abort_unless(
            $request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER'),
            403
        );
    }

    private function authorizeReceiving(Request $request): void
    {
        abort_unless(
            $request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER', 'STORE_KEEPER'),
            403
        );
    }

    private function authorizeStore(Request $request, Store $store): void
    {
        if ($request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER', 'STORE_KEEPER')) {
            return;
        }

        abort_unless(
            $store->department_id && $request->user()->canAccessDepartment($store->department_id),
            403
        );
    }

    private function nextNumber(string $prefix): string
    {
        return $prefix . '-' . now()->format('Ymd-His') . '-' . random_int(100, 999);
    }
}
