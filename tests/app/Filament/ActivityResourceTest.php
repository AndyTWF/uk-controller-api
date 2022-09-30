<?php

namespace App\Filament;

use App\BaseFilamentTestCase;
use App\Filament\AccessCheckingHelpers\ChecksListingFilamentAccess;
use App\Filament\AccessCheckingHelpers\ChecksViewFilamentAccess;
use App\Filament\Resources\ActivityResource;
use App\Models\User\RoleKeys;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class ActivityResourceTest extends BaseFilamentTestCase
{
    use ChecksListingFilamentAccess;
    use ChecksViewFilamentAccess;

    private readonly Activity $activity;

    public function setUp(): void
    {
        parent::setUp();
        $this->activity = activity('test')
            ->log('ohai');
    }

    private function indexRoleProvider(): array
    {
        return [
            'None' => [null, false],
            'DSG' => [RoleKeys::DIVISION_STAFF_GROUP, true],
            'Web' => [RoleKeys::WEB_TEAM, true],
            'Operations' => [RoleKeys::OPERATIONS_TEAM, false],
        ];
    }

    private function viewRoleProvider(): array
    {
        return [
            'None' => [null, false],
            'DSG' => [RoleKeys::DIVISION_STAFF_GROUP, true],
            'Web' => [RoleKeys::WEB_TEAM, true],
            'Operations' => [RoleKeys::OPERATIONS_TEAM, false],
        ];
    }

    protected function getIndexText(): array
    {
        return ['Activity Logs'];
    }

    protected function getViewText(): string
    {
        return 'View Activity Log';
    }

    protected function getViewRecord(): Model
    {
        return Activity::query()->firstOrFail();
    }

    protected function getResourceClass(): string
    {
        return ActivityResource::class;
    }

    protected function resourceClass(): string
    {
        return ActivityResource::class;
    }
}
