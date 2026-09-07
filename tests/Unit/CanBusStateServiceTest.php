<?php

use App\Services\CanBusStateService;

test('it decodes teltonika p4 security state flags', function () {
    $flags = (1 << 8)
        | (1 << 10)
        | (1 << 18)
        | (1 << 20)
        | (1 << 22)
        | (1 << 25)
        | (1 << 27)
        | (1 << 48);

    $states = app(CanBusStateService::class)->decode([517 => $flags]);

    expect($states)
        ->toMatchArray([
            'ignition_on' => true,
            'key_in_ignition' => false,
            'webasto_on' => true,
            'engine_running' => false,
            'handbrake_active' => true,
            'footbrake_active' => false,
            'clutch_pressed' => true,
            'front_left_door_open' => true,
            'front_right_door_open' => false,
            'rear_left_door_open' => false,
            'rear_right_door_open' => true,
            'trunk_open' => false,
            'hood_open' => true,
            'roof_open' => true,
            'doors_open' => true,
        ]);
});

test('it decodes legacy security flags and door status', function () {
    $legacyFlags = (1 << 25) | (1 << 36) | (1 << 38) | (1 << 41) | (1 << 45);
    $doorStatus = 0x0100 | 0x0800 | 0x1000;

    $states = app(CanBusStateService::class)->decode([
        90 => $doorStatus,
        132 => $legacyFlags,
    ]);

    expect($states)
        ->ignition_on->toBeTrue()
        ->handbrake_active->toBeTrue()
        ->engine_running->toBeTrue()
        ->front_left_door_open->toBeFalse()
        ->front_right_door_open->toBeTrue()
        ->rear_right_door_open->toBeFalse()
        ->trunk_open->toBeTrue()
        ->hood_open->toBeFalse()
        ->doors_open->toBeTrue();
});

test('individual CAN values take precedence and inverted clutch value is normalized', function () {
    $states = app(CanBusStateService::class)->decode([
        517 => 0,
        654 => 1,
        911 => 0,
    ]);

    expect($states)
        ->front_left_door_open->toBeTrue()
        ->clutch_pressed->toBeTrue()
        ->doors_open->toBeTrue();
});

test('it decodes the current fmb140 production flag values', function () {
    $states = app(CanBusStateService::class)->decode([
        90 => 0,
        132 => 33554435,
        517 => 317,
    ]);

    expect($states)
        ->ignition_on->toBeTrue()
        ->engine_running->toBeFalse()
        ->front_left_door_open->toBeFalse()
        ->rear_right_door_open->toBeFalse()
        ->hood_open->toBeFalse()
        ->trunk_open->toBeFalse()
        ->doors_open->toBeFalse();
});

test('it preserves high p4 flags received as hexadecimal strings', function () {
    $states = app(CanBusStateService::class)->decode([
        517 => '0x0001000000000000',
    ]);

    expect($states)
        ->roof_open->toBeTrue()
        ->ignition_on->toBeFalse();
});

test('it never treats the complete p4 word as a boolean engine state', function () {
    $stopped = app(CanBusStateService::class)->decode(
        [517 => 317],
        ['payload' => ['can' => ['engine_running' => 317]]],
    );
    $running = app(CanBusStateService::class)->decode(
        [517 => 2048],
        ['payload' => ['can' => ['engine_running' => 0]]],
    );

    expect($stopped['engine_running'])->toBeFalse()
        ->and($running['engine_running'])->toBeTrue();
});
