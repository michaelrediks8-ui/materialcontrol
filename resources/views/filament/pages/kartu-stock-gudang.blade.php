<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}
    </form>

    {{ $this->table }}
</x-filament-panels::page>
