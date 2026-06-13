<?php

namespace Database\Seeders;

use App\Services\DemoAccountService;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        if (!filter_var(env('ENABLE_DEMO_ACCOUNTS', false), FILTER_VALIDATE_BOOL)) {
            return;
        }

        app(DemoAccountService::class)->ensure();
    }
}
