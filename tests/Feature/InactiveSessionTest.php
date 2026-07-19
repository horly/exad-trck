<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

test('authenticated pages configure automatic logout after thirty minutes of inactivity', function () {
    $user = User::factory()->superadmin()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('data-inactivity-timeout="1800000"', false)
        ->assertSee('data-inactivity-user="'.$user->id.'"', false)
        ->assertSee('data-inactivity-logout', false)
        ->assertSee('js/inactivity-logout.js', false);

    expect(config('session.lifetime'))->toBe(30)
        ->and(config('session.inactivity_timeout'))->toBe(30)
        ->and(config('session.expire_on_close'))->toBeTrue();
});

test('login page explains an inactivity logout', function () {
    $this->get(route('login', ['reason' => 'inactive']))
        ->assertSuccessful()
        ->assertSee("Votre session a expiré après 30 minutes d'inactivité.");
});

test('login never creates a persistent remember cookie', function () {
    $user = User::factory()->superadmin()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
        'remember' => '1',
    ])
        ->assertRedirect()
        ->assertCookieMissing(Auth::guard()->getRecallerName());

    $this->assertAuthenticatedAs($user);
});
