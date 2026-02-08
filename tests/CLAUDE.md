# Architecture Testing Documentation

This document explains the comprehensive architecture tests that enforce Hexagonal Architecture (Ports & Adapters) principles in this Laravel project.

## Overview

The architecture tests in `tests/Unit/ArchTest.php` provide automated validation of 33 architectural rules across 12 categories. These tests ensure strict adherence to hexagonal architecture principles defined in `docs/ai-rules/backend.md`.

## Architecture Test Categories

### 1. Domain Layer Purity (4 rules)

Ensures the Domain layer has zero framework dependencies:

- ✅ No `Illuminate\*` imports in Domain
- ✅ No `Laravel\*` imports in Domain
- ✅ Domain cannot depend on Application layer
- ✅ Domain cannot depend on Infrastructure layer

**Why**: Domain layer must contain pure business logic with no coupling to frameworks or outer layers.

### 2. Layer Dependency Direction (2 rules)

Enforces correct dependency flow: Infrastructure → Application → Domain

- ✅ Application layer cannot depend on Infrastructure layer
- ✅ Application layer can only use Domain and standard library

**Why**: Dependencies must point inward. Outer layers depend on inner layers, never the reverse.

### 3. Naming Conventions (5 rules)

Validates consistent naming patterns:

- ✅ Domain ports: `*Interface` suffix and must be interfaces
- ✅ Domain entities: no `Interface` suffix
- ✅ Domain exceptions: `*Exception` suffix
- ✅ Application use cases: `*UseCase` suffix
- ✅ DTOs: documented pattern (both `*Dto` and `*Input` acceptable)

**Why**: Consistent naming makes the codebase self-documenting and easier to navigate.

### 4. Repository Patterns (2 rules)

Validates repository structure:

- ✅ Repository interfaces are in `Domain/{Context}/Ports`
- ✅ Repository implementations are in `Infrastructure/{Context}/Repositories`

**Why**: Repositories follow dependency inversion - interfaces defined in Domain, implementations in Infrastructure.

### 5. Use Case Patterns (2 rules)

Ensures use cases follow immutable patterns:

- ✅ Use cases are `final` (cannot be extended)
- ✅ Use cases are `readonly` (immutable properties)

**Why**: Use cases are application orchestrators that should not be extended or modified.

### 6. DTO Patterns (1 rule)

Validates data transfer objects:

- ✅ DTOs are `readonly` (immutable)

**Why**: DTOs are data containers that cross layer boundaries and must be immutable.

### 7. Value Object Patterns (1 rule)

Ensures value objects are immutable:

- ✅ Value objects are `readonly`

**Why**: Value objects represent domain concepts and must be immutable to maintain consistency.

### 8. Entity Patterns (1 rule)

Validates rich domain entities:

- ✅ Entities are `readonly`

**Why**: Entities use immutable state with behavior methods that return new instances.

### 9. Infrastructure Patterns (2 rules)

Controls framework usage:

- ✅ Infrastructure implementations are `final`
- ✅ Infrastructure CAN use `Illuminate\*` framework libraries

**Why**: Infrastructure adapts external dependencies. Implementations are final; framework usage is allowed here.

### 10. Port/Adapter Validation (2 rules)

Enforces interface segregation:

- ✅ Ports (interfaces) are defined in Domain
- ✅ Repository adapters implement corresponding repository interfaces

**Why**: Ports define contracts in the Domain; adapters implement them in Infrastructure.

### 11. Controller Delegation (1 rule)

Preserves single-action controller pattern:

- ✅ Controllers should not be used directly (enforces invokable controllers)

**Why**: Controllers delegate to use cases, never contain business logic.

## What We DON'T Test (Manual Review Required)

Some patterns cannot be validated with Pest Arch due to API limitations:

1. **DTO suffix variations** - Both `*Dto` and `*Input` suffixes are acceptable
2. **Repository prefixes** - `Eloquent*`, `Laravel*`, `Database*` prefixes all acceptable
3. **Exception hierarchy** - Abstract `DomainException` base class pattern
4. **Return types** - Repositories must return entities (not arrays) - requires PHPStan/Psalm

These patterns are documented in `docs/ai-rules/backend.md` and enforced via code review.

## Critical Discovery: Final Classes & Mocking

### The Problem

Making ALL concrete classes `final` prevents mocking in unit tests. This blocks:
- Mocking domain ports (e.g., `UserRepositoryInterface`) in use case tests
- Creating test doubles for isolated testing
- Using Mockery/PHPUnit mocks without reflection hacks

### The Solution

Only apply `final` keyword to:
1. **Infrastructure implementations** (adapters like `EloquentUserRepository`)
2. **Application use cases** (like `RegisterUserUseCase`)
3. **Value objects and entities** (already immutable via `readonly`)

**NEVER apply `final` to**:
- Interfaces (can't be final by definition)
- Abstract classes (defeats their purpose)
- Any class that needs to be mocked in tests

This allows proper unit testing with mocks while maintaining architectural integrity.

## Common Mistakes to Avoid

### 1. Namespace Errors

The most common error is incorrect namespace layer ordering:

❌ **WRONG**:
```php
namespace Src\Authentication\Infrastructure;  // Wrong order
```

✅ **CORRECT**:
```php
namespace Src\Infrastructure\Authentication;  // Layer first, then context
```

❌ **WRONG** (missing subfolder):
```php
// File: src/Infrastructure/Authentication/Repositories/EloquentUserRepository.php
namespace Src\Infrastructure\Authentication;  // Missing Repositories
```

✅ **CORRECT**:
```php
// File: src/Infrastructure/Authentication/Repositories/EloquentUserRepository.php
namespace Src\Infrastructure\Authentication\Repositories;  // Include all folders
```

**Fix**: Always run `composer dump-autoload` to catch PSR-4 violations:
```bash
composer dump-autoload  # Shows namespace mismatches
```

### 2. Framework Dependencies in Domain

❌ **WRONG**:
```php
// In src/Domain/Authentication/Entities/User.php
use Illuminate\Support\Facades\Hash;  // Framework dependency!
```

✅ **CORRECT**:
```php
// In src/Domain/Authentication/Entities/User.php
use Src\Domain\Authentication\ValueObjects\HashedPassword;  // Domain concept
```

**Fix**: The architecture tests will fail with "domain layer should not depend on illuminate"

### 3. Application Depending on Infrastructure

❌ **WRONG**:
```php
// In src/Application/Authentication/UseCases/RegisterUserUseCase.php
use Src\Infrastructure\Authentication\Repositories\EloquentUserRepository;  // Concrete class!
```

✅ **CORRECT**:
```php
// In src/Application/Authentication/UseCases/RegisterUserUseCase.php
use Src\Domain\Authentication\Ports\UserRepositoryInterface;  // Interface only
```

**Fix**: The architecture tests will fail with "application layer should not depend on infrastructure layer"

## Pest Arch API Patterns

### What Works ✅

```php
// Dependency checks
->expect('Src\Domain')->not->toUse('Illuminate')

// Type validation
->toBeInterfaces()
->toBeClasses()

// Final/Readonly checks
->toBeFinal()
->toBeReadonly()

// Naming patterns
->toHaveSuffix('Interface')
->toHavePrefix('Eloquent')

// Inheritance/Implementation
->toExtend('BaseClass')
->toImplement('InterfaceClass')
```

### What Doesn't Work ❌

```php
// Regex matching on namespace patterns (matches namespace string, not classes)
->expect('Src\Application\*\DTOs')->toMatch('/regex/')  // ❌ Doesn't work

// OR operator for multiple conditions
->toHaveSuffix('Dto')->or()->toHaveSuffix('Input')  // ❌ No or() method

// Filter callbacks
->that(fn($class) => str_contains($class->name, 'Foo'))  // ❌ No that() method

// Exclusion methods
->ignoring('BaseClass')  // ❌ No ignoring() method
->not->toBe('BaseClass')  // ❌ Doesn't work for exclusion
```

### Workarounds

1. **Multiple acceptable suffixes/prefixes**: Document in comments, validate in code review
2. **Complex filtering**: Use specific class names instead of wildcards
3. **Excluding base classes**: Skip the test and document the pattern
4. **Wildcard patterns**: Use concrete interface names per bounded context

## Testing Strategy

### Unit Tests (Domain & Application)

```php
// Mock domain ports (interfaces) using Mockery
$mockRepository = Mockery::mock(UserRepositoryInterface::class);
$mockRepository->shouldReceive('findByEmail')->andReturn($user);

// Test use cases with mocked dependencies
$useCase = new RegisterUserUseCase($mockRepository, $mockHasher);
```

**Key Points**:
- Always mock interfaces (ports), never concrete classes
- Test business logic in domain entities
- Test orchestration in application use cases
- Never mock final classes

### Architecture Tests (Pest Arch)

```php
// Run architecture tests only
php artisan test --filter ArchTest

// Run full test suite
php artisan test
```

**Key Points**:
- Fast feedback on architectural violations
- Runs automatically in CI/CD
- Prevents merging non-compliant code
- Serves as executable documentation

### Integration Tests

```php
// Test infrastructure adapters with real dependencies
test('EloquentUserRepository saves user to database', function () {
    $repository = new EloquentUserRepository();
    $user = new User(...);

    $repository->save($user);

    expect($repository->findByEmail($user->email()))->toEqual($user);
});
```

**Key Points**:
- Test infrastructure with actual framework/database
- Validate repository implementations
- Test external API integrations
- Use database transactions for cleanup

## Running the Tests

### Run All Architecture Tests

```bash
php artisan test --filter ArchTest
```

Expected output: All tests passing (33 assertions)

### Run Full Test Suite

```bash
php artisan test
```

Expected output: All architecture, unit, and feature tests passing

### Verify Specific Rules

```bash
# Check domain purity
php artisan test --filter "domain layer should not depend on illuminate"

# Check dependency direction
php artisan test --filter "application layer should not depend on infrastructure"

# Check naming conventions
php artisan test --filter "use cases should be final"
```

## Continuous Integration

Architecture tests should be part of your CI/CD pipeline:

```yaml
# .github/workflows/tests.yml
- name: Run Architecture Tests
  run: php artisan test --filter ArchTest

- name: Run Full Test Suite
  run: php artisan test
```

This ensures:
- Every commit is validated against architectural rules
- Pull requests cannot be merged with violations
- Team alignment on architecture patterns
- Refactoring doesn't break architecture

## Extensibility

The architecture tests are designed to be extensible:

### Adding New Bounded Contexts

When you add a new bounded context (e.g., `Orders`, `Payments`), the tests automatically apply:

```bash
# Create new context structure
src/
├── Domain/Orders/
├── Application/Orders/
└── Infrastructure/Orders/

# Tests automatically validate the new context
php artisan test --filter ArchTest
```

All wildcard rules (`Src\Domain\*`, `Src\Application\*`) apply to new contexts without modification.

### Adding New Rules

To add new architectural rules:

1. Add a new `arch()` test in `tests/Unit/ArchTest.php`
2. Follow the existing pattern
3. Document the rule in this file
4. Run tests to verify

Example:
```php
arch('new rule description')
    ->expect('Src\SomeNamespace')
    ->toFollowSomePattern();
```

## References

- **Backend Rules**: `docs/ai-rules/backend.md` - Complete hexagonal architecture guide
- **Frontend Rules**: `docs/ai-rules/frontend.md` - Frontend patterns (Inertia, React)
- **Clean Code**: `docs/ai-rules/clean-code.md` - Clean code principles
- **Architecture Tests**: `tests/Unit/ArchTest.php` - Test implementation
- **Project Instructions**: `CLAUDE.md` - Project-wide AI agent instructions

## Troubleshooting

### PSR-4 Autoloading Errors

```bash
composer dump-autoload
```

Look for messages like:
```
Class Src\Authentication\Infrastructure\EloquentUserRepository located in
./src/Infrastructure/Authentication/Repositories/EloquentUserRepository.php
does not comply with psr-4 autoloading standard
```

**Fix**: Update the namespace to match the directory structure.

### Tests Failing After Refactor

1. Run `composer dump-autoload` first
2. Check namespace declarations match directory structure
3. Verify imports use correct namespaces
4. Run specific failing test with verbose output:
   ```bash
   php artisan test --filter "test name" -v
   ```

### Mocking Issues

If you can't mock a class:
1. Verify you're mocking an interface, not a concrete class
2. Check the class isn't marked as `final`
3. Ensure you're using `Mockery::mock(InterfaceClass::class)`

## Summary

These architecture tests provide:
- ✅ Automated enforcement of hexagonal architecture
- ✅ Fast feedback on violations
- ✅ Executable documentation
- ✅ CI/CD integration
- ✅ Extensibility for new bounded contexts
- ✅ Prevention of common mistakes
- ✅ Proper testing enablement (no final class conflicts)

By following these patterns, you ensure the codebase maintains clean architecture principles as it grows.
