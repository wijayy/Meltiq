<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('stock-index')
        ->assertStatus(200);
});
