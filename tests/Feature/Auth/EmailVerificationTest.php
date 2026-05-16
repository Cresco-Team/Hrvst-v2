<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

test('verified user is redirected away from verification notice', function () {
    $user = User::factory()->create(['must_change_pin' => false]);

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertRedirect();
});

test('email can be verified when user has an email address', function () {
    $user = User::factory()->create(['must_change_pin' => false]);

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email ?? '')]
    );

    $this->actingAs($user)->get($verificationUrl);

    Event::assertNotDispatched(Verified::class);
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->create(['must_change_pin' => false]);

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $this->actingAs($user)->get($verificationUrl);

    Event::assertNotDispatched(Verified::class);
});

test('email is not verified with invalid user id', function () {
    $user = User::factory()->create(['must_change_pin' => false]);

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => 999999, 'hash' => sha1($user->email ?? '')]
    );

    $this->actingAs($user)->get($verificationUrl);

    Event::assertNotDispatched(Verified::class);
});

test('verified user visiting verification link is redirected', function () {
    $user = User::factory()->create(['must_change_pin' => false]);

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email ?? '')]
    );

    $this->actingAs($user)->get($verificationUrl)->assertRedirect();

    Event::assertNotDispatched(Verified::class);
});
