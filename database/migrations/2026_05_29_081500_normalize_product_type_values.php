<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        if (DB::getDriverName() !== 'sqlite') {
            DB::table('products')
                ->whereNotIn('product_type', ['FINISHED_PRODUCT', 'RAW_MATERIAL', 'SERVICE'])
                ->update(['product_type' => 'FINISHED_PRODUCT']);

            return;
        }

        DB::statement('PRAGMA foreign_keys=OFF');
        Schema::dropIfExists('products_normalized');

        DB::statement(<<<'SQL'
            CREATE TABLE products_normalized (
                id integer primary key autoincrement not null,
                product_code varchar not null,
                barcode varchar,
                name varchar not null,
                description text,
                category_id integer,
                product_type varchar check ("product_type" in ('FINISHED_PRODUCT', 'RAW_MATERIAL', 'SERVICE')) not null default 'FINISHED_PRODUCT',
                buy_price numeric not null default '0',
                selling_price numeric not null default '0',
                track_stock tinyint(1) not null default '1',
                stock numeric not null default '0',
                alert_stock numeric not null default '5',
                unit varchar not null default 'PCS',
                active tinyint(1) not null default '1',
                created_at datetime,
                updated_at datetime,
                foreign key("category_id") references "categories"("id") on delete set null
            )
        SQL);

        DB::statement(<<<'SQL'
            INSERT INTO products_normalized (
                id,
                product_code,
                barcode,
                name,
                description,
                category_id,
                product_type,
                buy_price,
                selling_price,
                track_stock,
                stock,
                alert_stock,
                unit,
                active,
                created_at,
                updated_at
            )
            SELECT
                id,
                product_code,
                barcode,
                name,
                description,
                category_id,
                CASE
                    WHEN product_type = 'RAW_MATERIAL' THEN 'RAW_MATERIAL'
                    WHEN product_type = 'SERVICE' THEN 'SERVICE'
                    ELSE 'FINISHED_PRODUCT'
                END,
                buy_price,
                selling_price,
                track_stock,
                stock,
                alert_stock,
                unit,
                active,
                created_at,
                updated_at
            FROM products
        SQL);

        Schema::drop('products');
        Schema::rename('products_normalized', 'products');

        Schema::table('products', function ($table) {
            $table->unique('product_code');
            $table->unique('barcode');
        });

        DB::statement('PRAGMA foreign_keys=ON');
    }

    public function down(): void
    {
        //
    }
};
