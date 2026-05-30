<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username', 80)
                    ->nullable()
                    ->after('name');
            });
        }

        $used = [];

        DB::table('users')
            ->orderBy('id')
            ->select(['id', 'name', 'email', 'username'])
            ->chunkById(100, function ($users) use (&$used) {
                foreach ($users as $user) {
                    if (!empty($user->username)) {
                        $used[strtolower($user->username)] = true;

                        continue;
                    }

                    $base = $this->usernameBase($user->email ?: $user->name ?: 'user');
                    $username = $base;
                    $suffix = 2;

                    while (isset($used[$username])) {
                        $username = $base . $suffix;
                        $suffix++;
                    }

                    $used[$username] = true;

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['username' => $username]);
                }
            });

        if (!$this->hasIndex('users_username_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('username', 'users_username_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                if ($this->hasIndex('users_username_unique')) {
                    $table->dropUnique('users_username_unique');
                }

                $table->dropColumn('username');
            });
        }
    }

    private function usernameBase(string $value): string
    {
        $value = Str::of($value)
            ->before('@')
            ->lower()
            ->replaceMatches('/[^a-z0-9._-]+/', '.')
            ->trim('.-_')
            ->toString();

        return strlen($value) >= 3 ? $value : 'user' . ($value ?: '');
    }

    private function hasIndex(string $index): bool
    {
        if (method_exists(Schema::getFacadeRoot(), 'hasIndex')) {
            return Schema::hasIndex('users', $index);
        }

        if (DB::getDriverName() === 'sqlite') {
            return collect(DB::select('PRAGMA index_list(users)'))
                ->contains(fn ($row) => ($row->name ?? null) === $index);
        }

        return false;
    }
};
