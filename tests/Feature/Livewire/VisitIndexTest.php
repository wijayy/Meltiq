<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('visit-index')
        ->assertStatus(200);
});
