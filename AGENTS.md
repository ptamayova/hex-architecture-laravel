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

- Entities (business objects with identity)
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
- Keep entities rich with behavior, not anemic data containers
- Put all business rules in domain layer

❌ **NEVER**:
- Import framework libraries
- Import infrastructure classes
- Add UI concerns (formatting, colors, display logic)
- Add persistence concerns (SQL, database logic)
- Depend on Application or Infrastructure layers
- Add methods like `toArray()`, `toJson()`, `toForm()` for presentation

### Code Patterns

```php
// ✅ CORRECT: Rich entity with behavior
final readonly class Order {
    public function __construct(
        private OrderId $id,
        private Money $total,
        private OrderStatus $status,
    ) {}
    
    public function cancel(): void {
        if ($this->status->isFinal()) {
            throw new OrderCannotBeCancelledException();
        }
        $this->status = OrderStatus::CANCELLED;
    }
}

// ✅ CORRECT: Port interface (no implementation)
interface OrderRepository {
    public function getById(OrderId $id): Order;
    public function save(Order $order): void;
}

// ❌ WRONG: Framework dependency in domain
use Illuminate\Database\Eloquent\Model;
class Order extends Model { } // NO!

// ❌ WRONG: Presentation logic in domain
class Order {
    public function toArray(): array { } // NO!
    public function getColorForStatus(): string { } // NO!
}
```

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

- Repository implementations
- Query service implementations
- External API clients
- Message queue adapters
- Database/ORM code
- Framework-specific code
- Third-party integrations

### Mandatory Rules

✅ **ALWAYS**:
- Implement Domain/Application interfaces
- Use framework/library features freely
- Make implementation classes final
- Translate infrastructure exceptions to domain/application exceptions
- Keep separate query services (read) from repositories (write)

❌ **NEVER**:
- Add business logic (delegate to Domain)
- Let infrastructure concerns leak to inner layers
- Mix query methods and command methods in same interface
- Return DTOs from repositories (DTOs belong in Application)

### Code Patterns

```php
// ✅ CORRECT: Repository implementation
final class EloquentOrderRepository implements OrderRepository {
    public function getById(OrderId $id): Order {
        $model = OrderModel::findOrFail($id->value());
        return $this->toDomain($model);
    }
    
    public function save(Order $order): void {
        OrderModel::updateOrCreate(
            ['id' => $order->id()->value()],
            $this->toArray($order)
        );
    }
    
    private function toDomain(OrderModel $model): Order {
        return new Order(
            id: new OrderId($model->id),
            total: new Money($model->total),
            status: OrderStatus::from($model->status),
        );
    }
}

// ✅ CORRECT: Separate query service for reads
final class EloquentDashboardQueryService implements DashboardQueryService {
    public function getDashboardData(): array {
        return DB::table('orders')
            ->select('id', 'total', 'status', 'created_at')
            ->where('status', 'pending')
            ->get()
            ->toArray();
    }
}

// ❌ WRONG: Business logic in infrastructure
final class EloquentOrderRepository implements OrderRepository {
    public function save(Order $order): void {
        if ($order->total() > 1000) { // NO! Business logic belongs in Domain
            throw new Exception();
        }
    }
}
```

---

## Frontend Development Rules

### Mandatory Rules

✅ **ALWAYS**:
- Use shadcn/ui components (Button, Input, Select, Table, etc.)
- Create custom reusable components only if shadcn doesn't provide it
- Keep business logic and API calls in page/container components only
- Use TypeScript for type safety

❌ **NEVER**:
- Write inline markup for buttons, inputs, forms, or any UI element
- Put API calls or business logic inside presentational components
- Create custom components without checking shadcn first

### Code Patterns

```tsx
// ✅ CORRECT: Using shadcn, business logic in container
import { Button } from "@/components/ui/button"

function OrderListPage() {
    const [orders, setOrders] = useState<OrderDto[]>([]);

    useEffect(() => {
        api.get('/api/orders').then(setOrders); // API call in container
    }, []);

    const handleCancel = async (orderId: string) => {
        await api.post(`/api/orders/${orderId}/cancel`); // Business logic here
    };

    return <OrderTable orders={orders} onCancel={handleCancel} />;
}

// ❌ WRONG: Inline markup + business logic in presentational component
function OrderTable({ orders }: Props) {
    return (
        <div>
            <button className="bg-blue-600 px-4 py-2">Cancel</button> {/* NO! */}
            {orders.map(order => {
                const canCancel = order.total < 1000; // NO! Business rule
                return <div key={order.id}>...</div>;
            })}
        </div>
    );
}
```

---

## Query vs Command Pattern (CQRS)

Use separate interfaces for reads and writes.

### When to Use Query Services (Reads)

**Use for**: Dashboards, lists, reports, search results, any display-only data

**Pattern**:
- Create `{Name}QueryService` interface in Application layer
- Return arrays or primitives (NOT domain entities)
- Optimize queries for specific views
- No business rule enforcement needed

```php
// ✅ Query Service Interface
interface DashboardQueryService {
    public function getDashboardData(): array;
    public function getOrdersByStatus(string $status): array;
}

// ✅ Use case with query service
final class GetDashboardUseCase {
    public function __construct(
        private DashboardQueryService $queryService
    ) {}
    
    public function execute(): array {
        $data = $this->queryService->getDashboardData();
        return array_map(
            fn($row) => new DashboardItemDto(...$row),
            $data
        );
    }
}
```

### When to Use Repositories (Writes)

**Use for**: Creating, updating, deleting entities with business rules

**Pattern**:
- Create `{Entity}Repository` interface in Domain layer
- Return/accept domain entities
- Enforce business rules through entities

```php
// ✅ Repository Interface
interface OrderRepository {
    public function getById(OrderId $id): Order;
    public function save(Order $order): void;
    public function delete(OrderId $id): void;
}

// ✅ Use case with repository
final class CancelOrderUseCase {
    public function __construct(
        private OrderRepository $repository
    ) {}
    
    public function execute(string $orderId): void {
        $order = $this->repository->getById(new OrderId($orderId));
        $order->cancel(); // Business rules enforced in entity
        $this->repository->save($order);
    }
}
```

### Decision Matrix

| Operation | Use | Returns | Purpose |
|-----------|-----|---------|---------|
| Display list | Query Service | `array` | Read-only data for UI |
| Display details | Query Service | `array` | Optimized view data |
| Create entity | Repository | `void` | Enforce business rules |
| Update entity | Repository | `void` | Enforce business rules |
| Delete entity | Repository | `void` | Enforce business rules |
| Search/filter | Query Service | `array` | Read-only search results |

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

## Development Workflow

### Step-by-Step Process

1. **Identify bounded context** - Which business area?

2. **Write failing test** - Define expected behavior

3. **Create use case** - Get it working end-to-end

4. **Refactor to layers**:
    - Extract business rules → Domain entities
    - Define ports → Domain interfaces
    - Create DTOs → Application layer
    - Implement adapters → Infrastructure layer

5. **Verify architecture** - Check dependency direction

### Refactoring Checklist

Before committing code, verify:

- [ ] Domain layer has zero framework imports
- [ ] Application layer only imports Domain interfaces
- [ ] Infrastructure implements all ports
- [ ] Use cases return DTOs (never entities)
- [ ] Exceptions flow from inner to outer layers
- [ ] Business logic is in Domain, not Application or Infrastructure
- [ ] Each use case has single responsibility
- [ ] Entities are immutable where possible

---

## Common Mistakes to Avoid

### ❌ Mistake 1: Business Logic in Application Layer

```php
// WRONG
final class CreateOrderUseCase {
    public function execute(CreateOrderInput $input): OrderDto {
        // Business logic here - should be in Domain!
        if ($input->total < 0) {
            throw new InvalidOrderException();
        }
        // ...
    }
}

// CORRECT
final class CreateOrderUseCase {
    public function execute(CreateOrderInput $input): OrderDto {
        // Domain entity enforces rules
        $order = Order::create($input->items); // validates internally
        $this->repository->save($order);
        return OrderDto::fromEntity($order);
    }
}
```

### ❌ Mistake 2: Returning Entities from Use Cases

```php
// WRONG
public function execute(string $id): Order {
    return $this->repository->getById(new OrderId($id));
}

// CORRECT
public function execute(string $id): OrderDto {
    $order = $this->repository->getById(new OrderId($id));
    return new OrderDto(
        id: $order->id()->value(),
        total: $order->total()->amount(),
    );
}
```

### ❌ Mistake 3: Framework Imports in Domain

```php
// WRONG
namespace Domain;
use Illuminate\Support\Collection;

class Order {
    private Collection $items; // NO!
}

// CORRECT
namespace Domain;

class Order {
    private array $items; // Use standard types
}
```

### ❌ Mistake 4: Direct Infrastructure Dependencies

```php
// WRONG
final class CreateOrderUseCase {
    public function __construct(
        private EloquentOrderRepository $repository // Concrete class!
    ) {}
}

// CORRECT
final class CreateOrderUseCase {
    public function __construct(
        private OrderRepository $repository // Interface!
    ) {}
}
```

### ❌ Mistake 5: Mixed Query and Command Operations

```php
// WRONG
interface OrderRepository {
    public function getById(OrderId $id): Order; // Command
    public function getDashboardOrders(): array; // Query - doesn't belong here!
}

// CORRECT - Separate interfaces
interface OrderRepository {
    public function getById(OrderId $id): Order;
    public function save(Order $order): void;
}

interface OrderQueryService {
    public function getDashboardOrders(): array;
}
```

---

## Quick Reference

### Layer Checklist

| Layer | Imports | Returns | Throws |
|-------|---------|---------|--------|
| **Domain** | Standard lib only | Entities, Value Objects | Domain exceptions |
| **Application** | Domain only | DTOs | Application exceptions |
| **Infrastructure** | Domain, Application, Frameworks | Arrays (queries), void (commands) | Translated exceptions |

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

1. **Domain layer is pure** - No framework, no imports, only business logic
2. **Application orchestrates** - Use cases coordinate, return DTOs
3. **Infrastructure implements** - Adapters connect to external systems
4. **Dependencies point inward** - Domain ← Application ← Infrastructure
5. **Entities stay internal** - Never return entities to presentation
6. **Separate reads and writes** - Query services for reads, repositories for writes
7. **Translate exceptions** - Infrastructure exceptions become domain/application exceptions
8. **One use case per action** - Single responsibility principle
9. **Interfaces in inner layers** - Implementations in outer layers
10. **DTOs are the boundary** - Clean data contracts between layers
