<?php

namespace App\Filament;

use App\BaseFilamentTestCase;
use App\Filament\AccessCheckingHelpers\ChecksManageRecordsFilamentAccess;
use App\Filament\Resources\FirExitPointResource;
use App\Filament\Resources\FirExitPointResource\Pages\ManageFirExitPoints;
use App\Models\IntentionCode\FirExitPoint;
use Livewire\Livewire;

class FirExitPointResourceTest extends BaseFilamentTestCase
{
    use ChecksManageRecordsFilamentAccess;
    use ChecksFilamentActionVisibility;

    public function testItCreatesAnExitPoint()
    {
        Livewire::test(ManageFirExitPoints::class)
            ->callPageAction(
                'create',
                [
                    'exit_point' => 'BOO',
                    'internal' => true,
                    'exit_direction_start' => 123,
                    'exit_direction_end' => 345,
                ]
            )
            ->assertHasNoErrors();

        $this->assertDatabaseHas(
            'fir_exit_points',
            [
                'exit_point' => 'BOO',
                'internal' => true,
                'exit_direction_start' => 123,
                'exit_direction_end' => 345,
            ]
        );
    }

    public function testItDoesntCreateAnExitPointIfExitPointMissing()
    {
        Livewire::test(ManageFirExitPoints::class)
            ->callPageAction(
                'create',
                [
                    'internal' => true,
                    'exit_direction_start' => 123,
                    'exit_direction_end' => 345,
                ]
            )
            ->assertHasPageActionErrors(['exit_point']);
    }

    public function testItDoesntCreateAnExitPointIfInternalMissing()
    {
        Livewire::test(ManageFirExitPoints::class)
            ->callPageAction(
                'create',
                [
                    'exit_point' => 'BOO',
                    'exit_direction_start' => 123,
                    'exit_direction_end' => 345,
                ]
            )
            ->assertHasPageActionErrors(['internal']);
    }

    public function testItDoesntCreateAnExitPointIfExitPointTooLong()
    {
        Livewire::test(ManageFirExitPoints::class)
            ->callPageAction(
                'create',
                [
                    'exit_point' => 'BOOOOOOOOO',
                    'internal' => true,
                    'exit_direction_start' => 123,
                    'exit_direction_end' => 345,
                ]
            )
            ->assertHasPageActionErrors(['exit_point']);
    }

    public function testItDoesntCreateAnExitPointIfStartDirectionMissing()
    {
        Livewire::test(ManageFirExitPoints::class)
            ->callPageAction(
                'create',
                [
                    'exit_point' => 'BOO',
                    'internal' => true,
                    'exit_direction_end' => 345,
                ]
            )
            ->assertHasPageActionErrors(['exit_direction_start']);
    }

    public function testItDoesntCreateAnExitPointIfStartDirectionNegative()
    {
        Livewire::test(ManageFirExitPoints::class)
            ->callPageAction(
                'create',
                [
                    'exit_point' => 'BOO',
                    'internal' => true,
                    'exit_direction_start' => -5,
                    'exit_direction_end' => 345,
                ]
            )
            ->assertHasPageActionErrors(['exit_direction_start']);
    }

    public function testItDoesntCreateAnExitPointIfStartDirectionTooLarge()
    {
        Livewire::test(ManageFirExitPoints::class)
            ->callPageAction(
                'create',
                [
                    'exit_point' => 'BOO',
                    'internal' => true,
                    'exit_direction_start' => 361,
                    'exit_direction_end' => 345,
                ]
            )
            ->assertHasPageActionErrors(['exit_direction_start']);
    }

    public function testItDoesntCreateAnExitPointIfEndDirectionMissing()
    {
        Livewire::test(ManageFirExitPoints::class)
            ->callPageAction(
                'create',
                [
                    'exit_point' => 'BOO',
                    'internal' => true,
                    'exit_direction_start' => 123,
                ]
            )
            ->assertHasPageActionErrors(['exit_direction_end']);
    }

    public function testItDoesntCreateAnExitPointIfEndDirectionNegative()
    {
        Livewire::test(ManageFirExitPoints::class)
            ->callPageAction(
                'create',
                [
                    'exit_point' => 'BOO',
                    'internal' => true,
                    'exit_direction_start' => 123,
                    'exit_direction_end' => -5,
                ]
            )
            ->assertHasPageActionErrors(['exit_direction_end']);
    }

    public function testItDoesntCreateAnExitPointIfEndDirectionTooLarge()
    {
        Livewire::test(ManageFirExitPoints::class)
            ->callPageAction(
                'create',
                [
                    'exit_point' => 'BOO',
                    'internal' => true,
                    'exit_direction_start' => 123,
                    'exit_direction_end' => 361,
                ]
            )
            ->assertHasPageActionErrors(['exit_direction_end']);
    }

    public function testItEditsAFirExitPoint()
    {
        Livewire::test(ManageFirExitPoints::class)
            ->callTableAction(
                'edit',
                FirExitPoint::findOrFail(1),
                [
                    'exit_point' => 'TEST',
                    'internal' => false,
                    'exit_direction_start' => 222,
                    'exit_direction_end' => 333,
                ]
            )
            ->assertHasNoErrors();

        $this->assertDatabaseHas(
            'fir_exit_points',
            [
                'id' => 1,
                'exit_point' => 'TEST',
                'internal' => false,
                'exit_direction_start' => 222,
                'exit_direction_end' => 333,
            ]
        );
    }

    public function testItDoesntEditAnExitPointIfExitPointMissing()
    {
        Livewire::test(ManageFirExitPoints::class)
            ->callTableAction(
                'edit',
                FirExitPoint::findOrFail(1),
                [
                    'internal' => true,
                    'exit_direction_start' => 123,
                    'exit_direction_end' => 345,
                ]
            )
            ->assertHasTableActionErrors(['exit_point']);
    }

    public function testItDoesntEditAnExitPointIfInternalMissing()
    {
        Livewire::test(ManageFirExitPoints::class)
            ->callTableAction(
                'edit',
                FirExitPoint::findOrFail(1),
                [
                    'exit_point' => 'TEST',
                    'exit_direction_start' => 123,
                    'exit_direction_end' => 345,
                ]
            )
            ->assertHasTableActionErrors(['internal']);
    }

    public function testItDoesntEditAnExitPointIfExitPointTooLong()
    {
        Livewire::test(ManageFirExitPoints::class)
            ->callTableAction(
                'edit',
                FirExitPoint::findOrFail(1),
                [
                    'exit_point' => 'BOOOOOOOOO',
                    'internal' => true,
                    'exit_direction_start' => 123,
                    'exit_direction_end' => 345,
                ]
            )
            ->assertHasTableActionErrors(['exit_point']);
    }

    public function testItDoesntEditAnExitPointIfStartDirectionMissing()
    {
        Livewire::test(ManageFirExitPoints::class)
            ->callTableAction(
                'edit',
                FirExitPoint::findOrFail(1),
                [
                    'exit_point' => 'BOO',
                    'internal' => true,
                    'exit_direction_end' => 345,
                ]
            )
            ->assertHasTableActionErrors(['exit_direction_start']);
    }


    public function testItDoesntEditAnExitPointIfStartDirectionNegative()
    {
        Livewire::test(ManageFirExitPoints::class)
            ->callTableAction(
                'edit',
                FirExitPoint::findOrFail(1),
                [
                    'exit_point' => 'BOO',
                    'internal' => true,
                    'exit_direction_start' => -5,
                    'exit_direction_end' => 345,
                ]
            )
            ->assertHasTableActionErrors(['exit_direction_start']);
    }

    public function testItDoesntEditAnExitPointIfStartDirectionTooLarge()
    {
        Livewire::test(ManageFirExitPoints::class)
            ->callTableAction(
                'edit',
                FirExitPoint::findOrFail(1),
                [
                    'exit_point' => 'BOO',
                    'internal' => true,
                    'exit_direction_start' => 361,
                    'exit_direction_end' => 345,
                ]
            )
            ->assertHasTableActionErrors(['exit_direction_start']);
    }

    public function testItDoesntEditAnExitPointIfEndDirectionMissing()
    {
        Livewire::test(ManageFirExitPoints::class)
            ->callTableAction(
                'edit',
                FirExitPoint::findOrFail(1),
                [
                    'exit_point' => 'BOO',
                    'internal' => true,
                    'exit_direction_start' => 123,
                ]
            )
            ->assertHasTableActionErrors(['exit_direction_end']);
    }

    public function testItDoesntEditAnExitPointIfEndDirectionNegative()
    {
        Livewire::test(ManageFirExitPoints::class)
            ->callTableAction(
                'edit',
                FirExitPoint::findOrFail(1),
                [
                    'exit_point' => 'BOO',
                    'internal' => true,
                    'exit_direction_start' => 123,
                    'exit_direction_end' => -5,
                ]
            )
            ->assertHasTableActionErrors(['exit_direction_end']);
    }

    public function testItDoesntEditAnExitPointIfEndDirectionTooLarge()
    {
        Livewire::test(ManageFirExitPoints::class)
            ->callTableAction(
                'edit',
                FirExitPoint::findOrFail(1),
                [
                    'exit_point' => 'BOO',
                    'internal' => true,
                    'exit_direction_start' => 123,
                    'exit_direction_end' => 361,
                ]
            )
            ->assertHasTableActionErrors(['exit_direction_end']);
    }

    protected function getCreateText(): string
    {
        return 'New fir exit point';
    }

    protected function getIndexText(): array
    {
        return ['Fir Exit Points', 'FOO'];
    }

    protected function resourceClass(): string
    {
        return FirExitPointResource::class;
    }

    protected function resourceListingClass(): string
    {
        return ManageFirExitPoints::class;
    }

    protected function resourceRecordClass(): string
    {
        return FirExitPoint::class;
    }

    protected function resourceId(): int|string
    {
        return 1;
    }

    protected function writeResourceTableActions(): array
    {
        return ['edit', 'delete'];
    }

    protected function writeResourcePageActions(): array
    {
        return ['create'];
    }
}
