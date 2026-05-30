<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createStores();
        $this->createSuppliers();
        $this->patchProducts();
        $this->createStoreStocks();
        $this->createPurchases();
        $this->createStoreIssues();
        $this->createDamages();
        $this->createReturns();
        $this->createRecipes();
        $this->patchStockMovements();
        $this->seedDefaultStores();
        $this->backfillProductStoreBalances();
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_items');
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('stock_returns');
        Schema::dropIfExists('stock_damages');
        Schema::dropIfExists('store_issue_items');
        Schema::dropIfExists('store_issues');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('store_stocks');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('stores');
    }

    private function createStores(): void
    {
        if (Schema::hasTable('stores')) {
            return;
        }

        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('type', 40)->default('MAIN');
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    private function createSuppliers(): void
    {
        if (Schema::hasTable('suppliers')) {
            return;
        }

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('payment_terms')->nullable();
            $table->text('supplied_categories')->nullable();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    private function patchProducts(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'default_store_id')) {
                $table->unsignedBigInteger('default_store_id')->nullable()->index();
            }

            if (!Schema::hasColumn('products', 'supplier_id')) {
                $table->unsignedBigInteger('supplier_id')->nullable()->index();
            }

            if (!Schema::hasColumn('products', 'status')) {
                $table->string('status', 30)->default('ACTIVE')->index();
            }
        });
    }

    private function createStoreStocks(): void
    {
        if (Schema::hasTable('store_stocks')) {
            return;
        }

        Schema::create('store_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->decimal('quantity', 15, 3)->default(0);
            $table->decimal('alert_stock', 15, 3)->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_value', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['store_id', 'product_id']);
        });
    }

    private function createPurchases(): void
    {
        if (!Schema::hasTable('purchases')) {
            Schema::create('purchases', function (Blueprint $table) {
                $table->id();
                $table->string('purchase_number')->unique();
                $table->unsignedBigInteger('supplier_id')->nullable()->index();
                $table->unsignedBigInteger('requisition_id')->nullable()->index();
                $table->unsignedBigInteger('department_id')->nullable()->index();
                $table->unsignedBigInteger('store_id')->index();
                $table->unsignedBigInteger('purchased_by')->nullable()->index();
                $table->unsignedBigInteger('approved_by')->nullable()->index();
                $table->unsignedBigInteger('received_by')->nullable()->index();
                $table->string('invoice_number')->nullable()->index();
                $table->date('purchase_date')->nullable();
                $table->date('expected_delivery_date')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('received_date')->nullable();
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('tax', 15, 2)->default(0);
                $table->decimal('discount', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->string('payment_status', 30)->default('UNPAID')->index();
                $table->string('status', 40)->default('DRAFT')->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('purchase_items')) {
            Schema::create('purchase_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('purchase_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->decimal('quantity_ordered', 15, 3)->default(0);
                $table->decimal('quantity_received', 15, 3)->default(0);
                $table->decimal('damaged_quantity', 15, 3)->default(0);
                $table->decimal('unit_cost', 15, 2)->default(0);
                $table->decimal('total_cost', 15, 2)->default(0);
                $table->string('batch_number')->nullable();
                $table->date('expiry_date')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    private function createStoreIssues(): void
    {
        if (!Schema::hasTable('store_issues')) {
            Schema::create('store_issues', function (Blueprint $table) {
                $table->id();
                $table->string('issue_number')->unique();
                $table->unsignedBigInteger('from_store_id')->index();
                $table->unsignedBigInteger('to_store_id')->nullable()->index();
                $table->unsignedBigInteger('department_id')->nullable()->index();
                $table->unsignedBigInteger('requisition_id')->nullable()->index();
                $table->unsignedBigInteger('issued_by')->nullable()->index();
                $table->unsignedBigInteger('received_by')->nullable()->index();
                $table->unsignedBigInteger('approved_by')->nullable()->index();
                $table->timestamp('issue_date')->nullable();
                $table->timestamp('received_date')->nullable();
                $table->string('status', 40)->default('DRAFT')->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('store_issue_items')) {
            Schema::create('store_issue_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('store_issue_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->decimal('quantity_requested', 15, 3)->default(0);
                $table->decimal('quantity_issued', 15, 3)->default(0);
                $table->decimal('quantity_received', 15, 3)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    private function createDamages(): void
    {
        if (Schema::hasTable('stock_damages')) {
            return;
        }

        Schema::create('stock_damages', function (Blueprint $table) {
            $table->id();
            $table->string('damage_number')->unique();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('store_id')->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->decimal('quantity', 15, 3);
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable()->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->string('status', 40)->default('PENDING_APPROVAL')->index();
            $table->timestamps();
        });
    }

    private function createReturns(): void
    {
        if (Schema::hasTable('stock_returns')) {
            return;
        }

        Schema::create('stock_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique();
            $table->string('return_type', 50);
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('from_store_id')->nullable()->index();
            $table->unsignedBigInteger('to_store_id')->nullable()->index();
            $table->unsignedBigInteger('supplier_id')->nullable()->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->decimal('quantity', 15, 3);
            $table->text('reason')->nullable();
            $table->string('status', 40)->default('PENDING_APPROVAL')->index();
            $table->unsignedBigInteger('recorded_by')->nullable()->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    private function createRecipes(): void
    {
        if (!Schema::hasTable('recipes')) {
            Schema::create('recipes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('department_id')->nullable()->index();
                $table->string('name')->nullable();
                $table->decimal('yield_quantity', 15, 3)->default(1);
                $table->text('notes')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('recipe_items')) {
            Schema::create('recipe_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('recipe_id')->index();
                $table->unsignedBigInteger('ingredient_product_id')->index();
                $table->unsignedBigInteger('store_id')->nullable()->index();
                $table->decimal('quantity', 15, 3);
                $table->string('unit')->nullable();
                $table->decimal('unit_cost', 15, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    private function patchStockMovements(): void
    {
        if (!Schema::hasTable('stock_movements')) {
            return;
        }

        Schema::table('stock_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_movements', 'from_store_id')) {
                $table->unsignedBigInteger('from_store_id')->nullable()->index();
            }

            if (!Schema::hasColumn('stock_movements', 'to_store_id')) {
                $table->unsignedBigInteger('to_store_id')->nullable()->index();
            }

            if (!Schema::hasColumn('stock_movements', 'movement_type')) {
                $table->string('movement_type', 50)->nullable()->index();
            }

            if (!Schema::hasColumn('stock_movements', 'quantity_before')) {
                $table->decimal('quantity_before', 15, 3)->nullable();
            }

            if (!Schema::hasColumn('stock_movements', 'quantity_changed')) {
                $table->decimal('quantity_changed', 15, 3)->nullable();
            }

            if (!Schema::hasColumn('stock_movements', 'quantity_after')) {
                $table->decimal('quantity_after', 15, 3)->nullable();
            }

            if (!Schema::hasColumn('stock_movements', 'unit_cost')) {
                $table->decimal('unit_cost', 15, 2)->nullable();
            }

            if (!Schema::hasColumn('stock_movements', 'total_cost')) {
                $table->decimal('total_cost', 15, 2)->nullable();
            }

            if (!Schema::hasColumn('stock_movements', 'performed_by')) {
                $table->unsignedBigInteger('performed_by')->nullable()->index();
            }

            if (!Schema::hasColumn('stock_movements', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->index();
            }

            if (!Schema::hasColumn('stock_movements', 'notes')) {
                $table->text('notes')->nullable();
            }
        });
    }

    private function seedDefaultStores(): void
    {
        if (!Schema::hasTable('stores')) {
            return;
        }

        $now = now();
        $kitchenDepartmentId = DB::table('departments')->where('code', 'KITCHEN')->value('id');
        $barDepartmentId = DB::table('departments')->where('code', 'BAR')->value('id');

        foreach ([
            [
                'code' => 'MAIN',
                'name' => 'Main Store',
                'type' => 'MAIN',
                'department_id' => null,
                'description' => 'Central receiving and supplier stock custody',
                'sort_order' => 10,
            ],
            [
                'code' => 'KITCHEN',
                'name' => 'Kitchen Store',
                'type' => 'KITCHEN',
                'department_id' => $kitchenDepartmentId,
                'description' => 'Kitchen ingredients, meals, recipes, food packaging, and consumables',
                'sort_order' => 20,
            ],
            [
                'code' => 'BAR',
                'name' => 'Bar Store',
                'type' => 'BAR',
                'department_id' => $barDepartmentId,
                'description' => 'Beer, soft drinks, liquor, wine, water, mixers, crates, and bottles',
                'sort_order' => 30,
            ],
        ] as $store) {
            DB::table('stores')->updateOrInsert(
                ['code' => $store['code']],
                array_merge($store, [
                    'active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
    }

    private function backfillProductStoreBalances(): void
    {
        if (
            !Schema::hasTable('products')
            || !Schema::hasTable('stores')
            || !Schema::hasTable('store_stocks')
        ) {
            return;
        }

        $mainStoreId = DB::table('stores')->where('code', 'MAIN')->value('id');
        $kitchenStoreId = DB::table('stores')->where('code', 'KITCHEN')->value('id');
        $barStoreId = DB::table('stores')->where('code', 'BAR')->value('id');
        $now = now();

        DB::table('products')
            ->leftJoin('departments', 'products.department_id', '=', 'departments.id')
            ->select(
                'products.id',
                'products.stock',
                'products.alert_stock',
                'products.buy_price',
                'products.active',
                'products.department_id',
                'departments.code as department_code'
            )
            ->orderBy('products.id')
            ->chunkById(100, function ($products) use ($mainStoreId, $kitchenStoreId, $barStoreId, $now) {
                foreach ($products as $product) {
                    $storeId = match (strtoupper((string) $product->department_code)) {
                        'KITCHEN' => $kitchenStoreId ?: $mainStoreId,
                        'BAR' => $barStoreId ?: $mainStoreId,
                        default => $mainStoreId ?: $barStoreId ?: $kitchenStoreId,
                    };

                    if (!$storeId) {
                        continue;
                    }

                    $stock = (float) ($product->stock ?? 0);
                    $unitCost = (float) ($product->buy_price ?? 0);

                    if (Schema::hasColumn('products', 'default_store_id')) {
                        DB::table('products')
                            ->where('id', $product->id)
                            ->update([
                                'default_store_id' => $storeId,
                                'status' => ($product->active ?? true) ? 'ACTIVE' : 'INACTIVE',
                                'updated_at' => $now,
                            ]);
                    }

                    DB::table('store_stocks')->updateOrInsert(
                        [
                            'store_id' => $storeId,
                            'product_id' => $product->id,
                        ],
                        [
                            'department_id' => $product->department_id,
                            'quantity' => $stock,
                            'alert_stock' => (float) ($product->alert_stock ?? 0),
                            'unit_cost' => $unitCost,
                            'total_value' => $stock * $unitCost,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }
            }, 'products.id', 'id');
    }
};
