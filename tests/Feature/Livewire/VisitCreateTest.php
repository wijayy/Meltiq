<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('visit-create')
        ->assertStatus(200);
});
