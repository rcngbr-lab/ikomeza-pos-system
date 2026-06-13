<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('password can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/dashboard')
        ->put('/password', [
            'current_password' => 'password',
            'password' => 'MyStrongPassword123',
            'password_confirmation' => 'MyStrongPassword123',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/dashboard');

    $this->assertTrue(Hash::check('MyStrongPassword123', $user->refresh()->password));
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/dashboard')
        ->put('/password', [
            'current_password' => 'wrong-password',
            'password' => 'MyStrongPassword123',
            'password_confirmation' => 'MyStrongPassword123',
        ]);

    $response
        ->assertSessionHasErrorsIn('updatePassword', 'current_password')
        ->assertRedirect('/dashboard');
});
