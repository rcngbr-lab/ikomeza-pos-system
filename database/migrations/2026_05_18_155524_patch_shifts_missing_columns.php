<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | BRANCH ID
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasColumn(
                'shifts',
                'branch_id'
            )) {

                $table->unsignedBigInteger(
                    'branch_id'
                )

                ->nullable()

                ->after('user_id');

            }

            /*
            |--------------------------------------------------------------------------
            | SHIFT CODE
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasColumn(
                'shifts',
                'shift_code'
            )) {

                $table->string(
                    'shift_code'
                )

                ->nullable()

                ->after('branch_id');

            }

            /*
            |--------------------------------------------------------------------------
            | IS OPEN
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasColumn(
                'shifts',
                'is_open'
            )) {

                $table->boolean(
                    'is_open'
                )

                ->default(true)

                ->after('status');

            }

        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {

            if (Schema::hasColumn(
                'shifts',
                'branch_id'
            )) {

                $table->dropColumn(
                    'branch_id'
                );

            }

            if (Schema::hasColumn(
                'shifts',
                'shift_code'
            )) {

                $table->dropColumn(
                    'shift_code'
                );

            }

            if (Schema::hasColumn(
                'shifts',
                'is_open'
            )) {

                $table->dropColumn(
                    'is_open'
                );

            }

        });
    }
};