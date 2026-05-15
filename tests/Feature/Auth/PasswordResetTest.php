<?php

test('forgot password screen returns 404', function () {
    $this->get('/forgot-password')->assertNotFound();
});

test('forgot password POST returns 404', function () {
    $this->post('/forgot-password', [])->assertNotFound();
});

test('reset password screen returns 404', function () {
    $this->get('/reset-password/fake-token')->assertNotFound();
});

test('reset password POST returns 404', function () {
    $this->post('/reset-password', [])->assertNotFound();
});
