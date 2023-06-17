<x-filament::page>
    <form wire:submit.prevent="submit" class="filament-forms-card-component p-6 bg-white rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800">
        {{ $this->form }}

        <x-forms::button wire:loading.attr="disabled" type="submit" class="mt-4 w-full">
            <span wire:loading.remove>Generate</span>
            <span wire:loading>{{ __('filament-seeder::seed.generating') }}</span>
        </x-forms::button>
    </form>
</x-filament::page>
