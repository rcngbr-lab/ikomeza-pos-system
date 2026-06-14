<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->patchBranchColumns();
        $this->backfillBranchColumns();
        $this->patchPayments();
        $this->patchTaxColumns();
        $this->patchSyncOutbox();
        $this->createSyncInbox();
        $this->createProductBatches();
        $this->createDiningAreas();
        $this->createSplitBillTables();
        $this->createDiscountApprovals();
        $this->createErrorEvents();
        $this->patchBackupRuns();
        $this->addPerformanceIndexes();
        $this->addForeignKeysWhenSafe();
    }

    public function down(): void
    {
        Schema::dropIfExists('error_events');
        Schema::dropIfExists('discount_approvals');
        Schema::dropIfExists('sale_split_items');
        Schema::dropIfExists('sale_splits');
        Schema::dropIfExists('dining_areas');
        Schema::dropIfExists('product_batches');
        Schema::dropIfExists('sync_failures');
        Schema::dropIfExists('sync_inbox');
    }

    private function patchBranchColumns(): void
    {
        foreach ([
            'products',
            'categories',
            'stocks',
            'store_stocks',
            'stores',
            'suppliers',
            'purchases',
            'purchase_items',
            'store_issues',
            'store_issue_items',
            'stock_damages',
            'stock_returns',
            'stock_count_items',
            'stock_requisitions',
            'order_tickets',
            'order_ticket_items',
            'customers',
            'payments',
            'refund_requests',
            'backup_runs',
        ] as $table) {
            $this->addUnsigned($table, 'branch_id', true);
        }
    }

    private function backfillBranchColumns(): void
    {
        if (!Schema::hasTable('branches')) {
            return;
        }

        $branchId = DB::table('branches')->orderBy('id')->value('id');

        if (!$branchId) {
            return;
        }

        foreach ([
            'products',
            'categories',
            'stocks',
            'store_stocks',
            'stores',
            'suppliers',
            'purchases',
            'stock_damages',
            'stock_returns',
            'stock_counts',
            'stock_requisitions',
            'store_issues',
            'order_tickets',
            'customers',
            'payments',
            'refund_requests',
        ] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'branch_id')) {
                DB::table($table)->whereNull('branch_id')->update(['branch_id' => $branchId]);
            }
        }
    }

    private function patchPayments(): void
    {
        if (!Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $this->addString($table, 'payments', 'provider_name', 80, null);
            $this->addString($table, 'payments', 'payment_reference', 160, null);
            $this->addString($table, 'payments', 'transaction_id', 160, null);
            $this->addString($table, 'payments', 'payment_status', 40, 'COMPLETED');
            $this->addString($table, 'payments', 'reconciliation_status', 40, 'UNMATCHED');
            $this->addUnsignedInTable($table, 'payments', 'reconciled_by', true);
            $this->addTimestamp($table, 'payments', 'reconciled_at');
            $this->addText($table, 'payments', 'reconciliation_notes');
            $this->addString($table, 'payments', 'idempotency_key', 120, null);
        });

        $this->safeIndex('payments', ['branch_id', 'method', 'paid_at'], 'payments_branch_method_paid_idx');
        $this->safeIndex('payments', ['reconciliation_status', 'paid_at'], 'payments_recon_paid_idx');
        $this->safeIndex('payments', ['payment_reference'], 'payments_payment_ref_idx');
        $this->safeIndex('payments', ['transaction_id'], 'payments_transaction_idx');
        $this->safeUnique('payments', ['idempotency_key'], 'payments_idempotency_unique');
    }

    private function patchTaxColumns(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $this->addBoolean($table, 'products', 'is_taxable', true);
            });
        }

        if (Schema::hasTable('sale_items')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $this->addBoolean($table, 'sale_items', 'is_taxable', true);
                $this->addString($table, 'sale_items', 'tax_category', 40, 'VATABLE');
            });
        }

        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                $this->addJson($table, 'sales', 'tax_summary');
            });
        }
    }

    private function patchSyncOutbox(): void
    {
        if (!Schema::hasTable('sync_outbox')) {
            return;
        }

        Schema::table('sync_outbox', function (Blueprint $table) {
            $this->addString($table, 'sync_outbox', 'device_id', 120, null);
            $this->addUnsignedInTable($table, 'sync_outbox', 'branch_id', true);
            $this->addString($table, 'sync_outbox', 'idempotency_key', 160, null);
            $this->addString($table, 'sync_outbox', 'sync_status', 40, 'PENDING');
            $this->addTimestamp($table, 'sync_outbox', 'next_attempt_at');
            $this->addTimestamp($table, 'sync_outbox', 'last_synced_at');
            $this->addString($table, 'sync_outbox', 'conflict_status', 40, 'NONE');
        });

        $this->safeIndex('sync_outbox', ['branch_id', 'sync_status'], 'sync_outbox_branch_status_idx');
        $this->safeUnique('sync_outbox', ['idempotency_key'], 'sync_outbox_idempotency_unique');
    }

    private function createSyncInbox(): void
    {
        if (!Schema::hasTable('sync_inbox')) {
            Schema::create('sync_inbox', function (Blueprint $table) {
                $table->id();
                $table->string('device_id', 120)->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->string('idempotency_key', 160)->unique();
                $table->string('event_type', 80)->index();
                $table->string('model_type')->nullable()->index();
                $table->unsignedBigInteger('model_id')->nullable()->index();
                $table->json('payload')->nullable();
                $table->string('sync_status', 40)->default('PENDING')->index();
                $table->string('conflict_status', 40)->default('NONE')->index();
                $table->json('conflict_payload')->nullable();
                $table->unsignedInteger('attempts')->default(0);
                $table->timestamp('last_attempt_at')->nullable();
                $table->timestamp('last_synced_at')->nullable();
                $table->text('last_error')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sync_failures')) {
            Schema::create('sync_failures', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sync_inbox_id')->nullable()->index();
                $table->unsignedBigInteger('sync_outbox_id')->nullable()->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->string('device_id', 120)->nullable()->index();
                $table->string('event_type', 80)->nullable()->index();
                $table->string('failure_type', 80)->default('ERROR')->index();
                $table->text('message')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }
    }

    private function createProductBatches(): void
    {
        if (Schema::hasTable('product_batches')) {
            return;
        }

        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('store_id')->nullable()->index();
            $table->unsignedBigInteger('supplier_id')->nullable()->index();
            $table->unsignedBigInteger('purchase_item_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('batch_number', 120)->nullable()->index();
            $table->decimal('quantity_received', 15, 3)->default(0);
            $table->decimal('quantity_remaining', 15, 3)->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->date('received_date')->nullable()->index();
            $table->date('expiry_date')->nullable()->index();
            $table->string('status', 40)->default('ACTIVE')->index();
            $table->timestamps();

            $table->unique(['product_id', 'store_id', 'batch_number'], 'product_batches_product_store_batch_unique');
        });
    }

    private function createDiningAreas(): void
    {
        if (!Schema::hasTable('dining_areas')) {
            Schema::create('dining_areas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->string('code', 60)->index();
                $table->string('name');
                $table->string('service_type', 40)->default('DINING')->index();
                $table->boolean('active')->default(true);
                $table->timestamps();

                $table->unique(['branch_id', 'code'], 'dining_areas_branch_code_unique');
            });
        }

        if (Schema::hasTable('restaurant_tables') && !Schema::hasColumn('restaurant_tables', 'dining_area_id')) {
            Schema::table('restaurant_tables', function (Blueprint $table) {
                $table->unsignedBigInteger('dining_area_id')->nullable()->index();
                $table->unsignedBigInteger('merged_with_table_id')->nullable()->index();
                $table->timestamp('transferred_at')->nullable();
                $table->unsignedBigInteger('transferred_by')->nullable()->index();
            });
        }
    }

    private function createSplitBillTables(): void
    {
        if (!Schema::hasTable('sale_splits')) {
            Schema::create('sale_splits', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sale_id')->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->string('split_number', 80)->unique();
                $table->string('split_type', 40)->default('AMOUNT')->index();
                $table->decimal('amount_due', 15, 2)->default(0);
                $table->decimal('amount_paid', 15, 2)->default(0);
                $table->string('status', 40)->default('OPEN')->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sale_split_items')) {
            Schema::create('sale_split_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sale_split_id')->index();
                $table->unsignedBigInteger('sale_item_id')->index();
                $table->decimal('quantity', 15, 3)->default(0);
                $table->decimal('amount', 15, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    private function createDiscountApprovals(): void
    {
        if (Schema::hasTable('discount_approvals')) {
            return;
        }

        Schema::create('discount_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id')->nullable()->index();
            $table->unsignedBigInteger('requested_by')->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('discount_percent', 8, 3)->default(0);
            $table->string('status', 40)->default('PENDING')->index();
            $table->text('reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    private function createErrorEvents(): void
    {
        if (Schema::hasTable('error_events')) {
            return;
        }

        Schema::create('error_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('source', 80)->default('APPLICATION')->index();
            $table->string('severity', 40)->default('ERROR')->index();
            $table->string('message', 500);
            $table->text('context')->nullable();
            $table->string('status', 40)->default('OPEN')->index();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    private function patchBackupRuns(): void
    {
        if (!Schema::hasTable('backup_runs')) {
            return;
        }

        Schema::table('backup_runs', function (Blueprint $table) {
            $this->addTimestamp($table, 'backup_runs', 'verified_at');
            $this->addString($table, 'backup_runs', 'failure_type', 80, null);
            $this->addString($table, 'backup_runs', 'disk', 80, 'local');
        });
    }

    private function addPerformanceIndexes(): void
    {
        foreach ([
            ['sales', ['branch_id', 'sale_status', 'created_at'], 'sales_branch_status_created_idx'],
            ['sales', ['user_id', 'created_at'], 'sales_user_created_idx'],
            ['sales', ['shift_id', 'sale_status'], 'sales_shift_status_idx'],
            ['sales', ['receipt_no'], 'sales_receipt_idx'],
            ['sale_items', ['sale_id', 'department_id'], 'sale_items_sale_dept_idx'],
            ['sale_items', ['product_id', 'created_at'], 'sale_items_product_created_idx'],
            ['products', ['branch_id', 'department_id', 'active'], 'products_branch_dept_active_idx'],
            ['products', ['barcode'], 'products_barcode_idx'],
            ['stocks', ['product_id', 'created_at'], 'stocks_product_created_idx'],
            ['stock_movements', ['branch_id', 'movement_type', 'created_at'], 'stock_movements_branch_type_created_idx'],
            ['stock_movements', ['product_id', 'created_at'], 'stock_movements_product_created_idx'],
            ['users', ['branch_id', 'status'], 'users_branch_status_idx'],
            ['users', ['username'], 'users_username_idx'],
            ['branches', ['code'], 'branches_code_idx'],
            ['shifts', ['branch_id', 'status', 'created_at'], 'shifts_branch_status_created_idx'],
            ['refunds', ['sale_id', 'status'], 'refunds_sale_status_idx'],
            ['purchases', ['branch_id', 'status', 'purchase_date'], 'purchases_branch_status_date_idx'],
            ['audit_logs', ['branch_id', 'module', 'created_at'], 'audit_logs_branch_module_created_idx'],
            ['audit_logs', ['user_id', 'created_at'], 'audit_logs_user_created_idx'],
        ] as [$table, $columns, $name]) {
            $this->safeIndex($table, $columns, $name);
        }
    }

    private function addForeignKeysWhenSafe(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        foreach ([
            ['payments', 'sale_id', 'sales'],
            ['payments', 'shift_id', 'shifts'],
            ['payments', 'customer_id', 'customers'],
            ['payments', 'received_by', 'users'],
            ['payments', 'reconciled_by', 'users'],
            ['product_batches', 'product_id', 'products'],
            ['product_batches', 'store_id', 'stores'],
            ['product_batches', 'supplier_id', 'suppliers'],
            ['product_batches', 'purchase_item_id', 'purchase_items'],
            ['sync_inbox', 'branch_id', 'branches'],
            ['sync_outbox', 'branch_id', 'branches'],
            ['stock_movements', 'branch_id', 'branches'],
            ['stock_movements', 'performed_by', 'users'],
            ['stock_movements', 'approved_by', 'users'],
            ['sale_splits', 'sale_id', 'sales'],
            ['sale_splits', 'customer_id', 'customers'],
            ['sale_split_items', 'sale_split_id', 'sale_splits'],
            ['sale_split_items', 'sale_item_id', 'sale_items'],
        ] as [$table, $column, $parent]) {
            $this->safeForeign($table, $column, $parent);
        }
    }

    private function addUnsigned(string $tableName, string $column, bool $nullable = false): void
    {
        if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, $column)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName, $column, $nullable) {
            $this->addUnsignedInTable($table, $tableName, $column, $nullable);
        });
    }

    private function addUnsignedInTable(Blueprint $table, string $tableName, string $column, bool $nullable = false): void
    {
        if (Schema::hasColumn($tableName, $column)) {
            return;
        }

        $definition = $table->unsignedBigInteger($column);

        if ($nullable) {
            $definition->nullable();
        }

        $definition->index();
    }

    private function addString(Blueprint $table, string $tableName, string $column, int $length, ?string $default): void
    {
        if (Schema::hasColumn($tableName, $column)) {
            return;
        }

        $definition = $table->string($column, $length)->nullable($default === null);

        if ($default !== null) {
            $definition->default($default);
        }
    }

    private function addText(Blueprint $table, string $tableName, string $column): void
    {
        if (!Schema::hasColumn($tableName, $column)) {
            $table->text($column)->nullable();
        }
    }

    private function addTimestamp(Blueprint $table, string $tableName, string $column): void
    {
        if (!Schema::hasColumn($tableName, $column)) {
            $table->timestamp($column)->nullable();
        }
    }

    private function addJson(Blueprint $table, string $tableName, string $column): void
    {
        if (!Schema::hasColumn($tableName, $column)) {
            $table->json($column)->nullable();
        }
    }

    private function addBoolean(Blueprint $table, string $tableName, string $column, bool $default): void
    {
        if (!Schema::hasColumn($tableName, $column)) {
            $table->boolean($column)->default($default);
        }
    }

    private function safeIndex(string $table, array $columns, string $name): void
    {
        if (!Schema::hasTable($table) || $this->hasIndex($table, $name)) {
            return;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return;
            }
        }

        try {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
        } catch (\Throwable) {
            // Existing installs may already have equivalent indexes with generated names.
        }
    }

    private function safeUnique(string $table, array $columns, string $name): void
    {
        if (!Schema::hasTable($table) || $this->hasIndex($table, $name)) {
            return;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return;
            }
        }

        try {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->unique($columns, $name));
        } catch (\Throwable) {
            // Keep migration non-destructive if legacy data already has duplicates.
        }
    }

    private function safeForeign(string $table, string $column, string $parent): void
    {
        if (
            !Schema::hasTable($table)
            || !Schema::hasTable($parent)
            || !Schema::hasColumn($table, $column)
            || $this->hasOrphans($table, $column, $parent)
        ) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $parent) {
                $blueprint->foreign($column)->references('id')->on($parent)->restrictOnDelete();
            });
        } catch (\Throwable) {
            // Existing constraints or legacy database engines can safely skip this pass.
        }
    }

    private function hasIndex(string $table, string $name): bool
    {
        try {
            return collect(Schema::getIndexes($table))
                ->contains(fn ($index) => ($index['name'] ?? null) === $name);
        } catch (\Throwable) {
            return false;
        }
    }

    private function hasOrphans(string $table, string $column, string $parent): bool
    {
        return DB::table($table)
            ->leftJoin($parent, $table . '.' . $column, '=', $parent . '.id')
            ->whereNotNull($table . '.' . $column)
            ->whereNull($parent . '.id')
            ->exists();
    }
};
