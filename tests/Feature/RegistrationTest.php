<?php

declare(strict_types=1);

use App\Models\User;
use Src\Authentication\Domain\Ports\PasswordHasherInterface;
use Src\Authentication\Domain\Ports\UserRepositoryInterface;
use Src\Authentication\Domain\ValueObjects\PlainPassword;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\mock;

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

test('register handles UserAlreadyExistsException from use case', function (): void {
    $repositoryMock = mock(UserRepositoryInterface::class);
    $repositoryMock->shouldReceive('emailExists')
        ->once()
        ->andReturn(true);

    $this->app->instance(UserRepositoryInterface::class, $repositoryMock);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
    $response->assertSessionHas('errors', function ($errors): bool {
        return str_contains($errors->first('email'), 'already exists');
    });
});

test('register handles InvalidArgumentException from use case', function (): void {
    $repositoryMock = mock(UserRepositoryInterface::class);
    $repositoryMock->shouldReceive('emailExists')
        ->once()
        ->andReturn(false);

    $passwordHasherMock = mock(PasswordHasherInterface::class);
    $passwordHasherMock->shouldReceive('hash')
        ->once()
        ->with(Mockery::type(PlainPassword::class))
        ->andThrow(new InvalidArgumentException('Invalid input provided'));

    $this->app->instance(UserRepositoryInterface::class, $repositoryMock);
    $this->app->instance(PasswordHasherInterface::class, $passwordHasherMock);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors('error');
    $response->assertSessionHas('errors', function ($errors): bool {
        return $errors->first('error') === 'Invalid input provided';
    });
});
