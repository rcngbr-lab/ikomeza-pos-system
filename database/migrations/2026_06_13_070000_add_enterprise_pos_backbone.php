<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createBusinessSettings();
        $this->createCustomers();
        $this->createPayments();
        $this->createCustomerLedger();
        $this->createRestaurantTables();
        $this->createOrderTickets();
        $this->createRefundRequests();
        $this->createStockCounts();
        $this->createAccountingTables();
        $this->createOperationalControlTables();
        $this->patchSales();
        $this->patchSaleItems();
        $this->patchPurchases();
        $this->patchSuppliers();
        $this->patchProducts();
        $this->patchShifts();
        $this->seedBusinessSettings();
        $this->seedRestaurantTables();
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_notifications');
        Schema::dropIfExists('backup_runs');
        Schema::dropIfExists('sync_outbox');
        Schema::dropIfExists('supplier_ledger_entries');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('ledger_accounts');
        Schema::dropIfExists('stock_count_items');
        Schema::dropIfExists('stock_counts');
        Schema::dropIfExists('refund_requests');
        Schema::dropIfExists('order_ticket_items');
        Schema::dropIfExists('order_tickets');
        Schema::dropIfExists('restaurant_tables');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('customer_ledger_entries');
        Schema::dropIfExists('business_settings');
    }

    private function createBusinessSettings(): void
    {
        if (Schema::hasTable('business_settings')) {
            return;
        }

        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type', 30)->default('string');
            $table->string('group', 50)->default('general')->index();
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    private function createCustomers(): void
    {
        if (Schema::hasTable('customers')) {
            return;
        }

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code')->unique();
            $table->string('name');
            $table->string('phone')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('tin')->nullable()->index();
            $table->text('address')->nullable();
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    private function createPayments(): void
    {
        if (Schema::hasTable('payments')) {
            return;
        }

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id')->index();
            $table->unsignedBigInteger('shift_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('received_by')->nullable()->index();
            $table->string('method', 40)->index();
            $table->decimal('amount', 15, 2);
            $table->decimal('change_amount', 15, 2)->default(0);
            $table->string('reference')->nullable()->index();
            $table->string('status', 30)->default('COMPLETED')->index();
            $table->timestamp('paid_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    private function createCustomerLedger(): void
    {
        if (Schema::hasTable('customer_ledger_entries')) {
            return;
        }

        Schema::create('customer_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('sale_id')->nullable()->index();
            $table->string('entry_type', 40)->index();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('balance_after', 15, 2)->default(0);
            $table->string('payment_method', 40)->nullable();
            $table->string('reference')->nullable()->index();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });
    }

    private function createRestaurantTables(): void
    {
        if (Schema::hasTable('restaurant_tables')) {
            return;
        }

        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('table_code')->unique();
            $table->string('name');
            $table->string('section')->nullable()->index();
            $table->unsignedInteger('seats')->default(4);
            $table->string('status', 30)->default('AVAILABLE')->index();
            $table->unsignedBigInteger('assigned_user_id')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    private function createOrderTickets(): void
    {
        if (!Schema::hasTable('order_tickets')) {
            Schema::create('order_tickets', function (Blueprint $table) {
                $table->id();
                $table->string('ticket_number')->unique();
                $table->unsignedBigInteger('sale_id')->index();
                $table->unsignedBigInteger('department_id')->nullable()->index();
                $table->unsignedBigInteger('table_id')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('assigned_to')->nullable()->index();
                $table->string('ticket_type', 20)->default('KOT')->index();
                $table->string('status', 40)->default('PENDING')->index();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('ready_at')->nullable();
                $table->timestamp('served_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('order_ticket_items')) {
            Schema::create('order_ticket_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_ticket_id')->index();
                $table->unsignedBigInteger('sale_item_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('product_name');
                $table->decimal('quantity', 15, 3)->default(0);
                $table->string('unit')->nullable();
                $table->string('status', 40)->default('PENDING')->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    private function createRefundRequests(): void
    {
        if (Schema::hasTable('refund_requests')) {
            return;
        }

        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->unsignedBigInteger('sale_id')->index();
            $table->unsignedBigInteger('requested_by')->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->unsignedBigInteger('executed_by')->nullable()->index();
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('reason')->nullable();
            $table->string('status', 40)->default('PENDING_APPROVAL')->index();
            $table->timestamp('requested_at')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('approval_note')->nullable();
            $table->timestamps();
        });
    }

    private function createStockCounts(): void
    {
        if (!Schema::hasTable('stock_counts')) {
            Schema::create('stock_counts', function (Blueprint $table) {
                $table->id();
                $table->string('count_number')->unique();
                $table->unsignedBigInteger('store_id')->index();
                $table->unsignedBigInteger('department_id')->nullable()->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('counted_by')->nullable()->index();
                $table->unsignedBigInteger('approved_by')->nullable()->index();
                $table->string('status', 40)->default('DRAFT')->index();
                $table->date('count_date')->nullable()->index();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('stock_count_items')) {
            Schema::create('stock_count_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('stock_count_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->string('barcode')->nullable()->index();
                $table->decimal('system_quantity', 15, 3)->default(0);
                $table->decimal('counted_quantity', 15, 3)->default(0);
                $table->decimal('variance_quantity', 15, 3)->default(0);
                $table->decimal('unit_cost', 15, 2)->default(0);
                $table->decimal('variance_value', 15, 2)->default(0);
                $table->text('reason')->nullable();
                $table->timestamps();
            });
        }
    }

    private function createAccountingTables(): void
    {
        if (!Schema::hasTable('ledger_accounts')) {
            Schema::create('ledger_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->string('type', 40)->index();
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('journal_entries')) {
            Schema::create('journal_entries', function (Blueprint $table) {
                $table->id();
                $table->string('entry_number')->unique();
                $table->date('entry_date')->index();
                $table->string('source_type')->nullable()->index();
                $table->unsignedBigInteger('source_id')->nullable()->index();
                $table->string('reference')->nullable()->index();
                $table->text('description')->nullable();
                $table->decimal('total_debit', 15, 2)->default(0);
                $table->decimal('total_credit', 15, 2)->default(0);
                $table->unsignedBigInteger('posted_by')->nullable()->index();
                $table->string('status', 30)->default('POSTED')->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('journal_lines')) {
            Schema::create('journal_lines', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('journal_entry_id')->index();
                $table->unsignedBigInteger('ledger_account_id')->nullable()->index();
                $table->string('account_code')->index();
                $table->string('account_name');
                $table->decimal('debit', 15, 2)->default(0);
                $table->decimal('credit', 15, 2)->default(0);
                $table->unsignedBigInteger('department_id')->nullable()->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->text('memo')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('supplier_ledger_entries')) {
            Schema::create('supplier_ledger_entries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('supplier_id')->index();
                $table->unsignedBigInteger('purchase_id')->nullable()->index();
                $table->string('entry_type', 40)->index();
                $table->decimal('debit', 15, 2)->default(0);
                $table->decimal('credit', 15, 2)->default(0);
                $table->decimal('balance_after', 15, 2)->default(0);
                $table->string('reference')->nullable()->index();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    private function createOperationalControlTables(): void
    {
        if (!Schema::hasTable('sync_outbox')) {
            Schema::create('sync_outbox', function (Blueprint $table) {
                $table->id();
                $table->string('event_type')->index();
                $table->string('model_type')->nullable()->index();
                $table->unsignedBigInteger('model_id')->nullable()->index();
                $table->json('payload')->nullable();
                $table->string('status', 30)->default('PENDING')->index();
                $table->unsignedInteger('attempts')->default(0);
                $table->timestamp('last_attempt_at')->nullable();
                $table->text('last_error')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('backup_runs')) {
            Schema::create('backup_runs', function (Blueprint $table) {
                $table->id();
                $table->string('backup_name')->unique();
                $table->string('path');
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->string('status', 30)->default('COMPLETED')->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('approval_notifications')) {
            Schema::create('approval_notifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('role_name')->nullable()->index();
                $table->string('module', 80)->index();
                $table->string('action_required', 80)->index();
                $table->string('reference')->nullable()->index();
                $table->string('status', 30)->default('UNREAD')->index();
                $table->json('metadata')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    private function patchSales(): void
    {
        if (!Schema::hasTable('sales')) {
            return;
        }

        Schema::table('sales', function (Blueprint $table) {
            $this->addUnsignedBigInteger($table, 'sales', 'customer_id', true);
            $this->addUnsignedBigInteger($table, 'sales', 'table_id', true);
            $this->addDecimal($table, 'sales', 'taxable_amount', 15, 2, 0);
            $this->addDecimal($table, 'sales', 'vat_rate', 6, 3, 0);
            $this->addString($table, 'sales', 'fiscal_status', 40, 'NOT_SUBMITTED');
            $this->addString($table, 'sales', 'fiscal_receipt_no', 120, null);
            $this->addJson($table, 'sales', 'fiscal_payload');
            $this->addDecimal($table, 'sales', 'credit_due', 15, 2, 0);
            $this->addString($table, 'sales', 'discount_reason', 255, null);
            $this->addUnsignedBigInteger($table, 'sales', 'discount_approved_by', true);
        });
    }

    private function patchSaleItems(): void
    {
        if (!Schema::hasTable('sale_items')) {
            return;
        }

        Schema::table('sale_items', function (Blueprint $table) {
            $this->addDecimal($table, 'sale_items', 'taxable_amount', 15, 2, 0);
            $this->addDecimal($table, 'sale_items', 'vat_rate', 6, 3, 0);
            $this->addDecimal($table, 'sale_items', 'vat_amount', 15, 2, 0);
            $this->addString($table, 'sale_items', 'ticket_status', 40, 'PENDING');
        });
    }

    private function patchPurchases(): void
    {
        if (!Schema::hasTable('purchases')) {
            return;
        }

        Schema::table('purchases', function (Blueprint $table) {
            $this->addDecimal($table, 'purchases', 'paid_amount', 15, 2, 0);
            $this->addDecimal($table, 'purchases', 'balance_due', 15, 2, 0);
            $this->addString($table, 'purchases', 'accounting_status', 40, 'UNPOSTED');
        });

        if (!Schema::hasTable('purchase_items')) {
            return;
        }

        Schema::table('purchase_items', function (Blueprint $table) {
            $this->addDecimal($table, 'purchase_items', 'quantity_difference', 15, 3, 0);
        });
    }

    private function patchSuppliers(): void
    {
        if (!Schema::hasTable('suppliers')) {
            return;
        }

        Schema::table('suppliers', function (Blueprint $table) {
            $this->addDecimal($table, 'suppliers', 'opening_balance', 15, 2, 0);
            $this->addDecimal($table, 'suppliers', 'current_balance', 15, 2, 0);
            $this->addDecimal($table, 'suppliers', 'reliability_score', 6, 2, 0);
        });
    }

    private function patchProducts(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $this->addInteger($table, 'products', 'expiry_alert_days', 30);
            $this->addString($table, 'products', 'tax_category', 40, 'VATABLE');
        });
    }

    private function patchShifts(): void
    {
        if (!Schema::hasTable('shifts')) {
            return;
        }

        Schema::table('shifts', function (Blueprint $table) {
            $this->addTimestamp($table, 'shifts', 'locked_at');
            $this->addUnsignedBigInteger($table, 'shifts', 'locked_by', true);
        });
    }

    private function seedBusinessSettings(): void
    {
        if (!Schema::hasTable('business_settings')) {
            return;
        }

        $settings = [
            ['key' => 'business_name', 'value' => 'IKOMEZA POS', 'type' => 'string', 'group' => 'business', 'label' => 'Business Name'],
            ['key' => 'business_tin', 'value' => null, 'type' => 'string', 'group' => 'tax', 'label' => 'Rwanda TIN'],
            ['key' => 'vat_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'tax', 'label' => 'VAT Enabled'],
            ['key' => 'vat_rate', 'value' => '18', 'type' => 'decimal', 'group' => 'tax', 'label' => 'VAT Rate'],
            ['key' => 'prices_include_vat', 'value' => '1', 'type' => 'boolean', 'group' => 'tax', 'label' => 'Prices Include VAT'],
            ['key' => 'fiscal_ebm_mode', 'value' => 'MANUAL', 'type' => 'string', 'group' => 'tax', 'label' => 'Fiscal EBM Mode'],
            ['key' => 'discount_cashier_limit', 'value' => '0', 'type' => 'decimal', 'group' => 'controls', 'label' => 'Cashier Discount Limit'],
            ['key' => 'discount_waiter_limit', 'value' => '0', 'type' => 'decimal', 'group' => 'controls', 'label' => 'Waiter Discount Limit'],
            ['key' => 'discount_manager_limit', 'value' => '10', 'type' => 'decimal', 'group' => 'controls', 'label' => 'Manager Discount Limit Percent'],
            ['key' => 'backup_retention_days', 'value' => '30', 'type' => 'integer', 'group' => 'backup', 'label' => 'Backup Retention Days'],
        ];

        foreach ($settings as $setting) {
            DB::table('business_settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, ['updated_at' => now(), 'created_at' => now()])
            );
        }
    }

    private function seedRestaurantTables(): void
    {
        if (!Schema::hasTable('restaurant_tables') || DB::table('restaurant_tables')->exists()) {
            return;
        }

        for ($i = 1; $i <= 12; $i++) {
            DB::table('restaurant_tables')->insert([
                'table_code' => 'T' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'name' => 'Table ' . $i,
                'section' => $i <= 6 ? 'Restaurant' : 'Bar',
                'seats' => $i <= 6 ? 4 : 2,
                'status' => 'AVAILABLE',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function addUnsignedBigInteger(Blueprint $table, string $tableName, string $column, bool $nullable = false): void
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

    private function addDecimal(Blueprint $table, string $tableName, string $column, int $precision, int $scale, float|int $default): void
    {
        if (!Schema::hasColumn($tableName, $column)) {
            $table->decimal($column, $precision, $scale)->default($default);
        }
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

    private function addJson(Blueprint $table, string $tableName, string $column): void
    {
        if (!Schema::hasColumn($tableName, $column)) {
            $table->json($column)->nullable();
        }
    }

    private function addInteger(Blueprint $table, string $tableName, string $column, int $default): void
    {
        if (!Schema::hasColumn($tableName, $column)) {
            $table->integer($column)->default($default);
        }
    }

    private function addTimestamp(Blueprint $table, string $tableName, string $column): void
    {
        if (!Schema::hasColumn($tableName, $column)) {
            $table->timestamp($column)->nullable();
        }
    }
};
