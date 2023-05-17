@php use App\Models\Stand\Stand; @endphp
<div>
    <form wire:submit.prevent="submit">
        {{ $this->form }}

        <x-filament::button type="submit" color="primary" style="margin-top: 25px">Generate Stands</x-filament::button>
    </form>

    @if($prioritisedStands && $prioritisedStands->isNotEmpty())
        @foreach($prioritisedStands as $groupNumber => $stands)
            <div>
                <h1 class="filament-header-heading text-xl font-bold tracking-tight">Group {{ $groupNumber + 1 }}</h1>
                <p>
                    {{$stands->map(fn(Stand $stand) => $stand->identifier)->join(', ')}}
                </p>
            </div>
        @endforeach
    @endif
</div>
