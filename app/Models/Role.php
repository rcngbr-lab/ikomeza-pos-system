<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\Traits\HasPermissions;

class Role extends SpatieRole
{
    use HasPermissions;

    protected $fillable = [

        'name',
        'guard_name',
        'code',
        'slug',
        'description',
        'is_system',
        'active',

    ];
}