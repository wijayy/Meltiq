<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('create-post')
        ->assertStatus(200);
});
