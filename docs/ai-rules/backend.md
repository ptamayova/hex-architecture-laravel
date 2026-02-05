# Hexagonal Architecture Rules for AI Agents

This document contains strict architectural rules for implementing Hexagonal Architecture (Ports & Adapters pattern).

---

## Directory Structure

All business logic lives in bounded contexts under `/src`:

```
src/{BoundedContext}/
├── Domain/         # Pure business logic (entities, interfaces, enums)
├── Application/    # Use cases, DTOs, exceptions
└── Infrastructure/ # Framework implementations (repos, APIs, persistence)
```

**Rule**: Never put business logic outside `/src` namespace.

---

## Domain Layer Rules

**Location**: `src/{Context}/Domain/`

### What Goes Here

- **Rich entities** with behavior methods (NOT anemic data containers)
- Value objects (immutable data with no identity)
- Domain services (business logic that doesn't fit in entities)
- Port interfaces (contracts for external dependencies)
- Domain events
- Business rule validation
- Enums and domain-specific types

### Mandatory Rules

✅ **ALWAYS**:
- Use only standard library (no external dependencies)
- Make entities immutable/readonly when possible
- Define interfaces for all external dependencies (ports)
- **Keep entities RICH with behavior** - business logic lives in entity methods
- Put all business rules and invariants in domain layer
- Use strongly-typed value objects instead of primitives
- Encapsulate business operations in entity methods (e.g., `order.cancel()`, `user.activate()`)

❌ **NEVER**:
- Create anemic domain models (entities with only getters/setters and no behavior)
- Import framework libraries
- Import infrastructure classes
- Add UI concerns (formatting, colors, display logic)
- Add persistence concerns (SQL, database logic)
- Depend on Application or Infrastructure layers
- Add methods like `toArray()`, `toJson()`, `toForm()` for presentation
- Use primitive types where value objects should be used (e.g., use `Email` not `string`)

### Code Patterns

```php
// ✅ CORRECT: Rich entity with behavior and business logic
final readonly class Order {
    public function __construct(
        private OrderId $id,
        private Money $total,
        private OrderStatus $status,
        private array $items,
    ) {
        // Invariants enforced in constructor
        if (count($items) === 0) {
            throw new EmptyOrderException();
        }
    }

    // Business behavior as methods
    public function cancel(): self {
        if ($this->status->isFinal()) {
            throw new OrderCannotBeCancelledException($this->id);
        }

        return new self(
            $this->id,
            $this->total,
            OrderStatus::CANCELLED,
            $this->items
        );
    }

    public function addItem(OrderItem $item): self {
        return new self(
            $this->id,
            $this->total->add($item->price()),
            $this->status,
            [...$this->items, $item]
        );
    }

    public function isEligibleForDiscount(): bool {
        return $this->total->isGreaterThan(Money::fromAmount(100));
    }

    // Getters for accessing state (not for business logic)
    public function id(): OrderId { return $this->id; }
    public function total(): Money { return $this->total; }
    public function status(): OrderStatus { return $this->status; }
}

// ✅ CORRECT: Strongly-typed repository interface returning domain entities
interface OrderRepository {
    public function getById(OrderId $id): Order;
    public function save(Order $order): void;
    public function findByCustomer(CustomerId $customerId): array; // array<Order>
}

// ❌ WRONG: Anemic domain model (data container without behavior)
class Order {
    public function __construct(
        private string $id,
        private float $total,
        private string $status,
    ) {}

    public function getId(): string { return $this->id; }
    public function getTotal(): float { return $this->total; }
    public function setStatus(string $status): void { $this->status = $status; } // NO!
}

// ❌ WRONG: Repository returning arrays instead of entities
interface OrderRepository {
    public function getById(string $id): array; // NO! Return Order entity
}

// ❌ WRONG: Framework dependency in domain
use Illuminate\Database\Eloquent\Model;
class Order extends Model { } // NO!

// ❌ WRONG: Presentation logic in domain
class Order {
    public function toArray(): array { } // NO!
    public function getColorForStatus(): string { } // NO!
}

// ❌ WRONG: Using primitives instead of value objects
class Order {
    public function __construct(
        private string $id,        // NO! Use OrderId
        private float $total,      // NO! Use Money
        private string $status,    // NO! Use OrderStatus enum
    ) {}
}
```

---

## ⚠️ Anti-Pattern: Data-Oriented Programming

**AVOID THIS PATTERN** - This section describes what NOT to do.

### What is Data-Oriented Programming (Anti-Pattern)?

A data-oriented approach treats domain entities as passive data containers (arrays, DTOs, or anemic objects) and puts business logic in use cases or services. This violates object-oriented principles and hexagonal architecture.

### Examples of Data-Oriented Anti-Pattern

```php
// ❌ WRONG: Repository returning arrays
interface OrderRepository {
    public function getById(string $id): array;  // Returns ['id' => ..., 'status' => ...]
}

// ❌ WRONG: Business logic in use case (should be in entity)
final class CancelOrderUseCase {
    public function execute(string $orderId): array {
        $orderData = $this->repository->getById($orderId);

        // Business rules scattered in use case - WRONG!
        if ($orderData['status'] === 'completed' || $orderData['status'] === 'shipped') {
            throw new Exception("Cannot cancel order");
        }

        $orderData['status'] = 'cancelled';
        $this->repository->save($orderData);

        return $orderData;
    }
}
```

### Why This Is Wrong

1. **No encapsulation** - Business rules scattered across use cases
2. **Type safety lost** - Arrays have no type contracts
3. **No behavior** - Logic duplicated wherever data is used
4. **Testing harder** - Must test use cases, not domain logic
5. **Violates OOP** - Objects should have behavior, not just data

### The Correct Approach: Rich Domain Models

```php
// ✅ CORRECT: Repository returning strongly-typed entities
interface OrderRepository {
    public function getById(OrderId $id): Order;  // Returns domain entity
}

// ✅ CORRECT: Rich entity with business behavior
final readonly class Order {
    public function cancel(): self {
        // Business rules encapsulated in entity
        if ($this->status->isFinal()) {
            throw new OrderCannotBeCancelledException($this->id);
        }

        return new self(
            $this->id,
            $this->total,
            OrderStatus::CANCELLED,
            $this->items
        );
    }
}

// ✅ CORRECT: Use case orchestrates, entity enforces rules
final class CancelOrderUseCase {
    public function execute(CancelOrderInput $input): OrderDto {
        $order = $this->repository->getById(new OrderId($input->orderId));

        // Entity behavior enforces business rules
        $cancelledOrder = $order->cancel();

        $this->repository->save($cancelledOrder);

        return OrderDto::fromEntity($cancelledOrder);
    }
}
```

### Migration Path

If you have existing data-oriented code:

1. **Create rich entity classes** with value objects and behavior methods
2. **Update repository interfaces** to return entities instead of arrays
3. **Move business logic** from use cases into entity methods
4. **Update use cases** to delegate to entity behavior
5. **Keep query services** for read-only projections (those can still return arrays)

---

## Application Layer Rules

**Location**: `src/{Context}/Application/`

### What Goes Here

- Use cases (one per user action/business workflow)
- Input DTOs (data coming into use case)
- Output DTOs (data going out of use case)
- Application exceptions
- Command/Query interfaces

### Mandatory Rules

✅ **ALWAYS**:
- Depend ONLY on Domain interfaces (never Infrastructure)
- Use constructor dependency injection
- Return DTOs (never domain entities)
- Map domain entities to DTOs inside use case
- Make use case classes final
- Create one use case per action
- Throw explicitly-named exceptions
- Create DTOs with ONLY properties needed by consumers

❌ **NEVER**:
- Import framework facades or global helpers
- Import Infrastructure classes directly
- Return domain entities to presentation layer
- Put business logic here (belongs in Domain)
- Mix multiple actions in one use case
- Instantiate dependencies (use DI)

### Code Patterns

```php
// ✅ CORRECT: Use case with DI and DTO output
final class CreateOrderUseCase {
    public function __construct(
        private OrderRepository $repository,
        private PricingService $pricingService,
    ) {}
    
    public function execute(CreateOrderInput $input): OrderCreatedDto {
        $order = Order::create(
            items: $input->items,
            total: $this->pricingService->calculate($input->items)
        );
        
        $this->repository->save($order);
        
        // Map to DTO
        return new OrderCreatedDto(
            id: $order->id()->value(),
            total: $order->total()->amount(),
            status: $order->status()->value(),
        );
    }
}

// ✅ CORRECT: Output DTO (only what UI needs)
final readonly class OrderCreatedDto {
    public function __construct(
        public string $id,
        public float $total,
        public string $status,
    ) {}
}

// ❌ WRONG: Direct dependency on Infrastructure
final class CreateOrderUseCase {
    public function __construct(
        private EloquentOrderRepository $repository // NO! Use interface
    ) {}
}

// ❌ WRONG: Returning domain entity
public function execute(CreateOrderInput $input): Order {
    return $this->repository->save($order); // NO!
}
```

---

## Infrastructure Layer Rules


**Location**: `src/{Context}/Infrastructure/`


### What Goes Here


- Repository implementations (persist/reconstitute aggregates only). [web:16][web:27]
- Query service implementations (read models/projections for UI/use cases). [web:16]
- External API clients
- Message queue adapters
- Database/ORM code
- Framework-specific code
- Third-party integrations


### Mandatory Rules


✅ **ALWAYS**:
- Implement Domain/Application interfaces (ports); infrastructure is an adapter. [web:27]
- Use framework/library features freely inside adapters (ORM, HTTP clients, queues). [web:27]
- Make implementation classes final.
- Translate infrastructure exceptions to domain/application exceptions (don't leak vendor errors inward).
- Keep separate query services (read) from repositories (write/consistency boundary). [web:16]
- **Repositories MUST reconstitute and return strongly-typed domain entities**, never arrays. [web:16][web:27]
- Repositories must **reconstitute and persist a single Aggregate Root** (load full aggregate; save full aggregate). [web:16][web:27]
- Repository methods return: single entity (`Order`), nullable entity (`?Order`), or array of entities (`array<Order>`)


❌ **NEVER**:
- Add business logic (delegate to Domain).
- Let infrastructure concerns leak to inner layers (ORM models, query builders, HTTP responses).
- **Return arrays/DTOs/projections from repositories** (repositories return domain entities ONLY). [web:16][web:27]
- Return raw database query results or Eloquent models from repositories
- Use repositories to serve UI/reporting queries (that's what query services/read models are for). [web:16]
- Mix repository pattern with data-oriented array returns (this violates hexagonal architecture)


### Code Patterns


```php
// ✅ CORRECT: Repository returns strongly-typed domain entities, never arrays
final class EloquentOrderRepository implements OrderRepository
{
    public function getById(OrderId $id): Order
    {
        $model = OrderModel::with('items')->findOrFail($id->value());

        // Reconstitute the full aggregate with all behavior
        return $this->toDomain($model);
    }

    public function findByCustomer(CustomerId $customerId): array
    {
        $models = OrderModel::where('customer_id', $customerId->value())->get();

        // Return array of domain entities, not arrays!
        return $models->map(fn($model) => $this->toDomain($model))->all();
    }

    public function save(Order $order): void
    {
        // Persist the aggregate state; infra decides how (ORM, SQL, events, etc.)
        OrderModel::updateOrCreate(
            ['id' => $order->id()->value()],
            [
                'total' => $order->total()->amount(),
                'status' => $order->status()->value(),
                'customer_id' => $order->customerId()->value(),
            ]
        );
    }

    private function toDomain(OrderModel $model): Order
    {
        // Full reconstitution with all data needed for entity behavior
        return new Order(
            id: new OrderId($model->id),
            total: new Money($model->total),
            status: OrderStatus::from($model->status),
            items: $model->items->map(fn($item) => $this->itemToDomain($item))->all(),
        );
    }
}


// ✅ CORRECT: Query service for reads (returns arrays/DTOs for UI)
// Use query services when you DON'T need entity behavior, just data projection
final class EloquentOrderQueryService implements OrderQueryService
{
    public function getOrdersForDashboard(): array
    {
        // Direct array return for read-only UI data
        return DB::table('orders')
            ->select('id', 'total', 'status', 'created_at')
            ->where('status', 'pending')
            ->get()
            ->toArray();
    }

    public function getOrderSummary(string $orderId): array
    {
        return DB::table('orders')
            ->where('id', $orderId)
            ->first();
    }
}


// ✅ CORRECT: Use case uses repository to get entity, then maps to DTO
final class CancelOrderUseCase
{
    public function __construct(private OrderRepository $orders) {}

    public function execute(CancelOrderInput $input): OrderDto
    {
        // Get strongly-typed entity from repository
        $order = $this->orders->getById(new OrderId($input->orderId));

        // Use entity behavior to enforce business rules
        $cancelledOrder = $order->cancel();

        // Save updated entity
        $this->orders->save($cancelledOrder);

        // Map entity to DTO for presentation layer
        return OrderDto::fromEntity($cancelledOrder);
    }
}


// ❌ WRONG: Repository returning array instead of entity (data-oriented anti-pattern)
final class EloquentOrderRepository implements OrderRepository
{
    public function getById(OrderId $id): array  // NO! Return Order entity
    {
        return DB::table('orders')->where('id', $id->value())->first();
    }
}


// ❌ WRONG: Repository returning DTO/projection (breaks repository contract)
final class EloquentOrderRepository implements OrderRepository
{
    public function getById(OrderId $id): OrderDto  // NO! Return Order entity
    {
        return new OrderDto(...);
    }
}


// ❌ WRONG: Use case working with arrays instead of entities (data-oriented anti-pattern)
final class CancelOrderUseCase
{
    public function execute(CancelOrderInput $input): array
    {
        $order = $this->orders->getById($input->orderId);  // Returns array - WRONG!

        // Business logic in use case instead of entity - WRONG!
        if ($order['status'] === 'completed') {
            throw new Exception();
        }

        $order['status'] = 'cancelled';
        $this->orders->save($order);

        return $order;
    }
}


// ❌ WRONG: Business logic in infrastructure
final class EloquentOrderRepository implements OrderRepository
{
    public function save(Order $order): void
    {
        if ($order->total()->amount() > 1000) { // NO! Business logic belongs in Domain
            throw new Exception();
        }
    }
}

---

## Exception Handling Strategy

### Exception Flow Direction

```
Domain Exceptions ←─── Application Exceptions ←─── Infrastructure (translates)
       ↑                       ↑
       └───────────────────────┘
   (Inner layers define, outer layers throw)
```

### Domain Exceptions

**Purpose**: Business rule violations

**Naming**: `{BusinessConcept}{ViolationType}Exception`

**Examples**:
- `OrderCannotBeCancelledException`
- `InsufficientFundsException`
- `InvalidEmailFormatException`
- `DuplicateOrderException`

```php
// ✅ Domain exception
final class OrderCannotBeCancelledException extends DomainException {
    public function __construct(OrderId $orderId) {
        parent::__construct("Order {$orderId->value()} cannot be cancelled");
    }
}
```

### Application Exceptions

**Purpose**: Use case workflow failures

**Naming**: `{Resource}{Operation}Exception`

**Examples**:
- `OrderNotFoundException`
- `UnauthorizedAccessException`
- `ValidationException`

```php
// ✅ Application exception
final class OrderNotFoundException extends ApplicationException {
    public function __construct(string $orderId) {
        parent::__construct("Order not found: {$orderId}");
    }
}
```

### Infrastructure Exception Translation

**Rule**: Always catch infrastructure exceptions and translate to domain/application exceptions

```php
// ✅ CORRECT: Translate infrastructure exceptions
final class EloquentOrderRepository implements OrderRepository {
    public function getById(OrderId $id): Order {
        try {
            $model = OrderModel::findOrFail($id->value());
            return $this->toDomain($model);
        } catch (ModelNotFoundException $e) {
            throw new OrderNotFoundException($id->value());
        } catch (QueryException $e) {
            throw new RepositoryException("Database error", previous: $e);
        }
    }
}

// ❌ WRONG: Let infrastructure exceptions bubble up
public function getById(OrderId $id): Order {
    return $this->toDomain(OrderModel::findOrFail($id->value())); // NO!
}
```

---

## Dependency Rules

### The Golden Rule

**Dependencies point inward. Inner layers define interfaces (ports). Outer layers implement interfaces (adapters).**

```
┌─────────────────────────────────────────┐
│         Infrastructure Layer            │
│  (implements Domain/Application ports)  │
├─────────────────────────────────────────┤
│         Application Layer               │
│  (depends on Domain ports only)         │
├─────────────────────────────────────────┤
│         Domain Layer                    │
│  (zero external dependencies)           │
└─────────────────────────────────────────┘
```

### Import Rules by Layer

**Domain Layer** can import:
- ✅ Only standard library
- ✅ Other domain classes in same context

**Application Layer** can import:
- ✅ Domain layer (interfaces and entities)
- ✅ Other application classes in same context
- ❌ NEVER Infrastructure layer

**Infrastructure Layer** can import:
- ✅ Domain layer (interfaces)
- ✅ Application layer (interfaces)
- ✅ Framework libraries
- ✅ External libraries

---

## Quick Reference

### Layer Checklist

| Layer | Imports | Returns | Throws |
|-------|---------|---------|--------|
| **Domain** | Standard lib only | Entities, Value Objects | Domain exceptions |
| **Application** | Domain only | DTOs | Application exceptions |
| **Infrastructure (Repositories)** | Domain, Application, Frameworks | **Entities** (single, array, or nullable) | Translated exceptions |
| **Infrastructure (Query Services)** | Domain, Application, Frameworks | Arrays, DTOs (read projections) | Translated exceptions |

### File Naming Conventions

```
Domain/
  ├── Order.php                    # Entity
  ├── OrderId.php                  # Value Object
  ├── OrderRepository.php          # Port interface
  └── OrderCannotBeCancelled.php   # Domain exception

Application/
  ├── CreateOrderUseCase.php       # Use case
  ├── CreateOrderInput.php         # Input DTO
  ├── OrderCreatedDto.php          # Output DTO
  └── OrderNotFoundException.php   # Application exception

Infrastructure/
  ├── EloquentOrderRepository.php  # Repository adapter
  └── OrderQueryService.php        # Query adapter
```

---

## Summary: The Core Rules

1. **Rich domain entities** - Entities have behavior methods, NOT anemic data containers
2. **Strongly-typed repositories** - Repositories return domain entities, NEVER arrays or DTOs
3. **Domain layer is pure** - No framework, no imports, only business logic
4. **Application orchestrates** - Use cases coordinate domain entities, return DTOs
5. **Infrastructure implements** - Adapters connect to external systems
6. **Dependencies point inward** - Domain ← Application ← Infrastructure
7. **Entities stay internal** - Never return entities to presentation (map to DTOs)
8. **Separate reads and writes** - Query services for reads (arrays), repositories for writes (entities)
9. **Translate exceptions** - Infrastructure exceptions become domain/application exceptions
10. **One use case per action** - Single responsibility principle
11. **Interfaces in inner layers** - Implementations in outer layers (dependency inversion)
12. **DTOs are the boundary** - Clean data contracts between application and presentation layers

### Key Principle: Avoid Data-Oriented Programming

**NEVER:**
- Return arrays from repositories (return entities)
- Put business logic in use cases (put in entities)
- Use primitive types where value objects belong
- Create anemic domain models (entities without behavior)

**ALWAYS:**
- Return strongly-typed entities from repositories
- Encapsulate business rules in entity behavior methods
- Use value objects for domain concepts (Money, Email, OrderId, etc.)
- Make entities rich with methods that enforce invariants
