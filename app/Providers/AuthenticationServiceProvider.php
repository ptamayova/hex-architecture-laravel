<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Application\Authentication\UseCases\LoginUserUseCase;
use Src\Application\Authentication\UseCases\LogoutUserUseCase;
use Src\Application\Authentication\UseCases\RegisterUserUseCase;
use Src\Domain\Authentication\Ports\AuthenticatorInterface;
use Src\Domain\Authentication\Ports\PasswordHasherInterface;
use Src\Domain\Authentication\Ports\UserRepositoryInterface;
use Src\Infrastructure\Authentication\EloquentUserRepository;
use Src\Infrastructure\Authentication\LaravelAuthenticator;
use Src\Infrastructure\Authentication\LaravelPasswordHasher;

final class AuthenticationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind interfaces to implementations
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(PasswordHasherInterface::class, LaravelPasswordHasher::class);
        $this->app->bind(AuthenticatorInterface::class, LaravelAuthenticator::class);

        // Register use cases as singletons
        $this->app->singleton(RegisterUserUseCase::class);
        $this->app->singleton(LoginUserUseCase::class);
        $this->app->singleton(LogoutUserUseCase::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
