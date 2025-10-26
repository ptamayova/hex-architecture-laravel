<?php

declare(strict_types=1);

use App\Models\User;

test('login page can be rendered', function (): void {
    $response = $this->get('/login');

    $response->assertSuccessful();
});

test('users can login with valid credentials', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect('/');
    $this->assertAuthenticated();
    $this->assertTrue(auth()->user()->is($user));
});

test('users cannot login with invalid password', function (): void {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('users cannot login with non-existent email', function (): void {
    $response = $this->post('/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('login requires email', function (): void {
    $response = $this->post('/login', [
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
});

test('login requires password', function (): void {
    $response = $this->post('/login', [
        'email' => 'test@example.com',
    ]);

    $response->assertSessionHasErrors('password');
});

test('login requires valid email format', function (): void {
    $response = $this->post('/login', [
        'email' => 'invalid-email',
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
});

test('authenticated users can logout', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->post('/logout');

    $response->assertRedirect('/');
    $this->assertGuest();
});
