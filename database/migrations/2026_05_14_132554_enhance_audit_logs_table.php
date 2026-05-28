<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | BRANCH
            |--------------------------------------------------------------------------
            */

            $table->foreignId('branch_id')
                ->nullable()
                ->after('user_id');

            /*
            |--------------------------------------------------------------------------
            | MODEL ID
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('model_id')
                ->nullable()
                ->after('model');

            /*
            |--------------------------------------------------------------------------
            | SNAPSHOTS
            |--------------------------------------------------------------------------
            */

            $table->longText('old_values')
                ->nullable()
                ->after('description');

            $table->longText('new_values')
                ->nullable()
                ->after('old_values');

            /*
            |--------------------------------------------------------------------------
            | SEVERITY
            |--------------------------------------------------------------------------
            */

            $table->string('severity')
                ->default('INFO')
                ->after('new_values');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {

            $table->dropColumn([

                'branch_id',

                'model_id',

                'old_values',

                'new_values',

                'severity'

            ]);

        });
    }
};