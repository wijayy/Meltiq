<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('financial-report')
        ->assertStatus(200);
});
