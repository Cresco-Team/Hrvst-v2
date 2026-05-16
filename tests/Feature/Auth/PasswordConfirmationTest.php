<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('confirm password screen can be rendered', function () {
    /** @var \App\Models\User $user */
    $user = User::factory()->create(['must_change_pin' => false]);

    $this->actingAs($user)
        ->get('/user/confirm-password')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/ConfirmPassword'));
});

test('password confirmation requires authentication', function () {
    $this->get('/user/confirm-password')
        ->assertRedirect(route('login'));
});
