<?php

namespace Database\Seeders;

use App\Services\AdminAccountService;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        app(AdminAccountService::class)->ensure();
    }
}
