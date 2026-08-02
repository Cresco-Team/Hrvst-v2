<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone_number' => $user->phone_number,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    /** @var User $user */
    $user = User::factory()->create([
        'email' => 'original@example.com',
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
            'phone_number' => $user->phone_number,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => '0000',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});

// ─── Phone number self-service editing ────────────────────────────────────────
// phone_number doubles as the login credential (see FortifyServiceProvider),
// and this app has no password-reset fallback (PasswordResetTest 404s
// /forgot-password and /reset-password). Changing it therefore requires
// current_password confirmation — these tests guard that gate specifically,
// not just the happy path.

test('user can update their phone number with correct current password', function () {
    $user = User::factory()->create();
    $user->update(['password' => 'correct-password']);

    $response = $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => '09991234567',
            'current_password' => 'correct-password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->phone_number)->toBe('09991234567');
});

test('changing phone number without current_password fails', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => '09991234567',
        ])
        ->assertSessionHasErrors('current_password');

    expect($user->fresh()->phone_number)->not->toBe('09991234567');
});

test('changing phone number with an incorrect current_password fails', function () {
    $user = User::factory()->create();
    $user->update(['password' => 'correct-password']);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => '09991234567',
            'current_password' => 'wrong-password',
        ])
        ->assertSessionHasErrors('current_password');

    expect($user->fresh()->phone_number)->not->toBe('09991234567');
});

test('updating name and email without changing phone does not require current_password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'New Name',
            'email' => $user->email,
            'phone_number' => $user->phone_number,
        ])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->name)->toBe('New Name');
});

test('phone number must be unique among users', function () {
    $other = User::factory()->create(['phone_number' => '09991234567']);
    $user = User::factory()->create();
    $user->update(['password' => 'correct-password']);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => '09991234567',
            'current_password' => 'correct-password',
        ])
        ->assertSessionHasErrors('phone_number');

    expect($user->fresh()->phone_number)->not->toBe('09991234567');
});

test('phone number must match PH mobile format', function () {
    $user = User::factory()->create();
    $user->update(['password' => 'correct-password']);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => '12345',
            'current_password' => 'correct-password',
        ])
        ->assertSessionHasErrors('phone_number');
});