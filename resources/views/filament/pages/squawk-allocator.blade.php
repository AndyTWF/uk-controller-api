<x-filament::page>
    <x-filament::card>
        <p>Please enter the Callsign, Origin and Destination airfields to assign a squawk.</p>
        <p>The following caveats apply:</p>
        <ul>
            <li>The aircraft must be on the ground at the airfield of origin.</li>
            <li>You must be logged in as a recognised controller position that can allocate squawks at the given airfield.</li>
        </ul>
        <p>This action will invalidate any previous squawk assigned by the plugin.</p>
        <form id="allocate" wire:submit.prevent="allocateSquawk" class="space-y-8">
            <label for="callsign">Callsign</label>
            <input type="text" name="callsign" id="callsign" wire:model="callsign" required/>
            <label for="origin">Origin</label>
            <input type="text" name="origin" id="origin" wire:model="origin" required/>
            <label for="destination">Destination</label>
            <input type="text" name="destination" id="destination" wire:model="destination" required/>
            <br>
            <x-filament::button type="submit" form="allocateSquawk">
                Allocate Squawk
            </x-filament::button>
        </form>
        @if ($assignedSquawk):
            <h1>Assigned Squawk</h1>
            <p id="assignedSquawk">{{$assignedSquawk->getCode()}}</p>
        @endif
    </x-filament::card>
</x-filament::page>
