<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Profile\UpdateAvatarAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('settings/Profile');
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // current_password is a verification field, not a User column — it
        // is intentionally absent from User's #[Fillable(...)] list. Strip
        // it explicitly rather than relying on fill() to drop it silently:
        // Model::preventSilentlyDiscardingAttributes() is enabled outside
        // production and will throw a MassAssignmentException otherwise.
        $request->user()->fill(
            Arr::except($request->validated(), ['current_password'])
        );

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return to_route('profile.edit');
    }

    public function updateAvatar(Request $request, UpdateAvatarAction $updateAvatar): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        $updateAvatar->handle($request->user(), $request->file('avatar'));

        return back()->with('flash', ['type' => 'success', 'message' => 'Avatar updated.']);
    }

    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
