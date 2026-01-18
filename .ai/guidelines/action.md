# Action Pattern Guidelines

## Overview

Use the **Action pattern** for all business logic. Actions are single-purpose, invokable classes that encapsulate one specific task.

## When to Use Actions

- Any business logic beyond simple CRUD
- Operations that could be reused across controllers, commands, jobs, or tests
- Complex workflows that benefit from clear naming and isolation

## Structure

```php
namespace App\Actions;

class CreateSubscription
{
    public function __construct(
        private StripeService $stripe,
        private NotificationService $notifications,
    ) {}

    public function __invoke(User $user, Plan $plan): Subscription
    {
        // Single responsibility: create a subscription
    }
}
```

## Rules

1. **One action, one task** - Name should describe exactly what it does
2. **Use `__invoke()`** - Makes the class callable: `app(CreateSubscription::class)($user, $plan)`
3. **Inject dependencies** - Use constructor injection for services
4. **Return meaningful values** - Return the result, not void when possible
5. **Keep controllers thin** - Controllers should delegate to actions immediately

## Naming Convention

- Use verb + noun: `CreateUser`, `SendInvoice`, `CalculateDiscount`
- Place in `app/Actions/` or `app/Actions/{Domain}/`

## Usage

```php
// In controller
public function store(Request $request, CreateSubscription $action)
{
    $subscription = $action($request->user(), $plan);

    return redirect()->route('subscriptions.show', $subscription);
}

// In test
$action = app(CreateSubscription::class);
$subscription = $action($user, $plan);
```

## Benefits

- Testable in isolation
- Reusable across the application
- Self-documenting through naming
- Easy to locate business logic
