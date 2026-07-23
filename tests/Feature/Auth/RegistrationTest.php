<?php

use Symfony\Component\Routing\Exception\RouteNotFoundException;

test('registration routes are disabled', function () {
    expect(fn () => route('register'))->toThrow(RouteNotFoundException::class)
        ->and(fn () => route('register.store'))->toThrow(RouteNotFoundException::class);
});
