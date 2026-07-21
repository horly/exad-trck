<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

test('profile page is available to every authenticated role', function () {
    $users = [
        User::factory()->superadmin()->create(),
        User::factory()->admin()->create(),
        User::factory()->simpleUser()->create(),
    ];

    $this->get(route('profile.show'))->assertRedirect(route('login'));

    foreach ($users as $user) {
        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertSuccessful()
            ->assertSee('vendor/cropperjs/cropper.min.js', false)
            ->assertSee('data-profile-photo-source', false)
            ->assertSee('data-profile-password-form', false)
            ->assertSee('aria-live="polite"', false)
            ->assertSee(route('profile.personal.update'), false)
            ->assertSee(route('profile.two-factor.enable'), false)
            ->assertDontSee('Abonnement :');
    }
});

test('profile link is connected to the global user menu', function () {
    $user = User::factory()->superadmin()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee(route('profile.show'), false);
});

test('user can update personal profile information', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.personal.update'), [
            'name' => '  Horly Andelo  ',
            'phone' => '  +243 999 000 111  ',
            'address' => '  Gombe, Kinshasa  ',
        ])
        ->assertRedirect(route('profile.show'))
        ->assertSessionHas('profile_status', 'personal-updated');

    expect($user->refresh())
        ->name->toBe('Horly Andelo')
        ->phone->toBe('+243 999 000 111')
        ->address->toBe('Gombe, Kinshasa');
});

test('email change requires the current password', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);

    $this->actingAs($user)
        ->patch(route('profile.email.update'), [
            'email' => 'new@example.com',
            'email_current_password' => 'incorrect',
        ])
        ->assertSessionHasErrorsIn('updateEmail', ['email_current_password']);

    expect($user->refresh()->email)->toBe('old@example.com');

    $this->actingAs($user)
        ->patch(route('profile.email.update'), [
            'email' => 'NEW@example.com',
            'email_current_password' => 'password',
        ])
        ->assertRedirect(route('profile.show'))
        ->assertSessionHas('profile_status', 'email-updated');

    expect($user->refresh()->email)->toBe('new@example.com');
});

test('user can replace and remove a cropped profile photo', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('profile.photo.update'), [
            'photo' => UploadedFile::fake()->createWithContent('profile.png', file_get_contents(public_path('images/icon-exad-tracking.png'))),
        ])
        ->assertRedirect(route('profile.show'));

    $firstPath = $user->refresh()->profile_photo_path;
    expect($firstPath)->not->toBeNull();
    Storage::disk('public')->assertExists($firstPath);

    $this->actingAs($user)
        ->post(route('profile.photo.update'), [
            'photo' => UploadedFile::fake()->createWithContent('replacement.png', file_get_contents(public_path('images/icon-exad-tracking.png'))),
        ])
        ->assertRedirect(route('profile.show'));

    $secondPath = $user->refresh()->profile_photo_path;
    expect($secondPath)->not->toBe($firstPath);
    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($secondPath);

    $this->actingAs($user)
        ->delete(route('profile.photo.destroy'))
        ->assertRedirect(route('profile.show'));

    expect($user->refresh()->profile_photo_path)->toBeNull();
    Storage::disk('public')->assertMissing($secondPath);
});

test('two factor authentication is disabled by default and controlled by the user', function () {
    $user = User::factory()->create();

    expect($user->hasEnabledTwoFactorAuthentication())->toBeFalse()
        ->and($user->two_factor_secret)->toBeNull();

    $this->actingAs($user)
        ->post(route('profile.two-factor.enable'), ['enable_current_password' => 'incorrect'])
        ->assertSessionHasErrorsIn('enableTwoFactorAuthentication', ['enable_current_password']);

    expect($user->refresh()->two_factor_secret)->toBeNull();

    $this->actingAs($user)
        ->post(route('profile.two-factor.enable'), ['enable_current_password' => 'password'])
        ->assertRedirect(route('profile.show'))
        ->assertSessionHas('profile_status', 'two-factor-setup');

    $user->refresh();
    expect($user->two_factor_secret)->not->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull()
        ->and($user->hasEnabledTwoFactorAuthentication())->toBeFalse();

    $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertSuccessful()
        ->assertSee('profile-qr-code', false)
        ->assertSee(route('profile.two-factor.confirm'), false);

    $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);
    $code = app(Google2FA::class)->getCurrentOtp($secret);

    $this->actingAs($user)
        ->post(route('profile.two-factor.confirm'), ['code' => $code])
        ->assertRedirect(route('profile.show'))
        ->assertSessionHas('profile_status', 'two-factor-enabled')
        ->assertSessionHas('recovery_codes');

    expect($user->refresh()->hasEnabledTwoFactorAuthentication())->toBeTrue()
        ->and($user->recoveryCodes())->toHaveCount(8);

    $this->actingAs($user)
        ->post(route('profile.two-factor.recovery-codes'), ['recovery_current_password' => 'password'])
        ->assertRedirect(route('profile.show'))
        ->assertSessionHas('recovery_codes');

    $this->actingAs($user)
        ->delete(route('profile.two-factor.disable'), ['disable_current_password' => 'password'])
        ->assertRedirect(route('profile.show'))
        ->assertSessionHas('profile_status', 'two-factor-disabled');

    expect($user->refresh()->hasEnabledTwoFactorAuthentication())->toBeFalse()
        ->and($user->two_factor_secret)->toBeNull();
});

test('confirmed two factor users are challenged during login', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('profile.two-factor.enable'), ['enable_current_password' => 'password']);
    $user->refresh()->forceFill(['two_factor_confirmed_at' => now()])->save();

    auth()->logout();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.login'));

    $this->assertGuest();
});

test('password update form remains backed by fortify', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'Corporate#2026',
            'password_confirmation' => 'Corporate#2026',
        ])
        ->assertRedirect();

    expect(Hash::check('Corporate#2026', $user->refresh()->password))->toBeTrue();
});

test('profile password form exposes interactive validation states', function () {
    $script = file_get_contents(public_path('js/profile.js'));
    $styles = file_get_contents(public_path('css/profile.css'));

    expect($script)
        ->toContain("setValidationState(passwordRules[rule], valid, passwordDirty)")
        ->toContain("setValidationState(passwordRules.match, confirmationValid, confirmationDirty)")
        ->toContain("setValidationState(confirmation, confirmationValid, confirmationDirty)")
        ->and($styles)
        ->toContain('.profile-password-rules li.is-invalid')
        ->toContain('.profile-password-rules li.is-valid');
});

test('fortify security challenge views are configured', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('password.confirm'))
        ->assertSuccessful()
        ->assertSee('data-auth-password-toggle', false);

    $user->forceFill([
        'two_factor_secret' => Fortify::currentEncrypter()->encrypt('test-secret'),
        'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['recovery-code'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    auth()->logout();
    $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);

    $this->get(route('two-factor.login'))
        ->assertSuccessful()
        ->assertSee('data-auth-recovery-toggle', false);
});
