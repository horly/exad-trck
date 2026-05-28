<?php

use App\Models\Device;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected from the home page to login', function () {
    $this->get('/')
        ->assertRedirect(route('login'));
});

test('users can switch the login language from the language button', function () {
    $this->from(route('login'))
        ->get(route('lang.switch', 'en'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('locale', 'en');

    $this->withSession(['locale' => 'en'])
        ->get(route('login'))
        ->assertSuccessful()
        ->assertSee('Sign in to EXAD Tracking')
        ->assertSee('English');
});

test('unsupported locales are not accepted', function () {
    $this->get('/lang/de')
        ->assertNotFound();
});

test('authenticated users can view dashboard metrics', function () {
    $user = User::factory()->create();
    $onlineDevice = Device::factory()->online()->create(['name' => 'Camion Kin 01']);
    $movingDevice = Device::factory()->moving()->create(['name' => 'Pick-up Gombe']);

    Position::factory()->forDevice($onlineDevice)->create(['server_time' => now()]);
    Position::factory()->forDevice($movingDevice)->create(['server_time' => now()->subDay()]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Tableau de bord')
        ->assertSee('Camion Kin 01')
        ->assertSee('Pick-up Gombe')
        ->assertSee('Positions du jour')
        ->assertSee('1');
});
