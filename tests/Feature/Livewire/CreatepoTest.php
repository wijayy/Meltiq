<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('createpo')
        ->assertStatus(200);
});
