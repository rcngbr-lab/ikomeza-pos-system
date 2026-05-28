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
    Schema::table('users', function (Blueprint $table) {

        $table->string('phone')
            ->nullable()
            ->after('email');

        $table->enum('status', [

            'ACTIVE',
            'INACTIVE',
            'SUSPENDED'

        ])->default('ACTIVE')
          ->after('password');

        $table->timestamp('last_login_at')
            ->nullable()
            ->after('remember_token');

        $table->string('avatar')
            ->nullable()
            ->after('last_login_at');

    });
}
};