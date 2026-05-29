<?php

namespace Database\Seeders;

use App\Services\DemoAccountService;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        app(DemoAccountService::class)->ensure();
    }
}
