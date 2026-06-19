<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->patchCustomers();
        $this->createCreditAccounts();
        $this->createApprovalLevels();
        $this->createApprovalRequests();
        $this->createCreditTransactions();
        $this->createCreditPayments();
        $this->createCreditNotes();
        $this->createCreditCollections();
        $this->createCustomerStatements();
        $this->createAgingSnapshots();
        $this->createBadDebts();
        $this->patchCustomerLedger();
        $this->seedApprovalLevels();
        $this->backfillCreditAccounts();
    }

    public function down(): void
    {
        Schema::dropIfExists('bad_debts');
        Schema::dropIfExists('aging_snapshots');
        Schema::dropIfExists('customer_statements');
        Schema::dropIfExists('credit_collections');
        Schema::dropIfExists('credit_notes');
        Schema::dropIfExists('credit_payments');
        Schema::dropIfExists('credit_transactions');
        Schema::dropIfExists('approval_requests');
        Schema::dropIfExists('approval_levels');
        Schema::dropIfExists('customer_credit_accounts');
    }

    private function patchCustomers(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $this->string($table, 'customers', 'category', 80, 'WALK_IN');
            $this->string($table, 'customers', 'national_id', 80);
            $this->string($table, 'customers', 'company_registration_number', 120);
            $this->integer($table, 'customers', 'credit_period_days', 30);
            $this->string($table, 'customers', 'risk_level', 40, 'LOW');
            $this->decimal($table, 'customers', 'total_credit_sales', 15, 2, 0);
            $this->decimal($table, 'customers', 'total_payments', 15, 2, 0);
            $this->decimal($table, 'customers', 'total_outstanding', 15, 2, 0);
            $this->timestamp($table, 'customers', 'last_payment_date');
            $this->timestamp($table, 'customers', 'last_credit_date');
        });
    }

    private function createCreditAccounts(): void
    {
        if (Schema::hasTable('customer_credit_accounts')) {
            return;
        }

        Schema::create('customer_credit_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('account_number', 80)->unique();
            $table->string('category', 80)->default('WALK_IN')->index();
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->unsignedInteger('credit_period_days')->default(30);
            $table->string('risk_level', 40)->default('LOW')->index();
            $table->string('status', 40)->default('ACTIVE')->index();
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->decimal('available_credit', 15, 2)->default(0);
            $table->decimal('total_credit_sales', 15, 2)->default(0);
            $table->decimal('total_payments', 15, 2)->default(0);
            $table->decimal('total_outstanding', 15, 2)->default(0);
            $table->timestamp('last_payment_date')->nullable();
            $table->timestamp('last_credit_date')->nullable();
            $table->text('blocked_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->timestamps();

            $table->unique(['customer_id', 'branch_id'], 'customer_credit_customer_branch_unique');
        });
    }

    private function createApprovalLevels(): void
    {
        if (Schema::hasTable('approval_levels')) {
            return;
        }

        Schema::create('approval_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('level_number')->index();
            $table->string('name', 80);
            $table->string('role_name', 80)->index();
            $table->decimal('min_amount', 15, 2)->default(0);
            $table->decimal('max_amount', 15, 2)->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->unique(['level_number', 'role_name'], 'approval_levels_number_role_unique');
        });
    }

    private function createApprovalRequests(): void
    {
        if (Schema::hasTable('approval_requests')) {
            return;
        }

        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 90)->unique();
            $table->string('approval_type', 80)->index();
            $table->string('module', 80)->default('Accounts Receivable')->index();
            $table->string('reference_type')->nullable()->index();
            $table->unsignedBigInteger('reference_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('credit_account_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('requested_by')->nullable()->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->unsignedInteger('level_required')->default(1)->index();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('status', 40)->default('PENDING')->index();
            $table->text('reason')->nullable();
            $table->text('approval_note')->nullable();
            $table->timestamp('requested_at')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    private function createCreditTransactions(): void
    {
        if (Schema::hasTable('credit_transactions')) {
            return;
        }

        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 90)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->unsignedBigInteger('credit_account_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('sale_id')->nullable()->index();
            $table->unsignedBigInteger('approval_request_id')->nullable()->index();
            $table->string('transaction_type', 60)->index();
            $table->string('document_number', 120)->nullable()->index();
            $table->text('description')->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('balance_before', 15, 2)->default(0);
            $table->decimal('balance_after', 15, 2)->default(0);
            $table->date('due_date')->nullable()->index();
            $table->date('transaction_date')->index();
            $table->string('status', 40)->default('POSTED')->index();
            $table->unsignedBigInteger('posted_by')->nullable()->index();
            $table->timestamps();

            $table->index(['branch_id', 'transaction_type', 'transaction_date'], 'credit_tx_branch_type_date_idx');
            $table->index(['customer_id', 'transaction_date'], 'credit_tx_customer_date_idx');
        });
    }

    private function createCreditPayments(): void
    {
        if (Schema::hasTable('credit_payments')) {
            return;
        }

        Schema::create('credit_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 90)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->unsignedBigInteger('credit_account_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('payment_method', 60)->index();
            $table->string('reference', 160)->nullable()->index();
            $table->unsignedBigInteger('received_by')->nullable()->index();
            $table->timestamp('received_at')->nullable()->index();
            $table->string('allocation_method', 40)->default('OLDEST_FIRST');
            $table->string('status', 40)->default('POSTED')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    private function createCreditNotes(): void
    {
        if (Schema::hasTable('credit_notes')) {
            return;
        }

        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('note_number', 90)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->unsignedBigInteger('credit_account_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('sale_id')->nullable()->index();
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('reason')->nullable();
            $table->string('status', 40)->default('PENDING')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    private function createCreditCollections(): void
    {
        if (Schema::hasTable('credit_collections')) {
            return;
        }

        Schema::create('credit_collections', function (Blueprint $table) {
            $table->id();
            $table->string('collection_number', 90)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->unsignedBigInteger('credit_account_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('stage', 60)->default('CURRENT')->index();
            $table->string('channel', 60)->default('CALL')->index();
            $table->string('contact_person')->nullable();
            $table->decimal('commitment_amount', 15, 2)->default(0);
            $table->date('commitment_date')->nullable();
            $table->timestamp('next_follow_up_at')->nullable()->index();
            $table->string('status', 40)->default('OPEN')->index();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('handled_by')->nullable()->index();
            $table->timestamps();
        });
    }

    private function createCustomerStatements(): void
    {
        if (Schema::hasTable('customer_statements')) {
            return;
        }

        Schema::create('customer_statements', function (Blueprint $table) {
            $table->id();
            $table->string('statement_number', 90)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->unsignedBigInteger('credit_account_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->date('period_start')->index();
            $table->date('period_end')->index();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('debit_total', 15, 2)->default(0);
            $table->decimal('credit_total', 15, 2)->default(0);
            $table->decimal('closing_balance', 15, 2)->default(0);
            $table->unsignedBigInteger('generated_by')->nullable()->index();
            $table->timestamp('generated_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    private function createAgingSnapshots(): void
    {
        if (Schema::hasTable('aging_snapshots')) {
            return;
        }

        Schema::create('aging_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->unsignedBigInteger('credit_account_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->date('snapshot_date')->index();
            $table->decimal('current_amount', 15, 2)->default(0);
            $table->decimal('days_1_30', 15, 2)->default(0);
            $table->decimal('days_31_60', 15, 2)->default(0);
            $table->decimal('days_61_90', 15, 2)->default(0);
            $table->decimal('days_91_120', 15, 2)->default(0);
            $table->decimal('over_120', 15, 2)->default(0);
            $table->decimal('total_outstanding', 15, 2)->default(0);
            $table->string('risk_level', 40)->default('LOW')->index();
            $table->timestamps();
        });
    }

    private function createBadDebts(): void
    {
        if (Schema::hasTable('bad_debts')) {
            return;
        }

        Schema::create('bad_debts', function (Blueprint $table) {
            $table->id();
            $table->string('writeoff_number', 90)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->unsignedBigInteger('credit_account_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('reason')->nullable();
            $table->string('status', 40)->default('PENDING')->index();
            $table->unsignedBigInteger('written_off_by')->nullable()->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->timestamp('written_off_at')->nullable();
            $table->timestamps();
        });
    }

    private function patchCustomerLedger(): void
    {
        if (!Schema::hasTable('customer_ledger_entries')) {
            return;
        }

        Schema::table('customer_ledger_entries', function (Blueprint $table) {
            $this->unsigned($table, 'customer_ledger_entries', 'credit_account_id');
            $this->unsigned($table, 'customer_ledger_entries', 'branch_id');
            $this->date($table, 'customer_ledger_entries', 'transaction_date');
            $this->date($table, 'customer_ledger_entries', 'due_date');
        });
    }

    private function seedApprovalLevels(): void
    {
        if (!Schema::hasTable('approval_levels')) {
            return;
        }

        $levels = [
            [1, 'Supervisor', 'SUPERVISOR', 0, 50000],
            [2, 'Manager', 'MANAGER', 50000.01, 250000],
            [3, 'Finance Manager', 'FINANCE_MANAGER', 250000.01, 1000000],
            [4, 'General Manager', 'GENERAL_MANAGER', 1000000.01, null],
        ];

        foreach ($levels as [$number, $name, $role, $min, $max]) {
            DB::table('approval_levels')->updateOrInsert(
                ['level_number' => $number, 'role_name' => $role],
                [
                    'name' => $name,
                    'min_amount' => $min,
                    'max_amount' => $max,
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function backfillCreditAccounts(): void
    {
        if (!Schema::hasTable('customers') || !Schema::hasTable('customer_credit_accounts')) {
            return;
        }

        DB::table('customers')
            ->orderBy('id')
            ->select(['id', 'branch_id', 'customer_code', 'credit_limit', 'balance', 'status'])
            ->chunkById(200, function ($customers) {
                foreach ($customers as $customer) {
                    $balance = (float) ($customer->balance ?? 0);
                    $limit = (float) ($customer->credit_limit ?? 0);

                    DB::table('customer_credit_accounts')->updateOrInsert(
                        ['customer_id' => $customer->id, 'branch_id' => $customer->branch_id],
                        [
                            'account_number' => 'AR-' . ($customer->customer_code ?: str_pad((string) $customer->id, 6, '0', STR_PAD_LEFT)),
                            'category' => 'WALK_IN',
                            'credit_limit' => $limit,
                            'credit_period_days' => 30,
                            'risk_level' => 'LOW',
                            'status' => ($customer->status ?? 'ACTIVE') === 'ACTIVE' ? 'ACTIVE' : 'SUSPENDED',
                            'current_balance' => $balance,
                            'available_credit' => max($limit - $balance, 0),
                            'total_outstanding' => $balance,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    DB::table('customers')
                        ->where('id', $customer->id)
                        ->update([
                            'category' => DB::raw("COALESCE(category, 'WALK_IN')"),
                            'total_outstanding' => $balance,
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    private function string(Blueprint $table, string $tableName, string $column, int $length, ?string $default = null): void
    {
        if (Schema::hasColumn($tableName, $column)) {
            return;
        }

        $definition = $table->string($column, $length)->nullable($default === null);

        if ($default !== null) {
            $definition->default($default);
        }

        $definition->index();
    }

    private function integer(Blueprint $table, string $tableName, string $column, int $default): void
    {
        if (!Schema::hasColumn($tableName, $column)) {
            $table->unsignedInteger($column)->default($default);
        }
    }

    private function decimal(Blueprint $table, string $tableName, string $column, int $precision, int $scale, float|int $default): void
    {
        if (!Schema::hasColumn($tableName, $column)) {
            $table->decimal($column, $precision, $scale)->default($default);
        }
    }

    private function timestamp(Blueprint $table, string $tableName, string $column): void
    {
        if (!Schema::hasColumn($tableName, $column)) {
            $table->timestamp($column)->nullable();
        }
    }

    private function date(Blueprint $table, string $tableName, string $column): void
    {
        if (!Schema::hasColumn($tableName, $column)) {
            $table->date($column)->nullable()->index();
        }
    }

    private function unsigned(Blueprint $table, string $tableName, string $column): void
    {
        if (!Schema::hasColumn($tableName, $column)) {
            $table->unsignedBigInteger($column)->nullable()->index();
        }
    }
};
