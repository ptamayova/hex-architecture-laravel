<?php

declare(strict_types=1);

use App\Models\User;
use Src\Domain\Authentication\Ports\AuthenticatorInterface;
use Src\Domain\Authentication\Ports\PasswordHasherInterface;
use Src\Domain\Authentication\Ports\UserRepositoryInterface;
use Src\Domain\Authentication\ValueObjects\Email;
use Src\Domain\Authentication\ValueObjects\HashedPassword;
use Src\Domain\Authentication\ValueObjects\UserId;

use function Pest\Laravel\mock;

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

test('login handles InvalidArgumentException from use case', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $repositoryMock = mock(UserRepositoryInterface::class);
    $repositoryMock->shouldReceive('findByEmail')
        ->once()
        ->andReturn(Src\Domain\Authentication\Entities\User::create(
            new UserId(1),
            $user->name,
            new Email($user->email),
            new HashedPassword($user->password)
        ));

    $passwordHasherMock = mock(PasswordHasherInterface::class);
    $passwordHasherMock->shouldReceive('verify')
        ->once()
        ->andReturn(true);

    $authenticatorMock = mock(AuthenticatorInterface::class);
    $authenticatorMock->shouldReceive('login')
        ->once()
        ->with(Mockery::type(UserId::class))
        ->andThrow(new InvalidArgumentException('Invalid input provided'));

    $this->app->instance(UserRepositoryInterface::class, $repositoryMock);
    $this->app->instance(PasswordHasherInterface::class, $passwordHasherMock);
    $this->app->instance(AuthenticatorInterface::class, $authenticatorMock);

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors('error');
    $response->assertSessionHas('errors', fn ($errors): bool => $errors->first('error') === 'Invalid input provided');
});
