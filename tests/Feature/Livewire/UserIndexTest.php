<?php

use App\Livewire\UserIndex;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

it('creates an unverified user and sends the verification email', function () {
    Notification::fake();
    $admin = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(UserIndex::class)
        ->set('name', 'Operator Outlet')
        ->set('email', 'operator@example.com')
        ->set('password', 'Password123!')
        ->set('passwordConfirmation', 'Password123!')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::query()->where('email', 'operator@example.com')->firstOrFail();

    expect($user->name)->toBe('Operator Outlet')
        ->and($user->email_verified_at)->toBeNull();

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('validates unique email and matching password confirmation', function () {
    $admin = User::factory()->create();
    User::factory()->create(['email' => 'existing@example.com']);

    Livewire::actingAs($admin)
        ->test(UserIndex::class)
        ->set('name', 'Operator Outlet')
        ->set('email', 'existing@example.com')
        ->set('password', 'Password123!')
        ->set('passwordConfirmation', 'different-password')
        ->call('save')
        ->assertHasErrors(['email', 'password']);
});

it('updates a user without changing the password when password is empty', function () {
    Notification::fake();
    $admin = User::factory()->create();
    $user = User::factory()->create([
        'name' => 'Nama Lama',
        'email' => 'user@example.com',
        'password' => 'OldPassword123!',
    ]);
    $oldPassword = $user->password;

    Livewire::actingAs($admin)
        ->test(UserIndex::class)
        ->call('openEditModal', $user->id)
        ->set('name', 'Nama Baru')
        ->call('save')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toBe('Nama Baru')
        ->and($user->password)->toBe($oldPassword)
        ->and($user->email_verified_at)->not->toBeNull();

    Notification::assertNothingSent();
});

it('resets verification and sends notification when email changes', function () {
    Notification::fake();
    $admin = User::factory()->create();
    $user = User::factory()->create(['email' => 'old@example.com']);

    Livewire::actingAs($admin)
        ->test(UserIndex::class)
        ->call('openEditModal', $user->id)
        ->set('email', 'new@example.com')
        ->set('password', 'NewPassword123!')
        ->set('passwordConfirmation', 'NewPassword123!')
        ->call('save')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->email)->toBe('new@example.com')
        ->and($user->email_verified_at)->toBeNull()
        ->and(Hash::check('NewPassword123!', $user->password))->toBeTrue();

    Notification::assertSentTo($user, VerifyEmail::class);
});
