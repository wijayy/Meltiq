<div class="flex h-[calc(100dvh-8rem)] flex-col gap-4 overflow-hidden lg:h-[calc(100dvh-4rem)]">
    <x-slot name="title">{{ $title }}</x-slot>

    <flux:sidebar-header>
        {{ $title }}

        <x-slot name="button">
            <flux:button wire:click="openCreateModal" icon="plus" variant="primary" size="sm">
                Tambah Pengguna
            </flux:button>
        </x-slot>
    </flux:sidebar-header>

    <flux:sidebar-content>
        <div class="w-full sm:w-80">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                placeholder="Cari nama atau email user..." />
        </div>

        <div class="flex min-w-3xl items-center gap-4 text-sm font-semibold">
            <div class="w-10">#</div>
            <div class="w-1/3">Nama</div>
            <div class="w-1/3">Email</div>
            <div class="w-1/4 text-center">Status Verifikasi</div>
            <div class="w-24 text-center">Aksi</div>
        </div>

        @forelse ($this->users as $index => $user)
            <div wire:key="user-{{ $user->id }}"
                class="flex min-w-3xl items-center gap-4 border-t border-mine-200 pt-2 text-sm dark:border-mine-400">
                <div class="w-10">{{ $index + 1 }}</div>
                <div class="w-1/3 font-semibold">{{ $user->name }}</div>
                <div class="w-1/3">{{ $user->email }}</div>
                <div class="w-1/4 text-center">
                    @if ($user->hasVerifiedEmail())
                        <flux:badge color="green" size="sm">Terverifikasi</flux:badge>
                    @else
                        <flux:badge color="amber" size="sm">Menunggu Verifikasi</flux:badge>
                    @endif
                </div>
                <div class="flex w-24 justify-center">
                    <flux:button wire:click="openEditModal({{ $user->id }})" icon="pencil-square"
                        variant="primary" size="sm">Ubah</flux:button>
                </div>
            </div>
        @empty
            <div class="min-w-3xl border-t border-mine-200 py-10 text-center text-sm text-mine-300 dark:border-mine-400 dark:text-mine-100">
                Pengguna tidak ditemukan.
            </div>
        @endforelse

        <flux:modal name="user-create" class="max-w-lg">
            <form wire:submit="save" class="space-y-5">
                <div>
                    <flux:heading size="lg" class="text-mine-400 dark:text-mine-100">
                        {{ $userId ? 'Ubah Pengguna' : 'Tambah Pengguna' }}
                    </flux:heading>
                    <flux:text class="mt-1 text-mine-300 dark:text-mine-200">
                        @if ($userId)
                            Kosongkan password jika tidak ingin mengubahnya. Perubahan email memerlukan verifikasi ulang.
                        @else
                            Pengguna akan menerima email verifikasi sebelum dapat mengakses sistem.
                        @endif
                    </flux:text>
                </div>

                <flux:input wire:model="name" label="Nama" autocomplete="name" required />
                <flux:input wire:model="email" label="Email" type="email" autocomplete="email" required />
                <flux:input wire:model="password" :label="$userId ? 'Password Baru (Opsional)' : 'Password Awal'"
                    type="password" autocomplete="new-password" viewable :required="! $userId" />
                <flux:input wire:model="passwordConfirmation" label="Konfirmasi Password" type="password"
                    autocomplete="new-password" viewable :required="! $userId" />

                <div class="flex justify-end gap-2 border-t border-mine-200 pt-4 dark:border-mine-400">
                    <flux:button type="button" wire:click="closeModal" variant="ghost">Batal</flux:button>
                    <flux:button type="submit" wire:loading.attr="disabled" wire:target="save" icon="check"
                        variant="primary">
                        <span wire:loading.remove wire:target="save">Simpan</span>
                        <span wire:loading wire:target="save">Menyimpan...</span>
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    </flux:sidebar-content>
</div>
