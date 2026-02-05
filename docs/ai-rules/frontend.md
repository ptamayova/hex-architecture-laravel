# Frontend Development Rules

## Mandatory Rules

✅ **ALWAYS**:
- Use shadcn/ui components (Button, Input, Select, Table, Dialog, Form, etc.)
- Create custom reusable components only if shadcn doesn't provide it
- Keep business logic and API calls in page/container components only
- Use TypeScript for type safety with proper interfaces
- Check shadcn/ui library FIRST before creating any UI component
- Split layouts into small, reusable, single-responsibility components
- Use Inertia's router for navigation (`router.visit`, `Link` component)
- Type all Inertia props with TypeScript interfaces

❌ **NEVER**:
- Write inline markup for buttons, inputs, forms, dialogs, or any UI element that shadcn provides
- Put API calls or business logic inside presentational components
- Create custom components without checking shadcn first
- Use axios or fetch directly - use Inertia's router methods instead
- Mutate Inertia props directly
- Use window.location for navigation

## Inertia.js Integration

### Controller to React Props Flow

Laravel controllers MUST:
- Use single-action controllers with `__invoke` method (one responsibility per controller)
- Delegate business logic to Application Layer Use Cases
- Pass Use Case DTOs directly as Inertia props (DTOs are serializable and type-safe)
- Never access repositories, models, or database directly

```php
// ✅ CORRECT: Single-Action Controllers with Use Cases

use Inertia\Inertia;
use Inertia\Response;
use App\Application\Order\ListOrders\ListOrdersUseCase;
use App\Application\Order\ListOrders\ListOrdersInputDto;
use App\Application\Order\ShowOrder\ShowOrderUseCase;
use App\Application\Order\ShowOrder\ShowOrderInputDto;
use App\Application\Order\CreateOrder\CreateOrderUseCase;
use App\Application\Order\CreateOrder\CreateOrderInputDto;

// List all orders
class ListOrdersController extends Controller
{
    public function __invoke(
        ListOrdersUseCase $useCase
    ): Response {
        $input = new ListOrdersInputDto(
            status: request('status'),
            search: request('search'),
            page: request('page', 1),
            perPage: 20
        );

        $output = $useCase->execute($input);

        return Inertia::render('Orders/Index', [
            'orders' => $output->orders,      // OrderListDto[]
            'pagination' => $output->pagination,
            'filters' => $output->filters,
            'stats' => $output->stats,
        ]);
    }
}

// Show single order
class ShowOrderController extends Controller
{
    public function __invoke(
        string $orderId,
        ShowOrderUseCase $useCase
    ): Response {
        $input = new ShowOrderInputDto(orderId: $orderId);
        $output = $useCase->execute($input);

        return Inertia::render('Orders/Show', [
            'order' => $output->order,  // OrderDetailDto
        ]);
    }
}

// Create new order
class StoreOrderController extends Controller
{
    public function __invoke(
        StoreOrderRequest $request,
        CreateOrderUseCase $useCase
    ): Response {
        $input = new CreateOrderInputDto(
            customerId: $request->validated('customer_id'),
            items: $request->validated('items'),
            notes: $request->validated('notes')
        );

        $output = $useCase->execute($input);

        return Inertia::render('Orders/Show', [
            'order' => $output->order,  // OrderDetailDto
        ]);
    }
}

// ❌ WRONG: Multi-action resource controller
class OrderController extends Controller
{
    public function index() { } // NO! Use ListOrdersController
    public function show() { }  // NO! Use ShowOrderController
    public function store() { } // NO! Use StoreOrderController
}

// ❌ WRONG: Controller accessing models/repositories directly
class ListOrdersController extends Controller
{
    public function __invoke(): Response
    {
        // NO! Don't access models directly
        $orders = Order::with('customer')->paginate(20);

        return Inertia::render('Orders/Index', ['orders' => $orders]);
    }
}

// ❌ WRONG: Controller with business logic
class StoreOrderController extends Controller
{
    public function __invoke(Request $request): Response
    {
        // NO! Business logic belongs in Use Cases
        if ($request->total > 1000) {
            $discount = $request->total * 0.1;
        }

        $order = Order::create([...]);

        return Inertia::render('Orders/Show', ['order' => $order]);
    }
}
```

**Application Layer DTOs:**
```php
// ✅ CORRECT: Output DTO from Use Case
namespace App\Application\Order\ListOrders;

final readonly class ListOrdersOutputDto
{
    public function __construct(
        /** @var OrderListDto[] */
        public array $orders,
        public PaginationDto $pagination,
        public OrderFiltersDto $filters,
        public OrderStatsDto $stats,
    ) {}
}

// Individual order in the list
final readonly class OrderListDto
{
    public function __construct(
        public string $id,
        public string $customerName,
        public string $customerEmail,
        public float $total,
        public string $status,
        public string $createdAt,
    ) {}
}

// Pagination info
final readonly class PaginationDto
{
    public function __construct(
        public int $currentPage,
        public int $perPage,
        public int $total,
        public int $lastPage,
    ) {}
}
```

**Routing:**
```php
// routes/web.php
Route::get('/orders', ListOrdersController::class)->name('orders.index');
Route::get('/orders/{order}', ShowOrderController::class)->name('orders.show');
Route::post('/orders', StoreOrderController::class)->name('orders.store');
Route::put('/orders/{order}', UpdateOrderController::class)->name('orders.update');
Route::delete('/orders/{order}', DeleteOrderController::class)->name('orders.destroy');
```

```tsx
// ✅ CORRECT: React Page consuming props
import { PageProps } from '@/types';

interface Order {
    id: string;
    customer: {
        name: string;
        email: string;
    };
    total: number;
    status: string;
}

interface OrderIndexProps extends PageProps {
    orders: {
        data: Order[];
        links: any;
        meta: any;
    };
    filters: {
        status?: string;
        search?: string;
    };
    stats: {
        total: number;
        pending: number;
    };
}

export default function OrderIndex({ orders, filters, stats }: OrderIndexProps) {
    return (
        <AuthenticatedLayout>
            <OrderStats stats={stats} />
            <OrderFilters filters={filters} />
            <OrderTable orders={orders.data} />
            <Pagination links={orders.links} />
        </AuthenticatedLayout>
    );
}
```

### Inertia Navigation & Forms

```tsx
// ✅ CORRECT: Using Inertia router for navigation and forms
import { router, Link, useForm } from '@inertiajs/react';

function OrderActions({ orderId }: { orderId: string }) {
    // Navigate programmatically
    const handleView = () => {
        router.visit(`/orders/${orderId}`);
    };

    // Form submission with Inertia
    const { data, setData, post, processing, errors } = useForm({
        status: 'cancelled',
        reason: '',
    });

    const handleCancel = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/orders/${orderId}/cancel`, {
            preserveScroll: true,
            onSuccess: () => {
                // Handle success
            },
        });
    };

    return (
        <div>
            {/* Declarative links */}
            <Link href={`/orders/${orderId}`} className="...">
                View Order
            </Link>

            {/* Form submission */}
            <form onSubmit={handleCancel}>
                <Input
                    value={data.reason}
                    onChange={(e) => setData('reason', e.target.value)}
                    error={errors.reason}
                />
                <Button type="submit" disabled={processing}>
                    Cancel Order
                </Button>
            </form>
        </div>
    );
}

// ❌ WRONG: Using axios or fetch
function WrongOrderActions({ orderId }: { orderId: string }) {
    const handleCancel = async () => {
        await axios.post(`/api/orders/${orderId}/cancel`); // NO!
        window.location.href = '/orders'; // NO!
    };
}
```

### Shared Data & Global Props

```php
// ✅ CORRECT: Share global data via HandleInertiaRequests middleware
class HandleInertiaRequests extends Middleware
{
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? new UserResource($request->user()) : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'app' => [
                'name' => config('app.name'),
            ],
        ]);
    }
}
```

```tsx
// ✅ CORRECT: Access shared data via usePage hook
import { usePage } from '@inertiajs/react';
import { PageProps } from '@/types';

export default function Navigation() {
    const { auth, flash } = usePage<PageProps>().props;

    return (
        <nav>
            {auth.user && <span>Welcome, {auth.user.name}</span>}
            {flash.success && <Alert variant="success">{flash.success}</Alert>}
        </nav>
    );
}
```

## Clean Code Principles

### Component Organization

```
resources/js/
├── Components/
│   ├── ui/              # shadcn/ui components (auto-generated)
│   ├── Layout/          # Layout components
│   │   ├── AuthenticatedLayout.tsx
│   │   ├── GuestLayout.tsx
│   │   ├── Navigation.tsx
│   │   └── Sidebar.tsx
│   ├── Orders/          # Feature-specific components
│   │   ├── OrderTable.tsx
│   │   ├── OrderCard.tsx
│   │   ├── OrderFilters.tsx
│   │   └── OrderStats.tsx
│   └── Shared/          # Shared reusable components
│       ├── Pagination.tsx
│       ├── EmptyState.tsx
│       └── LoadingSpinner.tsx
├── Pages/               # Inertia pages (containers)
│   ├── Orders/
│   │   ├── Index.tsx
│   │   ├── Show.tsx
│   │   └── Create.tsx
│   └── Dashboard.tsx
└── types/
    ├── index.d.ts
    └── models.d.ts
```

### Single Responsibility Principle

Break down complex layouts into small, focused components:

```tsx
// ✅ CORRECT: Small, focused components
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";

interface Order {
    id: string;
    total: number;
    status: string;
}

// Single responsibility: Display order status
function OrderStatus({ status }: { status: string }) {
    const variant = status === 'completed' ? 'success' : 'default';
    return <Badge variant={variant}>{status}</Badge>;
}

// Single responsibility: Display order total
function OrderTotal({ amount }: { amount: number }) {
    return <span className="font-semibold">${amount.toFixed(2)}</span>;
}

// Single responsibility: Display single order card
function OrderCard({ order }: { order: Order }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex justify-between">
                    <span>Order #{order.id}</span>
                    <OrderStatus status={order.status} />
                </CardTitle>
            </CardHeader>
            <CardContent>
                <OrderTotal amount={order.total} />
            </CardContent>
        </Card>
    );
}

// Single responsibility: Display list of orders
function OrderList({ orders }: { orders: Order[] }) {
    if (orders.length === 0) {
        return <EmptyState message="No orders found" />;
    }

    return (
        <div className="grid gap-4">
            {orders.map(order => (
                <OrderCard key={order.id} order={order} />
            ))}
        </div>
    );
}

// ❌ WRONG: Monolithic component doing everything
function OrderListWrong({ orders }: { orders: Order[] }) {
    return (
        <div>
            {orders.map(order => (
                <div key={order.id} className="border rounded p-4">
                    <div className="flex justify-between">
                        <span>Order #{order.id}</span>
                        <span className={order.status === 'completed' ? 'text-green-600' : ''}>
                            {order.status}
                        </span>
                    </div>
                    <div className="mt-2">
                        <span className="font-semibold">${order.total.toFixed(2)}</span>
                    </div>
                </div>
            ))}
        </div>
    );
}
```

### Props vs. Children Pattern

```tsx
// ✅ CORRECT: Use children for flexible composition
import { Card, CardContent, CardHeader } from "@/components/ui/card";

function OrderSection({ children }: { children: React.ReactNode }) {
    return (
        <Card className="mb-6">
            <CardContent className="pt-6">
                {children}
            </CardContent>
        </Card>
    );
}

// Usage
function OrderPage() {
    return (
        <div>
            <OrderSection>
                <OrderStats stats={stats} />
            </OrderSection>
            <OrderSection>
                <OrderTable orders={orders} />
            </OrderSection>
        </div>
    );
}
```

### Prop Interfaces & Type Safety

```tsx
// ✅ CORRECT: Well-defined interfaces
interface User {
    id: string;
    name: string;
    email: string;
}

interface OrderTableProps {
    orders: Order[];
    onEdit?: (orderId: string) => void;
    onDelete?: (orderId: string) => void;
    isLoading?: boolean;
}

function OrderTable({
    orders,
    onEdit,
    onDelete,
    isLoading = false
}: OrderTableProps) {
    // Implementation
}

// ❌ WRONG: Any types or missing interfaces
function OrderTableWrong({ orders, onEdit }: any) {
    // Implementation
}
```

### Event Handlers & Callbacks

```tsx
// ✅ CORRECT: Clear, typed event handlers
interface OrderActionsProps {
    orderId: string;
    onSuccess: () => void;
}

function OrderActions({ orderId, onSuccess }: OrderActionsProps) {
    const handleDelete = () => {
        router.delete(`/orders/${orderId}`, {
            onSuccess: () => {
                onSuccess();
            },
        });
    };

    return (
        <Button variant="destructive" onClick={handleDelete}>
            Delete
        </Button>
    );
}

// ❌ WRONG: Inline handlers with complex logic
function OrderActionsWrong({ orderId }: { orderId: string }) {
    return (
        <button onClick={() => {
            // Complex logic inline - NO!
            if (confirm('Are you sure?')) {
                router.delete(`/orders/${orderId}`);
                // More logic...
            }
        }}>
            Delete
        </button>
    );
}
```

## shadcn/ui Component Priority

### Component Checklist

Before creating ANY new component, check if shadcn/ui provides it:

**Available shadcn/ui components** (always use these):
- `Button` - all button variations
- `Input`, `Textarea`, `Select`, `Checkbox`, `RadioGroup` - form inputs
- `Form` - form handling with validation
- `Dialog`, `AlertDialog` - modals and confirmations
- `Table` - data tables
- `Card` - content containers
- `Badge` - status indicators
- `Alert` - notifications
- `Dropdown Menu`, `Context Menu` - menus
- `Tabs` - tabbed interfaces
- `Accordion` - collapsible content
- `Sheet` - slide-out panels
- `Toast` - toast notifications
- `Popover`, `Tooltip` - overlays
- `Pagination` - pagination controls
- `Skeleton` - loading states
- `Avatar` - user avatars
- `Calendar`, `DatePicker` - date selection
- `Command` - command palette
- `Separator` - dividers

```tsx
// ✅ CORRECT: Using shadcn components
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter
} from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";

function CreateOrderDialog({ open, onOpenChange }: Props) {
    const { data, setData, post, errors } = useForm({
        customer_name: '',
        amount: '',
    });

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Create New Order</DialogTitle>
                </DialogHeader>
                <div className="grid gap-4 py-4">
                    <div className="grid gap-2">
                        <Label htmlFor="customer_name">Customer Name</Label>
                        <Input
                            id="customer_name"
                            value={data.customer_name}
                            onChange={(e) => setData('customer_name', e.target.value)}
                        />
                        {errors.customer_name && (
                            <p className="text-sm text-destructive">{errors.customer_name}</p>
                        )}
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" onClick={() => onOpenChange(false)}>
                        Cancel
                    </Button>
                    <Button onClick={() => post('/orders')}>
                        Create Order
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

// ❌ WRONG: Custom modal implementation
function CreateOrderDialogWrong() {
    return (
        <div className="fixed inset-0 bg-black/50"> {/* NO! Use shadcn Dialog */}
            <div className="bg-white p-6 rounded">
                <h2>Create Order</h2>
                <input type="text" className="border p-2" /> {/* NO! Use shadcn Input */}
                <button className="bg-blue-600 text-white px-4 py-2"> {/* NO! Use shadcn Button */}
                    Submit
                </button>
            </div>
        </div>
    );
}
```

### Custom Components (Only When Necessary)

Create custom components ONLY when:
1. shadcn/ui doesn't provide the component
2. You need domain-specific composition (e.g., `OrderCard`, `ProductList`)
3. Explicitly requested by the user

```tsx
// ✅ CORRECT: Custom domain component using shadcn primitives
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";

interface OrderCardProps {
    order: Order;
    onView: (id: string) => void;
}

function OrderCard({ order, onView }: OrderCardProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex justify-between items-center">
                    <span>Order #{order.id}</span>
                    <Badge variant={order.status === 'completed' ? 'success' : 'default'}>
                        {order.status}
                    </Badge>
                </CardTitle>
            </CardHeader>
            <CardContent>
                <p className="text-sm text-muted-foreground mb-4">
                    Customer: {order.customer.name}
                </p>
                <div className="flex justify-between items-center">
                    <span className="font-semibold">${order.total}</span>
                    <Button size="sm" onClick={() => onView(order.id)}>
                        View Details
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}
```

## Code Patterns Summary

```tsx
// ✅ COMPLETE CORRECT EXAMPLE
import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import AuthenticatedLayout from '@/Components/Layout/AuthenticatedLayout';
import { PageProps } from '@/types';

interface Order {
    id: string;
    customer: { name: string; email: string };
    total: number;
    status: 'pending' | 'completed' | 'cancelled';
}

interface OrderIndexProps extends PageProps {
    orders: {
        data: Order[];
    };
}

// Presentational component
function OrderStatus({ status }: { status: Order['status'] }) {
    const variants = {
        pending: 'default',
        completed: 'success',
        cancelled: 'destructive',
    } as const;

    return <Badge variant={variants[status]}>{status}</Badge>;
}

// Presentational component
function OrderCard({ order, onView }: { order: Order; onView: () => void }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex justify-between">
                    <span>Order #{order.id}</span>
                    <OrderStatus status={order.status} />
                </CardTitle>
            </CardHeader>
            <CardContent>
                <p className="text-sm mb-2">{order.customer.name}</p>
                <div className="flex justify-between items-center">
                    <span className="font-semibold">${order.total.toFixed(2)}</span>
                    <Button size="sm" onClick={onView}>View</Button>
                </div>
            </CardContent>
        </Card>
    );
}

// Container/Page component with business logic
export default function OrderIndex({ orders }: OrderIndexProps) {
    const handleViewOrder = (orderId: string) => {
        router.visit(`/orders/${orderId}`);
    };

    return (
        <AuthenticatedLayout>
            <div className="container mx-auto py-6">
                <h1 className="text-3xl font-bold mb-6">Orders</h1>
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {orders.data.map((order) => (
                        <OrderCard
                            key={order.id}
                            order={order}
                            onView={() => handleViewOrder(order.id)}
                        />
                    ))}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
```
