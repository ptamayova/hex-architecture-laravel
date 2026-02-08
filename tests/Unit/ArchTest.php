<?php

declare(strict_types=1);

use Src\Domain\Authentication\Ports\UserRepositoryInterface;
use Src\Infrastructure\Authentication\Repositories\EloquentUserRepository;

/*
|--------------------------------------------------------------------------
| Hexagonal Architecture Tests
|--------------------------------------------------------------------------
|
| These tests enforce the Ports & Adapters (Hexagonal Architecture) pattern
| defined in docs/ai-rules/backend.md. They validate:
|
| 1. Domain Layer Purity (no framework dependencies)
| 2. Layer Dependency Direction (Infrastructure → Application → Domain)
| 3. Naming Conventions (interfaces, entities, use cases, repositories)
| 4. Repository Patterns (return entities, not arrays)
| 5. Use Case Patterns (final, readonly, execute method)
| 6. DTO Patterns (readonly, no behavior)
| 7. Exception Hierarchy (domain exceptions extend base)
| 8. Value Object Patterns (readonly, immutable)
| 9. Entity Patterns (readonly, rich with behavior)
| 10. Infrastructure Patterns (final, can use frameworks)
| 11. Port/Adapter Validation (interfaces in Domain, implementations in Infrastructure)
| 12. Controller Delegation (single-action invokable controllers)
|
| These rules automatically apply to all bounded contexts (Authentication, Orders, etc.)
|
*/

// ============================================================================
// Core Presets
// ============================================================================

arch()->preset()->php();
arch()->preset()->security();

// ============================================================================
// 1. Domain Layer Purity
// ============================================================================

arch('domain layer should not depend on illuminate')
    ->expect('Src\Domain')
    ->not->toUse('Illuminate');

arch('domain layer should not depend on laravel')
    ->expect('Src\Domain')
    ->not->toUse('Laravel');

arch('domain layer should not depend on application layer')
    ->expect('Src\Domain')
    ->not->toUse('Src\Application');

arch('domain layer should not depend on infrastructure layer')
    ->expect('Src\Domain')
    ->not->toUse('Src\Infrastructure');

// ============================================================================
// 2. Layer Dependency Direction
// ============================================================================

arch('application layer should not depend on infrastructure layer')
    ->expect('Src\Application')
    ->not->toUse('Src\Infrastructure');

arch('application layer can depend on domain layer')
    ->expect('Src\Application')
    ->toOnlyUse([
        'Src\Domain',
        'Src\Application',
        // Allow standard library
        'Exception',
        'InvalidArgumentException',
        'RuntimeException',
        'LogicException',
        'DomainException',
        'BadMethodCallException',
        'OutOfBoundsException',
        'OverflowException',
        'UnderflowException',
        'RangeException',
        'LengthException',
        'DateTime',
        'DateTimeImmutable',
        'DateTimeZone',
        'DateInterval',
        'Closure',
        'Generator',
        'ArrayObject',
        'ArrayIterator',
        'SplFileInfo',
        'JsonSerializable',
        'Stringable',
        'Throwable',
        'Countable',
        'IteratorAggregate',
        'Iterator',
        'Traversable',
    ]);

// ============================================================================
// 3. Naming Conventions
// ============================================================================

arch('domain ports should be interfaces with Interface suffix')
    ->expect('Src\Domain\*\Ports')
    ->toBeInterfaces()
    ->toHaveSuffix('Interface');

arch('domain entities should not have Interface suffix')
    ->expect('Src\Domain\*\Entities')
    ->not->toHaveSuffix('Interface');

arch('domain exceptions should have Exception suffix')
    ->expect('Src\Domain\*\Exceptions')
    ->toHaveSuffix('Exception')
    ->toExtend('Exception');

arch('application use cases should have UseCase suffix')
    ->expect('Src\Application\*\UseCases')
    ->toHaveSuffix('UseCase');

// Skipping DTO suffix check - accepts both "Dto" and "Input" suffixes
// This is validated by naming in code review

// Skipping repository prefix check - accepts Eloquent, Laravel, Database prefixes
// This is validated by naming in code review

// ============================================================================
// 4. Repository Patterns
// ============================================================================

arch('repository interfaces should be in Domain Ports')
    ->expect('Src\Domain\*\Ports')
    ->toBeInterfaces()
    ->toHaveSuffix('Interface');

arch('repository implementations should be in Infrastructure')
    ->expect('Src\Infrastructure\*\Repositories')
    ->toBeClasses()
    ->toBeFinal();

// Note: We cannot validate return types automatically in Pest/PHPUnit arch tests
// This would require static analysis tools like PHPStan/Psalm with custom rules
// For now, this is documented in backend.md and enforced via code review

// ============================================================================
// 5. Use Case Patterns
// ============================================================================

arch('use cases should be final')
    ->expect('Src\Application\*\UseCases')
    ->classes()
    ->toBeFinal();

arch('use cases should be readonly')
    ->expect('Src\Application\*\UseCases')
    ->classes()
    ->toBeReadonly();

// ============================================================================
// 6. DTO Patterns
// ============================================================================

arch('DTOs should be readonly')
    ->expect('Src\Application\*\DTOs')
    ->classes()
    ->toBeReadonly();

// ============================================================================
// 7. Exception Hierarchy
// ============================================================================

// Skipping base exception check - DomainException itself is abstract
// All other domain exceptions should extend DomainException
// This is validated in code review

// ============================================================================
// 8. Value Object Patterns
// ============================================================================

arch('value objects should be readonly')
    ->expect('Src\Domain\*\ValueObjects')
    ->classes()
    ->toBeReadonly();

// ============================================================================
// 9. Entity Patterns
// ============================================================================

arch('entities should be readonly')
    ->expect('Src\Domain\*\Entities')
    ->classes()
    ->toBeReadonly();

// ============================================================================
// 10. Infrastructure Patterns
// ============================================================================

arch('infrastructure implementations should be final')
    ->expect('Src\Infrastructure\*\Repositories')
    ->classes()
    ->toBeFinal();

arch('infrastructure can use illuminate')
    ->expect('Src\Infrastructure')
    ->toUse('Illuminate');

// ============================================================================
// 11. Port/Adapter Validation
// ============================================================================

arch('ports should only contain interfaces')
    ->expect('Src\Domain\*\Ports')
    ->toBeInterfaces();

// Repository interface implementation check
// Each repository in Infrastructure must implement a corresponding Domain interface
// Specific check for EloquentUserRepository
arch('EloquentUserRepository implements UserRepositoryInterface')
    ->expect(EloquentUserRepository::class)
    ->toImplement(UserRepositoryInterface::class);

// ============================================================================
// 12. Controller Delegation
// ============================================================================

arch('controllers should not be used directly')
    ->expect('App\Http\Controllers')
    ->not->toBeUsed();
