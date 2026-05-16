<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('password update page is displayed', function () {
    /** @var User $user */
    $user = User::factory()->create(['must_change_pin' => false]);

    $this->actingAs($user)
        ->get(route('user-password.edit'))
        ->assertOk();
});

test('password can be updated', function () {
    /** @var User $user */
    $user = User::factory()->create(['must_change_pin' => false]);

    $this->actingAs($user)
        ->from(route('user-password.edit'))
        ->put('/user/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('user-password.edit'));

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('correct password must be provided to update password', function () {
    /** @var User $user */
    $user = User::factory()->create(['must_change_pin' => false]);

    $this->actingAs($user)
        ->from(route('user-password.edit'))
        ->put('/user/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasErrors('current_password')
        ->assertRedirect(route('user-password.edit'));
});
