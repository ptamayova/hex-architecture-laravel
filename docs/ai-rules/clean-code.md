# Clean Code Rules for AI Agents

This document contains clean code principles that AI agents must follow when developing features across all layers of the application.

---

## Core Principles

### 1. Meaningful Names and Clear Formatting

Use meaningful names and clear, consistent formatting so code is easy to read and understand for humans, not just machines.

✅ **ALWAYS**:
- Use descriptive, intention-revealing names for variables, functions, and classes
- Follow consistent naming conventions (camelCase for variables/functions, PascalCase for classes)
- Use searchable names - avoid single-letter variables except for loop iterators
- Use pronounceable names that can be discussed in conversation
- Maintain consistent indentation and spacing throughout the codebase
- Group related code together and separate concerns with blank lines

❌ **NEVER**:
- Use cryptic abbreviations or single-letter names (except `i`, `j`, `k` for loops)
- Mix naming conventions within the same codebase
- Use misleading names that don't reflect the actual purpose
- Write inconsistently formatted code with random spacing or indentation

**Examples**:

```php
// ❌ BAD
function getData($d) {
    $r = [];
    foreach ($d as $x) {
        if ($x->s == 'a') $r[] = $x;
    }
    return $r;
}

// ✅ GOOD
function getActiveUsers(array $users): array
{
    $activeUsers = [];

    foreach ($users as $user) {
        if ($user->status === 'active') {
            $activeUsers[] = $user;
        }
    }

    return $activeUsers;
}
```

---

### 2. Small Functions and Single Responsibility

Keep functions and classes small, each with a single responsibility, and avoid deep, convoluted logic flows.

✅ **ALWAYS**:
- Write functions that do one thing and do it well
- Keep functions short (ideally < 20 lines, max 50 lines)
- Extract complex conditionals into well-named functions
- Limit function parameters (ideally ≤ 3, use DTOs/objects for more)
- Keep cyclomatic complexity low (avoid deeply nested conditionals)
- Apply Single Responsibility Principle to classes and functions

❌ **NEVER**:
- Write god functions that do multiple unrelated things
- Nest conditionals more than 2-3 levels deep
- Create functions with side effects that aren't obvious from the name
- Mix different levels of abstraction in the same function
- Write classes with multiple reasons to change

**Examples**:

```typescript
// ❌ BAD: Multiple responsibilities, deep nesting
function processOrder(order: Order) {
    if (order.status === 'pending') {
        if (order.items.length > 0) {
            let total = 0;
            for (const item of order.items) {
                if (item.inStock) {
                    total += item.price * item.quantity;
                    if (item.discount) {
                        total -= item.discount;
                    }
                }
            }
            order.total = total;
            if (total > 100) {
                order.shipping = 0;
            } else {
                order.shipping = 10;
            }
            order.status = 'confirmed';
            sendEmail(order.customer.email, order);
        }
    }
}

// ✅ GOOD: Single responsibility, extracted functions
function processOrder(order: Order): void {
    if (!canProcessOrder(order)) {
        return;
    }

    const total = calculateOrderTotal(order);
    const shipping = calculateShipping(total);

    confirmOrder(order, total, shipping);
    notifyCustomer(order);
}

function canProcessOrder(order: Order): boolean {
    return order.status === 'pending' && order.items.length > 0;
}

function calculateOrderTotal(order: Order): number {
    return order.items
        .filter(item => item.inStock)
        .reduce((total, item) => total + calculateItemTotal(item), 0);
}

function calculateItemTotal(item: OrderItem): number {
    const subtotal = item.price * item.quantity;
    return subtotal - (item.discount ?? 0);
}

function calculateShipping(total: number): number {
    return total > 100 ? 0 : 10;
}
```

---

### 3. Don't Repeat Yourself (DRY)

Apply the DRY principle: eliminate duplicated code by extracting common behaviors into reusable abstractions.

✅ **ALWAYS**:
- Extract duplicated code into reusable functions or classes
- Create shared utilities for common operations
- Use inheritance or composition to share behavior
- Identify patterns and create abstractions
- Refactor when you see the same logic in multiple places

❌ **NEVER**:
- Copy-paste code blocks with minor variations
- Write the same business logic in multiple places
- Ignore repeated patterns that could be abstracted
- Create premature abstractions before seeing the pattern at least 2-3 times

**Examples**:

```php
// ❌ BAD: Duplicated validation logic
class CreateUserController
{
    public function __invoke(Request $request)
    {
        if (empty($request->email)) {
            throw new ValidationException('Email is required');
        }
        if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid email format');
        }
        // ... create user
    }
}

class UpdateUserController
{
    public function __invoke(Request $request, User $user)
    {
        if (empty($request->email)) {
            throw new ValidationException('Email is required');
        }
        if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid email format');
        }
        // ... update user
    }
}

// ✅ GOOD: Extracted validation
class EmailValidator
{
    public static function validate(?string $email): void
    {
        if (empty($email)) {
            throw new ValidationException('Email is required');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid email format');
        }
    }
}

class CreateUserController
{
    public function __invoke(Request $request)
    {
        EmailValidator::validate($request->email);
        // ... create user
    }
}

class UpdateUserController
{
    public function __invoke(Request $request, User $user)
    {
        EmailValidator::validate($request->email);
        // ... update user
    }
}
```

---

### 4. Simplicity Over Cleverness

Prefer simplicity and clarity over cleverness; straightforward solutions are easier to maintain and debug.

✅ **ALWAYS**:
- Write code that is obvious and easy to understand
- Use simple, direct solutions over complex clever ones
- Favor readability over brevity when they conflict
- Use language features appropriately, not to show off
- Write code that junior developers can understand and maintain

❌ **NEVER**:
- Write overly clever code that requires mental gymnastics to understand
- Use obscure language features when simpler alternatives exist
- Optimize prematurely at the cost of readability
- Chain too many operations in a single statement
- Use regex when simple string operations would suffice

**Examples**:

```typescript
// ❌ BAD: Too clever, hard to understand
const result = data.reduce((a, b) => ({...a, [b.k]: b.v}), {});

// ✅ GOOD: Clear and simple
const result: Record<string, any> = {};
for (const item of data) {
    result[item.key] = item.value;
}

// ❌ BAD: Unnecessary complexity
const isValid = !!(value && value.trim() && value.length >= 3);

// ✅ GOOD: Clear boolean logic
const isValid = value !== null &&
                value.trim().length >= 3;

// ❌ BAD: Clever but confusing
const x = y || z && a || b;

// ✅ GOOD: Explicit and clear
let x;
if (y) {
    x = y;
} else if (z && a) {
    x = a;
} else {
    x = b;
}
```

---

### 5. Strategic Comments

Use comments sparingly, reserving them for explaining why non-obvious decisions were made, while striving to make the code itself so clear that explanatory comments are rarely needed.

✅ **ALWAYS**:
- Write self-documenting code with clear names and structure
- Add comments to explain WHY, not WHAT
- Document non-obvious business rules or constraints
- Explain workarounds for bugs in external libraries
- Add context for complex algorithms or unusual approaches
- Keep comments up-to-date when code changes

❌ **NEVER**:
- Write comments that simply restate what the code does
- Leave commented-out code in the codebase
- Use comments to explain poorly written code (refactor instead)
- Write misleading or outdated comments
- Add noise comments that don't provide value

**Examples**:

```php
// ❌ BAD: Redundant comments
// Get the user by ID
$user = User::find($id);

// Check if user exists
if ($user) {
    // Update the user's name
    $user->name = $newName;
    // Save the user
    $user->save();
}

// ✅ GOOD: Code is self-documenting, comment explains WHY
$user = User::find($id);

if ($user) {
    $user->name = $newName;
    $user->save();
}

// ❌ BAD: Commented-out code
// $oldCalculation = $price * 0.8;
// $discount = $price - $oldCalculation;
$discount = $price * 0.2;

// ✅ GOOD: Comment explains business decision
// Use 20% discount rate as per Q4 2024 marketing campaign
// See: TICKET-1234 for business justification
$discountRate = 0.2;
$discount = $price * $discountRate;

// ✅ GOOD: Explaining non-obvious workaround
// Workaround for Stripe API bug where refunds fail silently
// when amount is exactly $0.00. Issue tracked: stripe/stripe-php#1234
if ($refundAmount > 0) {
    $stripe->refunds->create(['amount' => $refundAmount]);
}
```

---

## Summary

When writing code, always ask yourself:

1. **Can someone else understand this in 6 months?** (including future you)
2. **Does this function do exactly one thing?**
3. **Have I seen this pattern before in this codebase?** (if yes, extract it)
4. **Is this the simplest solution that works?**
5. **Does my code explain itself, or do I need comments to clarify?**

Clean code is not about perfection—it's about care, clarity, and respect for the humans who will read and maintain it.
