<?php

use App\Models\Device;
use App\Models\Fleet;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserLoginHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('superadmin can access every subscription', function () {
    $firstSubscription = Subscription::factory()->create();
    $secondSubscription = Subscription::factory()->create();
    $superadmin = User::factory()->superadmin()->create();

    expect($superadmin->canAccessSubscription($firstSubscription))->toBeTrue()
        ->and($superadmin->canAccessSubscription($secondSubscription))->toBeTrue()
        ->and(Gate::forUser($superadmin)->allows('manage-subscriptions'))->toBeTrue();
});

test('admin and user are limited to their subscription', function () {
    $ownSubscription = Subscription::factory()->create();
    $otherSubscription = Subscription::factory()->create();
    $admin = User::factory()->admin($ownSubscription)->create();
    $user = User::factory()->simpleUser($ownSubscription)->create();

    expect($admin->canAccessSubscription($ownSubscription))->toBeTrue()
        ->and($admin->canAccessSubscription($otherSubscription))->toBeFalse()
        ->and($user->canAccessSubscription($ownSubscription))->toBeTrue()
        ->and($user->canAccessSubscription($otherSubscription))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('manage-subscription-users', $ownSubscription))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('manage-subscription-users', $otherSubscription))->toBeFalse()
        ->and(Gate::forUser($user)->allows('manage-users'))->toBeFalse();
});

test('dashboard is reserved to superadmin users', function () {
    $ownSubscription = Subscription::factory()->create();
    $otherSubscription = Subscription::factory()->create();
    $admin = User::factory()->admin($ownSubscription)->create();
    $superadmin = User::factory()->superadmin()->create();

    Device::factory()->create([
        'subscription_id' => $ownSubscription->id,
        'name' => 'Vehicule autorise',
    ]);

    Device::factory()->create([
        'subscription_id' => $otherSubscription->id,
        'name' => 'Vehicule masque',
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertForbidden();

    $this->actingAs($superadmin)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Vehicule autorise')
        ->assertSee('Vehicule masque');
});

test('disabled users cannot authenticate with fortify', function () {
    $user = User::factory()->disabled()->create([
        'email' => 'disabled@example.com',
        'password' => 'password',
    ]);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('fortify redirects users by role after login', function () {
    $subscription = Subscription::factory()->create();
    $superadmin = User::factory()->superadmin()->create([
        'email' => 'superadmin-login@example.com',
        'password' => 'password',
    ]);
    $admin = User::factory()->admin($subscription)->create([
        'email' => 'admin-login@example.com',
        'password' => 'password',
    ]);

    $this->post(route('login'), [
        'email' => $superadmin->email,
        'password' => 'password',
    ], [
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
    ])->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('user_login_histories', [
        'user_id' => $superadmin->id,
        'device' => 'Edge on Windows',
        'ip_address' => '127.0.0.1',
    ]);

    $this->post(route('logout'));

    $this->post(route('login'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('fleets.index'));
});

test('non superadmin users cannot manage fleets from superadmin console', function () {
    $ownSubscription = Subscription::factory()->create();
    $admin = User::factory()->admin($ownSubscription)->create();
    $user = User::factory()->simpleUser($ownSubscription)->create();

    $this->actingAs($user)
        ->post(route('fleets.store'), [
            'name' => 'Flotte interdite',
            'code' => 'DENIED',
            'status' => 'active',
        ])
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('fleets.store'), [
            'name' => 'Flotte interdite admin',
            'code' => 'DENIED-ADMIN',
            'status' => 'active',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('fleets', ['code' => 'DENIED']);
    $this->assertDatabaseMissing('fleets', ['code' => 'DENIED-ADMIN']);
});

test('superadmin can view and create users without subscription fields', function () {
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)
        ->get(route('users.index'))
        ->assertSuccessful()
        ->assertSee('Utilisateurs')
        ->assertSee('Nouvel utilisateur')
        ->assertDontSee('value="superadmin"', false)
        ->assertDontSee(route('users.destroy', $superadmin), false)
        ->assertDontSee('aria-label="Modifier"', false)
        ->assertDontSee('aria-label="Supprimer"', false)
        ->assertDontSee('Abonnement')
        ->assertDontSee('Grade')
        ->assertDontSee('Statut');

    $this->actingAs($superadmin)
        ->post(route('users.store'), [
            'name' => 'agent test',
            'email' => 'agent@example.com',
            'password' => 'AgentPassword@123',
            'password_confirmation' => 'AgentPassword@123',
            'role' => 'user',
            'phone' => '+243810000000',
            'address' => 'Kinshasa',
        ])
        ->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', [
        'email' => 'agent@example.com',
        'role' => 'user',
        'phone' => '+243810000000',
    ]);

    $this->actingAs($superadmin)
        ->post(route('users.store'), [
            'name' => 'superadmin interdit',
            'email' => 'superadmin-interdit@example.com',
            'password' => 'AgentPassword@123',
            'password_confirmation' => 'AgentPassword@123',
            'role' => 'superadmin',
        ])
        ->assertSessionHasErrors('role');
});

test('users list keeps superadmin first then newest users', function () {
    $superadmin = User::factory()->superadmin()->create([
        'name' => 'superadmin',
    ]);

    User::factory()->simpleUser()->create([
        'name' => 'old user',
    ]);

    User::factory()->admin()->create([
        'name' => 'new admin',
    ]);

    $this->actingAs($superadmin)
        ->get(route('users.index'))
        ->assertSeeInOrder([
            'superadmin',
            'new admin',
            'old user',
        ]);
});

test('normal users page load ignores stale sort parameters', function () {
    $superadmin = User::factory()->superadmin()->create(['name' => 'superadmin']);
    User::factory()->simpleUser()->create(['name' => 'z old user']);
    User::factory()->simpleUser()->create(['name' => 'a latest user']);

    $this->actingAs($superadmin)
        ->get(route('users.index', ['sort' => 'name', 'direction' => 'asc']))
        ->assertSuccessful()
        ->assertSeeInOrder([
            'superadmin',
            'a latest user',
            'z old user',
        ])
        ->assertDontSee('datatable-sort-link active', false);
});

test('users table paginates searches and sorts like a datatable', function () {
    $superadmin = User::factory()->superadmin()->create([
        'name' => 'superadmin',
        'created_at' => now()->subDays(10),
    ]);

    foreach (range(1, 6) as $index) {
        User::factory()->simpleUser()->create([
            'name' => "agent {$index}",
            'email' => "agent{$index}@example.com",
            'created_at' => now()->subDays($index),
        ]);
    }

    $this->actingAs($superadmin)
        ->get(route('users.index'))
        ->assertSuccessful()
        ->assertSee('data-datatable-search-form', false)
        ->assertSee('data-datatable-search', false)
        ->assertSee('datatable-sort-link', false)
        ->assertSee('5 / 7 lignes')
        ->assertSee('Affichage de 1 à 5 sur 7')
        ->assertSee('datatable-pagination', false)
        ->assertSee('agent 6')
        ->assertDontSee('agent 1');

    $this->actingAs($superadmin)
        ->get(route('users.index', ['search' => 'agent6']))
        ->assertSuccessful()
        ->assertSee('1 / 1 lignes')
        ->assertSee('agent 6')
        ->assertDontSee('agent 1');

    $this->actingAs($superadmin)
        ->get(route('users.index', ['sort' => 'name', 'direction' => 'asc']))
        ->assertSuccessful()
        ->assertSeeInOrder([
            'superadmin',
            'agent 6',
            'agent 5',
        ]);

    $response = $this->actingAs($superadmin)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('users.index', ['sort' => 'name', 'direction' => 'asc']));

    expect($response->json('html'))
        ->toContain('sort=name')
        ->toContain('direction=desc');
});

test('users datatable can refresh over ajax without full page reload', function () {
    $superadmin = User::factory()->superadmin()->create();
    User::factory()->simpleUser()->create([
        'name' => 'agent ajax',
        'email' => 'agent-ajax@example.com',
    ]);

    $response = $this->actingAs($superadmin)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('users.index', ['search' => 'ajax']))
        ->assertSuccessful()
        ->assertJsonStructure(['html', 'loginHistories']);

    expect($response->json('html'))
        ->toContain('agent ajax')
        ->toContain('data-datatable-sort');
});

test('superadmin can update non superadmin users from management', function () {
    $superadmin = User::factory()->superadmin()->create();
    $user = User::factory()->simpleUser()->create([
        'name' => 'agent ancien',
        'email' => 'agent-old@example.com',
        'phone' => '+243810000000',
        'address' => 'Kinshasa',
    ]);

    $this->actingAs($superadmin)
        ->get(route('users.index'))
        ->assertSuccessful()
        ->assertSee(route('users.update', $user), false)
        ->assertSee('data-user-edit', false)
        ->assertSee('data-confirm-delete', false)
        ->assertSee('data-confirm-processing="Traitement..."', false)
        ->assertSee('Supprimer cet utilisateur ?', false);

    $this->actingAs($superadmin)
        ->put(route('users.update', $user), [
            'name' => 'agent modifie',
            'email' => 'agent-updated@example.com',
            'password' => '',
            'password_confirmation' => '',
            'role' => 'admin',
            'phone' => '+243820000000',
            'address' => 'Gombe',
        ])
        ->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'agent modifie',
        'email' => 'agent-updated@example.com',
        'role' => 'admin',
        'phone' => '+243820000000',
        'address' => 'Gombe',
    ]);

    $passwordBefore = $user->password;

    $this->actingAs($superadmin)
        ->put(route('users.update', $user), [
            'name' => 'agent modifie',
            'email' => 'agent-updated@example.com',
            'password' => 'AgentPassword@456',
            'password_confirmation' => 'AgentPassword@456',
            'role' => 'user',
        ])
        ->assertRedirect(route('users.index'));

    $user->refresh();

    expect($user->password)->not->toBe($passwordBefore)
        ->and(Hash::check('AgentPassword@456', $user->password))->toBeTrue();
});

test('users page renders login history modal data', function () {
    $superadmin = User::factory()->superadmin()->create();
    $user = User::factory()->simpleUser()->create(['name' => 'user historique']);

    UserLoginHistory::query()->create([
        'user_id' => $user->id,
        'device' => 'Firefox on Windows',
        'ip_address' => '127.0.0.1',
        'logged_in_at' => now()->setDate(2026, 5, 26)->setTime(14, 1, 13),
    ]);

    $this->actingAs($superadmin)
        ->get(route('users.index'))
        ->assertSuccessful()
        ->assertSee('Historique de connexion de', false)
        ->assertSee('Firefox on Windows')
        ->assertSee('127.0.0.1')
        ->assertSee('2026-05-26 14:01:13');
});

test('superadmin user cannot be updated from user management', function () {
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)
        ->put(route('users.update', $superadmin), [
            'name' => 'superadmin modifie',
            'email' => 'superadmin-modifie@example.com',
            'role' => 'admin',
        ])
        ->assertForbidden();
});

test('user deletion flashes danger toast status', function () {
    $superadmin = User::factory()->superadmin()->create();
    $user = User::factory()->simpleUser()->create();

    $this->actingAs($superadmin)
        ->delete(route('users.destroy', $user))
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('status_type', 'danger')
        ->assertSessionHas('status', __('users.deleted'));

    $this->assertModelMissing($user);
});

test('non superadmin users cannot access user management', function () {
    $subscription = Subscription::factory()->create();
    $admin = User::factory()->admin($subscription)->create();

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertForbidden();
});

test('non superadmin users cannot access current superadmin console pages', function () {
    $subscription = Subscription::factory()->create();
    $admin = User::factory()->admin($subscription)->create();

    foreach ([
        route('dashboard'),
        route('users.index'),
        route('fleets.index'),
        route('vehicles.index'),
        route('trackers.index'),
        route('map.index'),
        route('map.devices'),
        route('alerts.index'),
        route('customization.index'),
    ] as $url) {
        $this->actingAs($admin)
            ->get($url)
            ->assertForbidden();
    }
});

test('reverb superadmin alerts channel is private to superadmin users', function () {
    $subscription = Subscription::factory()->create();
    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin($subscription)->create();
    $payload = [
        'socket_id' => '1234.5678',
        'channel_name' => 'private-superadmin.alerts',
    ];

    $this->actingAs($superadmin)
        ->postJson('/broadcasting/auth', $payload)
        ->assertSuccessful();

    $this->actingAs($admin)
        ->postJson('/broadcasting/auth', $payload)
        ->assertForbidden();
});
