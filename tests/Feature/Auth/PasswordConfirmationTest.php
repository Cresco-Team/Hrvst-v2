<?php

use App\Http\Middleware\EnsurePinChanged;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('public');
});

test('confirm password screen can be rendered', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withoutMiddleware(EnsurePinChanged::class)
        ->get('/user/confirm-password')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/ConfirmPassword'));
});

test('password confirmation requires authentication', function () {
    $this->get('/user/confirm-password')
        ->assertRedirect(route('login'));
});
