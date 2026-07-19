<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('server operations page exposes logs and the secured console interface', function () {
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)
        ->get(route('server-logs.index'))
        ->assertSuccessful()
        ->assertSee(__('server_logs.logs_tab'))
        ->assertSee(__('server_logs.console_tab'))
        ->assertSee('data-server-console', false)
        ->assertSee('data-console-auth-modal', false)
        ->assertSee('fa-terminal', false)
        ->assertSee('data-console-fullscreen', false)
        ->assertSee('fa-expand', false)
        ->assertSee(__('server_logs.console_fullscreen'))
        ->assertSee(route('server-logs.console-ticket'), false)
        ->assertSee('vendor/server-console/server-console.js', false)
        ->assertDontSee('SERVER_CONSOLE_TICKET_SECRET', false);
});

test('superadmin receives a valid short-lived console ticket without server credentials', function () {
    $secret = 'test-secret-that-is-longer-than-thirty-two-characters';
    config()->set([
        'server_console.enabled' => true,
        'server_console.ticket_secret' => $secret,
        'server_console.ticket_ttl_seconds' => 30,
        'server_console.gateway_url' => '/server-console/socket',
        'server_console.allowed_username' => 'exad-tracking',
    ]);
    $superadmin = User::factory()->superadmin()->create();

    $response = $this->actingAs($superadmin)
        ->postJson(route('server-logs.console-ticket'))
        ->assertSuccessful()
        ->assertJsonPath('gateway_url', '/server-console/socket')
        ->assertJsonPath('username', 'exad-tracking')
        ->assertJsonPath('expires_in', 30)
        ->assertJsonMissing(['password']);

    [$payload, $signature] = explode('.', $response->json('ticket'));
    $decoded = json_decode(base64_decode(strtr($payload, '-_', '+/')), true, flags: JSON_THROW_ON_ERROR);

    expect($signature)->toBe(hash_hmac('sha256', $payload, $secret))
        ->and($decoded['aud'])->toBe('exad-server-console')
        ->and($decoded['sub'])->toBe((string) $superadmin->id)
        ->and($decoded['exp'] - $decoded['iat'])->toBe(30)
        ->and($decoded)->not->toHaveKey('password');
});

test('console tickets remain unavailable to non superadmins and without gateway configuration', function () {
    $admin = User::factory()->admin()->create();
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($admin)
        ->postJson(route('server-logs.console-ticket'))
        ->assertForbidden();

    config()->set([
        'server_console.enabled' => false,
        'server_console.ticket_secret' => null,
    ]);

    $this->actingAs($superadmin)
        ->postJson(route('server-logs.console-ticket'))
        ->assertServiceUnavailable();
});
