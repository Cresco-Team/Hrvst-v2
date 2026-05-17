<?php

use App\Http\Middleware\EnsurePinChanged;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('confirm password screen can be rendered', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $this->withoutMiddleware(EnsurePinChanged::class)
        ->actingAs($user)
        ->get('/user/confirm-password')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/ConfirmPassword'));
});

test('password confirmation requires authentication', function () {
    $this->get('/user/confirm-password')
        ->assertRedirect(route('login'));
});
