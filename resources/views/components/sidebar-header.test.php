<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('sidebar-header')
        ->assertStatus(200);
});
