<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;

test('registration page can be rendered', function (): void {
    $response = $this->get('/register');

    $response->assertSuccessful();
});

test('users can register with valid data', function (): void {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect('/');

    assertDatabaseHas('users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $this->assertAuthenticated();
});

test('users cannot register with existing email', function (): void {
    User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
});

test('registration requires name', function (): void {
    $response = $this->post('/register', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors('name');
});

test('registration requires email', function (): void {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
});

test('registration requires valid email format', function (): void {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'invalid-email',
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
});

test('registration requires password', function (): void {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $response->assertSessionHasErrors('password');
});

test('registration requires password with minimum length', function (): void {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'short',
    ]);

    $response->assertSessionHasErrors('password');
});
