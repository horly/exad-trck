<?php

use App\Models\ApplicationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function validCustomizationPayload(array $overrides = []): array
{
    return array_merge([
        'app_name' => 'EXAD Tracking',
        'short_name' => 'EXAD Tracking',
        'website_url' => 'https://exadtracking.app',
        'map_provider' => 'google',
        'primary_color' => '#171064',
        'secondary_color' => '#2F67E8',
        'button_color' => '#171064',
        'avatar_color' => '#1D4ED8',
        'accent_color' => '#6D3DF2',
        'sidebar_start_color' => '#1B146F',
        'sidebar_end_color' => '#0F0A43',
        'support_email' => 'support@exadtracking.app',
        'support_phone' => '+243 000 000 000',
    ], $overrides);
}

test('customization page exposes the approved corporate settings only', function () {
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)
        ->get(route('customization.index'))
        ->assertSuccessful()
        ->assertSee('name="app_name"', false)
        ->assertSee('name="short_name"', false)
        ->assertSee('name="logo"', false)
        ->assertSee('name="internal_logo"', false)
        ->assertSee('name="favicon"', false)
        ->assertSee('name="map_provider"', false)
        ->assertSee('data-theme-color', false)
        ->assertSee('name="support_email"', false)
        ->assertDontSee('name="slogan"', false)
        ->assertDontSee('name="description"', false)
        ->assertDontSee('name="copyright"', false);

    expect(Schema::hasColumns('application_settings', ['app_name', 'short_name', 'map_provider', 'logo_path', 'internal_logo_path', 'favicon_path']))->toBeTrue()
        ->and(Schema::hasColumn('application_settings', 'slogan'))->toBeFalse()
        ->and(Schema::hasColumn('application_settings', 'description'))->toBeFalse()
        ->and(Schema::hasColumn('application_settings', 'copyright'))->toBeFalse();
});

test('superadmin can update and propagate application identity and colors', function () {
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)
        ->patch(route('customization.update'), validCustomizationPayload([
            'app_name' => 'EXAD Fleet Control',
            'short_name' => 'EXAD Fleet',
            'map_provider' => 'mapbox',
            'button_color' => '#123456',
            'sidebar_start_color' => '#112233',
            'sidebar_end_color' => '#223344',
        ]))
        ->assertRedirect(route('customization.index'))
        ->assertSessionHas('customization_status', 'updated');

    $settings = ApplicationSetting::query()->firstOrFail();
    expect($settings)
        ->app_name->toBe('EXAD Fleet Control')
        ->short_name->toBe('EXAD Fleet')
        ->map_provider->toBe('mapbox')
        ->button_color->toBe('#123456')
        ->sidebar_start_color->toBe('#112233')
        ->sidebar_end_color->toBe('#223344');

    $this->post(route('logout'));

    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('EXAD Fleet Control')
        ->assertSee('--exad-button: #123456', false);

    $this->actingAs($superadmin)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('EXAD Fleet - v.1.0')
        ->assertSee('--exad-sidebar: #112233', false);
});

test('customization validation errors remain attached to their fields', function () {
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)
        ->from(route('customization.index'))
        ->patch(route('customization.update'), validCustomizationPayload([
            'app_name' => '',
            'website_url' => 'invalid-address',
            'primary_color' => 'blue',
            'map_provider' => 'openstreetmap',
            'internal_logo' => UploadedFile::fake()->create('internal-logo.svg', 10, 'image/svg+xml'),
        ]))
        ->assertRedirect(route('customization.index'))
        ->assertSessionHasErrors(['app_name', 'website_url', 'primary_color', 'map_provider', 'internal_logo']);

    $this->actingAs($superadmin)
        ->get(route('customization.index'))
        ->assertSee('id="app_name" class="form-control', false)
        ->assertSee('id="internal_logo" class="visually-hidden is-invalid', false)
        ->assertSee('is-invalid', false)
        ->assertSee('invalid-feedback d-block', false);
});

test('customization replaces visual identity files without retaining obsolete files', function () {
    Storage::fake('public');
    $superadmin = User::factory()->superadmin()->create();
    $image = file_get_contents(public_path('images/icon-exad-tracking.png'));

    $this->actingAs($superadmin)
        ->patch(route('customization.update'), validCustomizationPayload([
            'logo' => UploadedFile::fake()->createWithContent('logo.png', $image),
            'internal_logo' => UploadedFile::fake()->createWithContent('internal-logo.png', $image),
            'favicon' => UploadedFile::fake()->createWithContent('favicon.png', $image),
        ]))
        ->assertRedirect(route('customization.index'));

    $settings = ApplicationSetting::query()->firstOrFail();
    $firstLogo = $settings->logo_path;
    $firstInternalLogo = $settings->internal_logo_path;
    $firstFavicon = $settings->favicon_path;
    Storage::disk('public')->assertExists($firstLogo);
    Storage::disk('public')->assertExists($firstInternalLogo);
    Storage::disk('public')->assertExists($firstFavicon);

    $this->actingAs($superadmin)
        ->patch(route('customization.update'), validCustomizationPayload([
            'logo' => UploadedFile::fake()->createWithContent('replacement.png', $image),
            'internal_logo' => UploadedFile::fake()->createWithContent('internal-replacement.png', $image),
        ]))
        ->assertRedirect(route('customization.index'));

    $settings->refresh();
    expect($settings->logo_path)->not->toBe($firstLogo)
        ->and($settings->internal_logo_path)->not->toBe($firstInternalLogo)
        ->and($settings->favicon_path)->toBe($firstFavicon);
    Storage::disk('public')->assertMissing($firstLogo);
    Storage::disk('public')->assertMissing($firstInternalLogo);
    Storage::disk('public')->assertExists($settings->logo_path);
    Storage::disk('public')->assertExists($settings->internal_logo_path);
    Storage::disk('public')->assertExists($firstFavicon);
});

test('sidebar uses the dedicated internal logo', function () {
    $superadmin = User::factory()->superadmin()->create();
    $settings = ApplicationSetting::query()->firstOrFail();
    $settings->update(['internal_logo_path' => 'application-branding/internal-logo/sidebar.png']);

    $this->actingAs($superadmin)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('/storage/application-branding/internal-logo/sidebar.png', false);
});

test('non superadmin users cannot update global customization', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch(route('customization.update'), validCustomizationPayload())
        ->assertForbidden();
});
