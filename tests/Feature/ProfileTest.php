<?php

use App\Models\User;

test('self-service profile page is not exposed to POS staff', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/profile')
        ->assertNotFound();
});

test('staff cannot delete their own managed POS account from a profile route', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ])
        ->assertNotFound();

    $this->assertNotNull($user->fresh());
});
