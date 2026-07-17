<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class UserIndex extends Component
{
    public string $title = 'Users';

    #[Url(as: 'search', except: '')]
    public string $search = '';

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public ?int $userId = null;

    /** @return Collection<int, User> */
    #[Computed]
    public function users(): Collection
    {
        return User::query()
            ->when($this->search, fn ($query) => $query
                ->where(fn ($query) => $query
                    ->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%')))
            ->orderBy('name')
            ->get();
    }

    public function updatedSearch(): void
    {
        unset($this->users);
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->dispatch('modal-show', name: 'user-create');
    }

    public function openEditModal(int $id): void
    {
        $user = User::query()->findOrFail($id);

        $this->resetForm();
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->dispatch('modal-show', name: 'user-create');
    }

    public function save(): void
    {
        $passwordRules = $this->userId === null
            ? ['required', 'same:passwordConfirmation', Password::defaults()]
            : ['nullable', 'same:passwordConfirmation', Password::defaults()];

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->userId)],
            'password' => $passwordRules,
            'passwordConfirmation' => [$this->userId === null ? 'required' : 'nullable', 'string'],
        ]);

        $user = $this->userId === null
            ? new User
            : User::query()->findOrFail($this->userId);
        $emailChanged = $user->exists && $user->email !== $validated['email'];

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if ($this->password !== '') {
            $user->password = $validated['password'];
        }

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $isCreating = ! $user->exists;
        $user->save();

        if ($isCreating || $emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        $this->dispatch('modal-close', name: 'user-create');
        session()->flash('success', $isCreating
            ? 'User berhasil ditambahkan. Email verifikasi telah dikirim.'
            : ($emailChanged
                ? 'User berhasil diperbarui. Email baru perlu diverifikasi.'
                : 'User berhasil diperbarui.'));
        $this->resetForm();
        unset($this->users);
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->dispatch('modal-close', name: 'user-create');
    }

    private function resetForm(): void
    {
        $this->reset(['userId', 'name', 'email', 'password', 'passwordConfirmation']);
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.user-index');
    }
}
