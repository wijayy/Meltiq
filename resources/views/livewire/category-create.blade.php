<div class="space-y-4">
    <x-slot name="title">
        {{ $title }}
    </x-slot>

    <flux:modal name="category-create">
        <div class="">{{ $title }}</div>

        <form wire:submit='save' class="mt-4">
            <flux:input wire:model.live="name" label="Category Name" placeholder="Enter category name" required />
            <div class="mt-4 flex justify-end">
                <flux:button type="submit" variant="primary" size="sm">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
