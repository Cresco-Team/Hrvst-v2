<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users are redirected from dashboard based on role', function () {
    $user = User::factory()->create(['must_change_pin' => false]);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('categories'));
});
