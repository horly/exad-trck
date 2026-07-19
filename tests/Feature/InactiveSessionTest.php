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
        ->assertSee('css/auth-login.css', false)
        ->assertSee('name="remember"', false)
        ->assertSee(__('auth.remember'))
        ->assertSee("Votre session a expiré après 30 minutes d'inactivité.");
});

test('login creates a persistent remember cookie only when requested', function () {
    $user = User::factory()->superadmin()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertRedirect()
        ->assertCookieMissing(Auth::guard()->getRecallerName());

    Auth::logout();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
        'remember' => '1',
    ])
        ->assertRedirect()
        ->assertCookie(Auth::guard()->getRecallerName());

    $this->assertAuthenticatedAs($user);
});
