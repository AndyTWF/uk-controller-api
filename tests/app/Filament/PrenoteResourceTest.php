<?php

namespace App\Filament;

use App\BaseFilamentTestCase;
use App\Filament\Resources\PrenoteResource;
use App\Models\Controller\Prenote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Livewire;

class PrenoteResourceTest extends BaseFilamentTestCase
{
    use ChecksDefaultFilamentAccess;

    public function testItLoadsDataForView()
    {
        Livewire::test(PrenoteResource\Pages\ViewPrenote::class, ['record' => 1])
            ->assertSet('data.description', 'Prenote One');
    }

    public function testItCreatesAPrenote()
    {
        Livewire::test(PrenoteResource\Pages\CreatePrenote::class)
            ->set('data.description', 'A Prenote')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(
            'prenotes',
            [
                'description' => 'A Prenote',
            ]
        );
    }

    public function testItDoesntCreatePrenoteIfDescriptionEmpty()
    {
        Livewire::test(PrenoteResource\Pages\CreatePrenote::class)
            ->set('data.description', '')
            ->call('create')
            ->assertHasErrors(['data.description']);
    }

    public function testItDoesntCreatePrenoteIfDescriptionTooLong()
    {
        Livewire::test(PrenoteResource\Pages\CreatePrenote::class)
            ->set('data.description', Str::padRight('', 256, 'a'))
            ->call('create')
            ->assertHasErrors(['data.description']);
    }

    public function testItLoadsDataForEdit()
    {
        Livewire::test(PrenoteResource\Pages\EditPrenote::class, ['record' => 1])
            ->assertSet('data.description', 'Prenote One');
    }

    public function testItEditsAPrenote()
    {
        Livewire::test(PrenoteResource\Pages\EditPrenote::class, ['record' => 1])
            ->set('data.description', 'A Prenote')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(
            'prenotes',
            [
                'description' => 'A Prenote',
            ]
        );
    }

    public function testItDoesntEditPrenoteIfIfDescriptionEmpty()
    {
        Livewire::test(PrenoteResource\Pages\EditPrenote::class, ['record' => 1])
            ->set('data.description', '')
            ->call('save')
            ->assertHasErrors(['data.description']);
    }

    public function testItDoesntEditPrenoteIfDescriptionTooLong()
    {
        Livewire::test(PrenoteResource\Pages\EditPrenote::class, ['record' => 1])
            ->set('data.description', Str::padRight('', 256, 'a'))
            ->call('save')
            ->assertHasErrors(['data.description']);
    }

    protected function getViewEditRecord(): Model
    {
        return Prenote::findOrFail(1);
    }

    protected function getResourceClass(): string
    {
        return PrenoteResource::class;
    }

    protected function getEditText(): string
    {
        return 'Edit Prenote One';
    }

    protected function getCreateText(): string
    {
        return 'Create prenote';
    }

    protected function getViewText(): string
    {
        return 'View Prenote One';
    }

    protected function getIndexText(): array
    {
        return ['Prenotes', 'Prenote One', 'Prenote Two'];
    }
}
