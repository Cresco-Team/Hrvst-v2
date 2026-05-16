<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;

test('verification send route redirects authenticated users', function () {
    Notification::fake();

    $user = User::factory()->create(['must_change_pin' => false]);

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect();

    Notification::assertNothingSent();
});

test('verification send route requires authentication', function () {
    $this->post(route('verification.send'))
        ->assertRedirect(route('login'));
});
