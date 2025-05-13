<x-filament::page>
    <form wire:submit.prevent="generateDtrPdf">
        <div class="flex items-center space-x-4">
            <div class="w-1/2">
                <x-filament::forms.components.fieldset>
                    <x-filament::forms.components.fieldset.heading>
                        Select Month
                    </x-filament::forms.components.fieldset.heading>
                    <x-filament::forms.components.date-picker
                        wire:model="month"
                        display-format="F Y"
                        max="today"
                    />
                </x-filament::forms.components.fieldset>
            </div>
            <div class="w-1/2">
                <x-filament::button type="submit" class="w-full">
                    Generate DTR Report
                </x-filament::button>
            </div>
        </div>
    </form>
</x-filament::page>
