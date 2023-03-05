<?php

namespace App\Policies;

use App\Models\User\RoleKeys;

/**
 * A base policy for doing things via Filament.
 */
class DefaultFilamentPolicy extends BaseCrudPolicy
{
    protected $roles = [
        RoleKeys::DIVISION_STAFF_GROUP, 
        RoleKeys::WEB_TEAM, 
        RoleKeys::OPERATIONS_TEAM
    ];
}
