# Authentication System

Hexagonal Architecture implementation of user authentication with login and registration.

## Architecture Overview

```mermaid
graph TB
    subgraph "Frontend Layer"
        LoginPage[Login.jsx]
        RegisterPage[Register.jsx]
    end

    subgraph "Presentation Layer"
        AuthController[AuthController]
        LoginRequest[LoginRequest]
        RegisterRequest[RegisterRequest]
    end

    subgraph "Application Layer"
        RegisterUseCase[RegisterUserUseCase]
        LoginUseCase[LoginUserUseCase]
        LogoutUseCase[LogoutUserUseCase]
        RegisterInput[RegisterUserInput]
        LoginInput[LoginUserInput]
        UserRegisteredDto[UserRegisteredDto]
        UserAuthenticatedDto[UserAuthenticatedDto]
    end

    subgraph "Domain Layer"
        User[User Entity]
        Email[Email VO]
        UserId[UserId VO]
        Password[Password VOs]
        UserRepo[UserRepositoryInterface]
        PasswordHasher[PasswordHasherInterface]
        Authenticator[AuthenticatorInterface]
    end

    subgraph "Infrastructure Layer"
        EloquentRepo[EloquentUserRepository]
        LaravelHasher[LaravelPasswordHasher]
        LaravelAuth[LaravelAuthenticator]
        EloquentModel[Eloquent User Model]
    end

    LoginPage -->|POST /login| AuthController
    RegisterPage -->|POST /register| AuthController

    AuthController -->|validates| LoginRequest
    AuthController -->|validates| RegisterRequest
    AuthController -->|executes| LoginUseCase
    AuthController -->|executes| RegisterUseCase
    AuthController -->|executes| LogoutUseCase

    RegisterUseCase -->|uses| UserRepo
    RegisterUseCase -->|uses| PasswordHasher
    RegisterUseCase -->|uses| Authenticator
    RegisterUseCase -->|returns| UserRegisteredDto

    LoginUseCase -->|uses| UserRepo
    LoginUseCase -->|uses| PasswordHasher
    LoginUseCase -->|uses| Authenticator
    LoginUseCase -->|returns| UserAuthenticatedDto

    LogoutUseCase -->|uses| Authenticator

    UserRepo -.implements.- EloquentRepo
    PasswordHasher -.implements.- LaravelHasher
    Authenticator -.implements.- LaravelAuth

    EloquentRepo -->|queries| EloquentModel
    LaravelHasher -->|uses| Hash[Hash Facade]
    LaravelAuth -->|uses| Auth[Auth Facade]

    style Domain Layer fill:#e1f5ff
    style Application Layer fill:#fff4e1
    style Infrastructure Layer fill:#ffe1e1
```

## Domain Model

```mermaid
classDiagram
    class User {
        -UserId id
        -string name
        -Email email
        -HashedPassword password
        +id() UserId
        +name() string
        +email() Email
        +password() HashedPassword
        +create() User
    }

    class Email {
        -string value
        +__construct(string)
        +value() string
        +equals(Email) bool
    }

    class UserId {
        -int value
        +__construct(int)
        +value() int
        +equals(UserId) bool
    }

    class HashedPassword {
        -string value
        +__construct(string)
        +value() string
    }

    class PlainPassword {
        -string value
        +__construct(string)
        +value() string
    }

    class UserRepositoryInterface {
        <<interface>>
        +save(User) void
        +findById(UserId) User
        +findByEmail(Email) User
        +emailExists(Email) bool
    }

    class PasswordHasherInterface {
        <<interface>>
        +hash(PlainPassword) HashedPassword
        +verify(PlainPassword, HashedPassword) bool
    }

    class AuthenticatorInterface {
        <<interface>>
        +login(UserId) void
        +logout() void
        +currentUserId() UserId
        +isAuthenticated() bool
    }

    User --> UserId
    User --> Email
    User --> HashedPassword
    UserRepositoryInterface ..> User
    PasswordHasherInterface ..> PlainPassword
    PasswordHasherInterface ..> HashedPassword
    AuthenticatorInterface ..> UserId
```

## Use Case: User Registration

### Flow Diagram

```mermaid
sequenceDiagram
    actor User
    participant UI as Register.jsx
    participant Controller as AuthController
    participant Request as RegisterRequest
    participant UseCase as RegisterUserUseCase
    participant UserRepo as UserRepository
    participant Hasher as PasswordHasher
    participant Auth as Authenticator
    participant DB as Database

    User->>UI: Fill form (name, email, password)
    User->>UI: Submit
    UI->>Controller: POST /register
    Controller->>Request: Validate input

    alt Validation fails
        Request-->>Controller: Validation errors
        Controller-->>UI: Redirect with errors
        UI-->>User: Display errors
    else Validation passes
        Request-->>Controller: Validated data
        Controller->>UseCase: execute(RegisterUserInput)

        UseCase->>UserRepo: emailExists(email)
        UserRepo->>DB: Query users
        DB-->>UserRepo: Result

        alt Email exists
            UserRepo-->>UseCase: true
            UseCase-->>Controller: UserAlreadyExistsException
            Controller-->>UI: Redirect with error
            UI-->>User: "Email already registered"
        else Email available
            UserRepo-->>UseCase: false
            UseCase->>Hasher: hash(PlainPassword)
            Hasher-->>UseCase: HashedPassword

            UseCase->>UseCase: Create User entity
            UseCase->>UserRepo: save(User)
            UserRepo->>DB: Insert user
            DB-->>UserRepo: Success

            UserRepo->>UserRepo: findByEmail(email)
            UserRepo->>DB: Query saved user
            DB-->>UserRepo: User with ID
            UserRepo-->>UseCase: User entity

            UseCase->>Auth: login(UserId)
            Auth-->>UseCase: Success

            UseCase-->>Controller: UserRegisteredDto
            Controller-->>UI: Redirect to home
            UI-->>User: Welcome!
        end
    end
```

### State Diagram

```mermaid
stateDiagram-v2
    [*] --> FormIdle: Load /register
    FormIdle --> Validating: Submit form

    Validating --> FormWithErrors: Validation fails
    Validating --> CheckingEmail: Validation passes

    FormWithErrors --> FormIdle: User corrects

    CheckingEmail --> FormWithErrors: Email exists
    CheckingEmail --> HashingPassword: Email available

    HashingPassword --> CreatingUser: Password hashed
    CreatingUser --> SavingUser: User entity created
    SavingUser --> AuthenticatingUser: User saved
    AuthenticatingUser --> Authenticated: Login successful

    Authenticated --> [*]: Redirect to home
```

## Use Case: User Login

### Flow Diagram

```mermaid
sequenceDiagram
    actor User
    participant UI as Login.jsx
    participant Controller as AuthController
    participant Request as LoginRequest
    participant UseCase as LoginUserUseCase
    participant UserRepo as UserRepository
    participant Hasher as PasswordHasher
    participant Auth as Authenticator
    participant DB as Database

    User->>UI: Enter email & password
    User->>UI: Submit
    UI->>Controller: POST /login
    Controller->>Request: Validate input

    alt Validation fails
        Request-->>Controller: Validation errors
        Controller-->>UI: Redirect with errors
        UI-->>User: Display errors
    else Validation passes
        Request-->>Controller: Validated data
        Controller->>UseCase: execute(LoginUserInput)

        UseCase->>UserRepo: findByEmail(email)
        UserRepo->>DB: Query user

        alt User not found
            DB-->>UserRepo: null
            UserRepo-->>UseCase: null
            UseCase-->>Controller: InvalidCredentialsException
            Controller-->>UI: Redirect with error
            UI-->>User: "Invalid credentials"
        else User found
            DB-->>UserRepo: User record
            UserRepo-->>UseCase: User entity

            UseCase->>Hasher: verify(PlainPassword, HashedPassword)

            alt Password incorrect
                Hasher-->>UseCase: false
                UseCase-->>Controller: InvalidCredentialsException
                Controller-->>UI: Redirect with error
                UI-->>User: "Invalid credentials"
            else Password correct
                Hasher-->>UseCase: true
                UseCase->>Auth: login(UserId)
                Auth-->>UseCase: Success
                UseCase-->>Controller: UserAuthenticatedDto
                Controller-->>UI: Redirect to home
                UI-->>User: Welcome back!
            end
        end
    end
```

### State Diagram

```mermaid
stateDiagram-v2
    [*] --> FormIdle: Load /login
    FormIdle --> Validating: Submit form

    Validating --> FormWithErrors: Validation fails
    Validating --> CheckingCredentials: Validation passes

    FormWithErrors --> FormIdle: User corrects

    CheckingCredentials --> FormWithErrors: User not found
    CheckingCredentials --> VerifyingPassword: User found

    VerifyingPassword --> FormWithErrors: Password incorrect
    VerifyingPassword --> AuthenticatingUser: Password correct

    AuthenticatingUser --> Authenticated: Login successful

    Authenticated --> [*]: Redirect to home
```

## Use Case: User Logout

### Flow Diagram

```mermaid
sequenceDiagram
    actor User
    participant UI as Frontend
    participant Controller as AuthController
    participant UseCase as LogoutUserUseCase
    participant Auth as Authenticator
    participant Session as Session Store

    User->>UI: Click logout
    UI->>Controller: POST /logout
    Controller->>UseCase: execute()
    UseCase->>Auth: logout()
    Auth->>Session: Destroy session
    Session-->>Auth: Success
    Auth-->>UseCase: Success
    UseCase-->>Controller: void
    Controller-->>UI: Redirect to home
    UI-->>User: Logged out
```

## Authentication State Management

```mermaid
stateDiagram-v2
    [*] --> Guest: Application starts

    Guest --> Authenticating: POST /login
    Guest --> Registering: POST /register

    Registering --> Authenticated: Success
    Registering --> Guest: Failure

    Authenticating --> Authenticated: Success
    Authenticating --> Guest: Failure

    Authenticated --> Guest: POST /logout

    Guest --> [*]
    Authenticated --> [*]
```

## Validation Rules

### Registration
- **name**: required, string, max 255 characters
- **email**: required, string, valid email format, max 255 characters, unique in users table
- **password**: required, string, minimum 8 characters

### Login
- **email**: required, string, valid email format
- **password**: required, string

## Error Handling

```mermaid
graph TB
    Error[Error Occurs]

    Error -->|Domain Layer| DomainEx[Domain Exception]
    Error -->|Application Layer| AppEx[Application Exception]
    Error -->|Infrastructure Layer| InfraEx[Framework Exception]

    DomainEx --> InvalidCreds[InvalidCredentialsException]
    DomainEx --> UserExists[UserAlreadyExistsException]

    AppEx --> NotFound[UserNotFoundException]

    InfraEx -->|Translate| DomainEx
    InfraEx -->|Translate| AppEx

    InvalidCreds --> Controller[AuthController]
    UserExists --> Controller
    NotFound --> Controller

    Controller --> ErrorResponse[Redirect with error message]
    ErrorResponse --> UI[Display to user]

    style DomainEx fill:#e1f5ff
    style AppEx fill:#fff4e1
    style InfraEx fill:#ffe1e1
```

## Routes

| Method | Path | Action | Middleware | Description |
|--------|------|--------|------------|-------------|
| GET | `/register` | `showRegisterForm()` | guest | Display registration form |
| POST | `/register` | `register()` | guest | Process registration |
| GET | `/login` | `showLoginForm()` | guest | Display login form |
| POST | `/login` | `login()` | guest | Process login |
| POST | `/logout` | `logout()` | auth | Process logout |

## Dependency Injection Bindings

```mermaid
graph LR
    subgraph "Interfaces (Contracts)"
        UserRepoInt[UserRepositoryInterface]
        PassHashInt[PasswordHasherInterface]
        AuthInt[AuthenticatorInterface]
    end

    subgraph "Implementations (Adapters)"
        EloquentRepo[EloquentUserRepository]
        LaravelHasher[LaravelPasswordHasher]
        LaravelAuth[LaravelAuthenticator]
    end

    subgraph "Service Provider"
        AuthProvider[AuthenticationServiceProvider]
    end

    AuthProvider -.binds.-> UserRepoInt
    AuthProvider -.binds.-> PassHashInt
    AuthProvider -.binds.-> AuthInt

    UserRepoInt -.to.-> EloquentRepo
    PassHashInt -.to.-> LaravelHasher
    AuthInt -.to.-> LaravelAuth

    EloquentRepo -->|uses| Laravel[Laravel Eloquent]
    LaravelHasher -->|uses| Hash[Hash Facade]
    LaravelAuth -->|uses| Auth[Auth Facade]
```

## Component Structure

```mermaid
graph TB
    subgraph "React Components"
        Login[Login.jsx]
        Register[Register.jsx]
    end

    subgraph "shadcn/ui Components"
        Button[Button]
        Input[Input]
        Label[Label]
        Card[Card]
    end

    subgraph "Inertia.js"
        useForm[useForm hook]
        Link[Link component]
    end

    Login -->|uses| Button
    Login -->|uses| Input
    Login -->|uses| Label
    Login -->|uses| Card
    Login -->|uses| useForm
    Login -->|uses| Link

    Register -->|uses| Button
    Register -->|uses| Input
    Register -->|uses| Label
    Register -->|uses| Card
    Register -->|uses| useForm
    Register -->|uses| Link

    useForm -->|POST| Backend[Laravel Backend]

    style Login fill:#61dafb
    style Register fill:#61dafb
```

## Testing Coverage

```mermaid
graph TB
    Tests[Authentication Tests]

    Tests --> RegTests[Registration Tests]
    Tests --> LoginTests[Login Tests]

    RegTests --> RegRender[Page renders]
    RegTests --> RegSuccess[Successful registration]
    RegTests --> RegDuplicate[Duplicate email]
    RegTests --> RegValName[Name validation]
    RegTests --> RegValEmail[Email validation]
    RegTests --> RegValPass[Password validation]

    LoginTests --> LoginRender[Page renders]
    LoginTests --> LoginSuccess[Successful login]
    LoginTests --> LoginWrongPass[Wrong password]
    LoginTests --> LoginNoUser[Non-existent user]
    LoginTests --> LoginValEmail[Email validation]
    LoginTests --> LoginValPass[Password validation]
    LoginTests --> Logout[Logout functionality]

    style Tests fill:#4caf50
    style RegTests fill:#8bc34a
    style LoginTests fill:#8bc34a
```

## Security Considerations

- Passwords are hashed using Laravel's `Hash` facade (bcrypt by default)
- CSRF protection via Laravel middleware
- Input validation via Form Request classes
- Guest/Auth middleware prevents unauthorized access
- Domain exceptions prevent credential enumeration
- Session-based authentication via Laravel's Auth system
