<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Authentication\Application\UseCases\LoginUserUseCase;
use Src\Authentication\Application\UseCases\LogoutUserUseCase;
use Src\Authentication\Application\UseCases\RegisterUserUseCase;
use Src\Authentication\Domain\Ports\AuthenticatorInterface;
use Src\Authentication\Domain\Ports\PasswordHasherInterface;
use Src\Authentication\Domain\Ports\UserRepositoryInterface;
use Src\Authentication\Infrastructure\EloquentUserRepository;
use Src\Authentication\Infrastructure\LaravelAuthenticator;
use Src\Authentication\Infrastructure\LaravelPasswordHasher;

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
