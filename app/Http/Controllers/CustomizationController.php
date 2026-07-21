<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateApplicationSettingRequest;
use App\Models\ApplicationSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CustomizationController extends Controller
{
    public function index(ApplicationSetting $settings): View
    {
        Gate::authorize('manage-platform');

        return view('customization.index', compact('settings'));
    }

    public function update(UpdateApplicationSettingRequest $request, ApplicationSetting $settings): RedirectResponse
    {
        Gate::authorize('manage-platform');

        $validated = $request->validated();
        $oldLogoPath = $settings->logo_path;
        $oldInternalLogoPath = $settings->internal_logo_path;
        $oldFaviconPath = $settings->favicon_path;

        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $request->file('logo')->store('application-branding/logo', 'public');
        }

        if ($request->hasFile('internal_logo')) {
            $validated['internal_logo_path'] = $request->file('internal_logo')->store('application-branding/internal-logo', 'public');
        }

        if ($request->hasFile('favicon')) {
            $validated['favicon_path'] = $request->file('favicon')->store('application-branding/favicon', 'public');
        }

        $settings->fill(Arr::except($validated, ['logo', 'internal_logo', 'favicon']))->save();

        if (isset($validated['logo_path']) && $oldLogoPath && $oldLogoPath !== $validated['logo_path']) {
            Storage::disk('public')->delete($oldLogoPath);
        }

        if (isset($validated['internal_logo_path']) && $oldInternalLogoPath && $oldInternalLogoPath !== $validated['internal_logo_path']) {
            Storage::disk('public')->delete($oldInternalLogoPath);
        }

        if (isset($validated['favicon_path']) && $oldFaviconPath && $oldFaviconPath !== $validated['favicon_path']) {
            Storage::disk('public')->delete($oldFaviconPath);
        }

        return to_route('customization.index')->with('customization_status', 'updated');
    }
}
