<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSecuritySettingsRequest;
use App\Models\ApplicationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show()
    {
        return view('profile.show', [
            'user' => Auth::user(),
        ]);
    }

    public function edit()
    {
        return view('profile.edit', [
            'user' => Auth::user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'bio' => ['nullable', 'string', 'max:500'],
            'profile_photo' => ['nullable', 'image', 'max:5120'], // 5MB max
        ], [
            'profile_photo.image' => 'The profile photo must be an image file.',
            'profile_photo.max' => 'The profile photo may not be greater than 5MB.',
        ]);

        if ($request->hasFile('profile_photo')) {
            // Delete old photo if exists
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            // Store new photo
            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $user->profile_photo_path = $path;
        }

        if (isset($validated['bio'])) {
            $user->bio = $validated['bio'];
        }

        $user->save();

        return redirect()
            ->route('profile.show')
            ->with('success', 'Your profile has been updated successfully.');
    }

    public function settings()
    {
        return view('profile.settings', [
            'sessionTimeout' => ApplicationSetting::get('session_timeout', 120),
            'loginMaxAttempts' => ApplicationSetting::get('login_max_attempts', 5),
            'lockoutDurationMinutes' => ApplicationSetting::get('lockout_duration_minutes', 15),
            'passwordMinLength' => ApplicationSetting::get('password_min_length', 8),
            'passwordRequireUppercase' => ApplicationSetting::get('password_require_uppercase', '1') === '1',
            'passwordRequireNumber' => ApplicationSetting::get('password_require_number', '1') === '1',
            'passwordRequireSymbol' => ApplicationSetting::get('password_require_symbol', '0') === '1',
            'gracefulErrors' => ApplicationSetting::gracefulErrorsEnabled(),
        ]);
    }

    public function updateSettings(UpdateSecuritySettingsRequest $request)
    {
        $validated = $request->validated();

        ApplicationSetting::set('session_timeout', $validated['session_timeout']);
        ApplicationSetting::set('login_max_attempts', $validated['login_max_attempts']);
        ApplicationSetting::set('lockout_duration_minutes', $validated['lockout_duration_minutes']);
        ApplicationSetting::set('password_min_length', $validated['password_min_length']);
        ApplicationSetting::set('password_require_uppercase', $request->boolean('password_require_uppercase') ? '1' : '0');
        ApplicationSetting::set('password_require_number', $request->boolean('password_require_number') ? '1' : '0');
        ApplicationSetting::set('password_require_symbol', $request->boolean('password_require_symbol') ? '1' : '0');
        ApplicationSetting::set('graceful_errors', $request->boolean('graceful_errors') ? '1' : '0');

        // Update Laravel session lifetime config
        config(['session.lifetime' => $validated['session_timeout']]);

        return redirect()
            ->route('profile.settings')
            ->with('success', 'Security settings updated successfully.');
    }
}
