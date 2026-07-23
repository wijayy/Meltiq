<?php

use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Schedule;
use Tests\TestCase;

uses(TestCase::class);

it('schedules stock snapshots every midnight in the application timezone', function () {
    $event = collect(Schedule::events())
        ->first(function (Event|CallbackEvent $event): bool {
            return str_contains((string) ($event->command ?? ''), 'stock:snapshot');
        });

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 0 * * *')
        ->and($event->timezone)->toBe('Asia/Makassar');
});
