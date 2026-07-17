<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('settings.system-settings')
        ->assertStatus(200);
});
