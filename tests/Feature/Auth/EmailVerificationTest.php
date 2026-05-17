<?php

test('email verification routes are not registered', function () {
    $this->get('/email/verify')->assertRedirect();
    $this->post('/email/verification-notification', [])
        ->assertRedirect(route('login'));
})->skip('Email verification not implemented — see file header for setup instructions');
