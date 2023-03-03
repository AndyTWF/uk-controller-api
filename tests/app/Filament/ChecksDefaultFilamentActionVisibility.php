<?php

namespace App\Filament;

use App\Models\User\RoleKeys;

trait ChecksDefaultFilamentActionVisibility
{
    use BaseChecksActionVisibility;

    private static function readOnlyRoles(): array
    {
        return [
            RoleKeys::OPERATIONS_TEAM,
            RoleKeys::WEB_TEAM,
            RoleKeys::DIVISION_STAFF_GROUP,
            RoleKeys::OPERATIONS_CONTRIBUTOR,
            null,
        ];
    }

    private static function writeRoles(): array
    {
        return [
            RoleKeys::OPERATIONS_TEAM,
            RoleKeys::WEB_TEAM,
            RoleKeys::DIVISION_STAFF_GROUP,
        ];
    }

    private static function rolesToIterate(): array
    {
        return [
            RoleKeys::OPERATIONS_TEAM,
            RoleKeys::WEB_TEAM,
            RoleKeys::DIVISION_STAFF_GROUP,
            RoleKeys::OPERATIONS_CONTRIBUTOR,
            null,
        ];
    }
}
