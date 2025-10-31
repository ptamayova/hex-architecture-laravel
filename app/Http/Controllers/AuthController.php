<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use InvalidArgumentException;
use Src\Authentication\Application\DTOs\LoginUserInput;
use Src\Authentication\Application\DTOs\RegisterUserInput;
use Src\Authentication\Application\UseCases\LoginUserUseCase;
use Src\Authentication\Application\UseCases\LogoutUserUseCase;
use Src\Authentication\Application\UseCases\RegisterUserUseCase;
use Src\Authentication\Domain\Exceptions\InvalidCredentialsException;
use Src\Authentication\Domain\Exceptions\UserAlreadyExistsException;
use Symfony\Component\HttpFoundation\RedirectResponse;

final readonly class AuthController
{
    public function __construct(
        private RegisterUserUseCase $registerUserUseCase,
        private LoginUserUseCase $loginUserUseCase,
        private LogoutUserUseCase $logoutUserUseCase,
    ) {}

    public function showRegisterForm(): InertiaResponse
    {
        return Inertia::render('Auth/Register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $input = new RegisterUserInput(
                name: (string) $validated['name'],
                email: (string) $validated['email'],
                password: (string) $validated['password'],
            );

            $this->registerUserUseCase->execute($input);

            return redirect()->route('home');
        } catch (UserAlreadyExistsException $e) {
            return back()->withErrors(['email' => $e->getMessage()]);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function showLoginForm(): InertiaResponse
    {
        return Inertia::render('Auth/Login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $input = new LoginUserInput(
                email: (string) $validated['email'],
                password: (string) $validated['password'],
            );

            $this->loginUserUseCase->execute($input);

            return redirect()->route('home');
        } catch (InvalidCredentialsException $e) {
            return back()->withErrors(['email' => $e->getMessage()]);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function logout(): RedirectResponse
    {
        $this->logoutUserUseCase->execute();

        return redirect()->route('home');
    }
}
