<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Department;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Store;
use App\Models\StoreStock;
use Illuminate\Database\Seeder;

class DemoEnvironmentSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production') || !filter_var(env('RUN_DEMO_SEEDERS', false), FILTER_VALIDATE_BOOL)) {
            return;
        }

        $branch = Branch::firstOrCreate(
            ['code' => 'DEMO'],
            [
                'name' => 'Demo Branch',
                'location' => 'Kigali',
                'country' => 'Rwanda',
                'currency' => 'RWF',
                'status' => 'ACTIVE',
            ]
        );

        $kitchen = Department::firstOrCreate(
            ['code' => 'KITCHEN'],
            ['name' => 'Kitchen', 'active' => true, 'sort_order' => 10]
        );

        $bar = Department::firstOrCreate(
            ['code' => 'BAR'],
            ['name' => 'Bar', 'active' => true, 'sort_order' => 20]
        );

        $stores = [
            Store::firstOrCreate(
                ['code' => 'DEMO-MAIN'],
                ['branch_id' => $branch->id, 'name' => 'Demo Main Store', 'type' => Store::TYPE_MAIN, 'active' => true, 'sort_order' => 1]
            ),
            Store::firstOrCreate(
                ['code' => 'DEMO-KITCHEN'],
                ['branch_id' => $branch->id, 'name' => 'Demo Kitchen Store', 'type' => Store::TYPE_KITCHEN, 'department_id' => $kitchen->id, 'active' => true, 'sort_order' => 2]
            ),
            Store::firstOrCreate(
                ['code' => 'DEMO-BAR'],
                ['branch_id' => $branch->id, 'name' => 'Demo Bar Store', 'type' => Store::TYPE_BAR, 'department_id' => $bar->id, 'active' => true, 'sort_order' => 3]
            ),
        ];

        $foodCategory = Category::firstOrCreate(
            ['code' => 'DEMO-FOOD'],
            ['branch_id' => $branch->id, 'name' => 'Demo Food', 'department_id' => $kitchen->id, 'active' => true, 'sort_order' => 10]
        );

        $drinkCategory = Category::firstOrCreate(
            ['code' => 'DEMO-DRINKS'],
            ['branch_id' => $branch->id, 'name' => 'Demo Drinks', 'department_id' => $bar->id, 'active' => true, 'sort_order' => 20]
        );

        $products = [
            [
                'code' => 'DEMO-HEINEKEN',
                'barcode' => 'DEMO1001',
                'name' => 'Demo Heineken',
                'category_id' => $drinkCategory->id,
                'department_id' => $bar->id,
                'store_id' => $stores[2]->id,
                'buy_price' => 900,
                'selling_price' => 1200,
                'stock' => 50,
                'unit' => 'Bottle',
            ],
            [
                'code' => 'DEMO-SODA',
                'barcode' => 'DEMO1002',
                'name' => 'Demo Soda',
                'category_id' => $drinkCategory->id,
                'department_id' => $bar->id,
                'store_id' => $stores[2]->id,
                'buy_price' => 500,
                'selling_price' => 800,
                'stock' => 80,
                'unit' => 'Bottle',
            ],
            [
                'code' => 'DEMO-FRIES',
                'barcode' => 'DEMO2001',
                'name' => 'Demo Fries Plate',
                'category_id' => $foodCategory->id,
                'department_id' => $kitchen->id,
                'store_id' => $stores[1]->id,
                'buy_price' => 1200,
                'selling_price' => 2500,
                'stock' => 30,
                'unit' => 'Plate',
            ],
        ];

        foreach ($products as $productData) {
            $product = Product::updateOrCreate(
                ['product_code' => $productData['code']],
                [
                    'branch_id' => $branch->id,
                    'barcode' => $productData['barcode'],
                    'name' => $productData['name'],
                    'category_id' => $productData['category_id'],
                    'department_id' => $productData['department_id'],
                    'default_store_id' => $productData['store_id'],
                    'product_type' => 'FINISHED_PRODUCT',
                    'buy_price' => $productData['buy_price'],
                    'selling_price' => $productData['selling_price'],
                    'track_stock' => true,
                    'stock' => $productData['stock'],
                    'alert_stock' => 5,
                    'unit' => $productData['unit'],
                    'active' => true,
                    'status' => 'ACTIVE',
                    'is_taxable' => true,
                    'tax_category' => 'VATABLE',
                ]
            );

            StoreStock::updateOrCreate(
                ['store_id' => $productData['store_id'], 'product_id' => $product->id],
                [
                    'branch_id' => $branch->id,
                    'department_id' => $productData['department_id'],
                    'quantity' => $productData['stock'],
                    'alert_stock' => 5,
                    'unit_cost' => $productData['buy_price'],
                    'total_value' => $productData['buy_price'] * $productData['stock'],
                ]
            );
        }

        for ($i = 1; $i <= 6; $i++) {
            RestaurantTable::firstOrCreate(
                ['table_code' => 'DEMO-T' . str_pad((string) $i, 2, '0', STR_PAD_LEFT)],
                [
                    'branch_id' => $branch->id,
                    'name' => 'Demo Table ' . $i,
                    'section' => $i <= 3 ? 'Restaurant' : 'Bar',
                    'seats' => $i <= 3 ? 4 : 2,
                    'status' => RestaurantTable::STATUS_AVAILABLE,
                ]
            );
        }
    }
}
