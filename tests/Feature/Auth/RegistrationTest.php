<?php

test('public registration screen is disabled for managed POS accounts', function () {
    $this->get('/register')->assertNotFound();
});

test('public users cannot self-register accounts', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'username' => 'testuser',
        'password' => 'MyStrongPassword123',
        'password_confirmation' => 'MyStrongPassword123',
    ])->assertNotFound();

    $this->assertGuest();
});
