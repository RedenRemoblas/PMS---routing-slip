<!-- approve-leaves.blade.php -->

<x-filament::page>
    <form wire:submit.prevent="save">
        <!-- Render the form -->
        {{ $this->form }}

        <!-- Approve and Disapprove buttons -->
        <div class="mt-4 space-x-2">
            <x-filament::button type="button" wire:click="approveLeave" color="success">
                Approve
            </x-filament::button>

            <x-filament::button type="button" wire:click="disapproveLeave" color="danger">
                Disapprove
            </x-filament::button>
        </div>
    </form>
</x-filament::page>
