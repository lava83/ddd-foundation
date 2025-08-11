# DDD Foundation

A PHP package providing foundational building blocks for implementing Domain-Driven Design (DDD) patterns in your applications.

## Features

- **Base Entity Classes**: Foundation for domain entities with built-in timestamping and optimistic locking
- **Aggregate Root Pattern**: Complete implementation with domain event handling
- **Domain Events**: Record and manage domain events within aggregates
- **Repository Contracts**: Standard interfaces for data persistence
- **Value Objects**: Type-safe value object implementations
- **Event Management**: Built-in support for domain event sourcing

## Installation

```bash
composer require lava83/ddd-foundation
```

## Quick Start

### Creating an Entity

Extend `BaseEntity` for your domain entities:

```php
<?php

use Lava83\DddFoundation\Entities\BaseEntity;
use Lava83\DddFoundation\ValueObjects\Id;

class User extends BaseEntity
{
    public function __construct(
        private Id $id,
        private string $email,
        private string $name,
    ) {
        parent::__construct();
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function changeName(string $newName): void
    {
        $this->name = $newName;
        $this->touch(); // Updates version and timestamp
    }

    public function email(): string
    {
        return $this->email;
    }

    public function name(): string
    {
        return $this->name;
    }
}
```

### Creating an Aggregate Root

Extend `BaseAggregateRoot` for your aggregate roots:

```php
<?php

use Lava83\DddFoundation\Entities\BaseAggregateRoot;
use Lava83\DddFoundation\ValueObjects\Id;

class Order extends BaseAggregateRoot
{
    public function __construct(
        private Id $id,
        private Id $customerId,
        private array $items = [],
    ) {
        parent::__construct();
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function addItem(OrderItem $item): void
    {
        $this->items[] = $item;
        $this->touch();
        
        // Record domain event
        $this->recordEvent(new OrderItemAdded($this->id, $item));
    }

    public function complete(): void
    {
        // Business logic here
        $this->recordEvent(new OrderCompleted($this->id));
        $this->touch();
    }

    public function items(): array
    {
        return $this->items;
    }
}
```

### Implementing a Repository

Implement the `Repository` contract:

```php
<?php

use Illuminate\Support\Collection;
use Lava83\DddFoundation\Contracts\Repository;
use Lava83\DddFoundation\ValueObjects\Id;

class OrderRepository implements Repository
{
    public function __construct(
        private PDO $connection
    ) {}

    public function nextId(): Id
    {
        return Id::generate();
    }

    public function save(Order $order): void
    {
        // Persist the aggregate
        // Handle optimistic locking with version
        // Dispatch domain events
        
        $events = $order->uncommittedEvents();
        // ... save logic ...
        $order->markEventsAsCommitted();
        
        // Dispatch events to event handlers
        foreach ($events as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }

    public function findById(Id $id): ?Order
    {
        // Retrieve and reconstitute aggregate
        $data = $this->fetchFromDatabase($id);
        
        if (!$data) {
            return null;
        }

        $order = new Order($data['id'], $data['customer_id'], $data['items']);
        $order->hydrate($data); // Restore timestamps and version
        
        return $order;
    }

    public function exists(Id $id): bool
    {
        // Implementation
    }

    public function delete(Id $id): void
    {
        // Implementation
    }

    public function all(): Collection
    {
        // Implementation
    }

    public function count(): int
    {
        // Implementation
    }
}
```

### Working with Domain Events

Create domain events by implementing the `DomainEvent` contract:

```php
<?php

use Lava83\DddFoundation\Contracts\DomainEvent;
use Lava83\DddFoundation\ValueObjects\Id;

class OrderCompleted implements DomainEvent
{
    public function __construct(
        private Id $orderId,
        private DateTimeImmutable $occurredAt = new DateTimeImmutable(),
    ) {}

    public function aggregateId(): Id
    {
        return $this->orderId;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventType(): string
    {
        return 'order.completed';
    }
}
```

### Value Objects

Use the provided value objects for type safety:

```php
<?php

use Lava83\DddFoundation\ValueObjects\Id;
use Lava83\DddFoundation\ValueObjects\Email;
use Lava83\DddFoundation\ValueObjects\Money;
use Lava83\DddFoundation\ValueObjects\DateRange;

// Working with IDs
$userId = Id::generate(); // Generates UUID
$customId = Id::fromString('custom-id-123');

// Email validation
$email = Email::fromString('user@example.com'); // Validates format
echo $email->value(); // user@example.com

// Money calculations
$price = Money::fromAmount(1000, 'USD'); // $10.00 USD
$discount = Money::fromAmount(200, 'USD'); // $2.00 USD
$total = $price->subtract($discount); // $8.00 USD

// Date ranges
$campaign = DateRange::create(
    new DateTimeImmutable('2025-01-01'),
    new DateTimeImmutable('2025-12-31')
);

if ($campaign->contains(new DateTimeImmutable())) {
    echo "Campaign is active!";
}
```

## Entity Features

### Optimistic Locking

All entities support optimistic locking through versioning:

```php
$user = $userRepository->findById($id);
$originalVersion = $user->version();

$user->changeName('New Name');

// When saving, check if version hasn't changed
if ($user->version() !== $originalVersion + 1) {
    throw new ConcurrencyException('Entity was modified by another process');
}
```

### Timestamps

Entities automatically track creation and update times:

```php
$user = new User($id, 'john@example.com', 'John Doe');

echo $user->createdAt()->format('Y-m-d H:i:s');
echo $user->updatedAt()->format('Y-m-d H:i:s');

$user->changeName('Jane Doe'); // This calls touch() internally
// updatedAt is now updated automatically
```

### Entity Equality

Compare entities by their identity:

```php
$user1 = $userRepository->findById($id);
$user2 = $userRepository->findById($id);

if ($user1->equals($user2)) {
    echo "Same entity!";
}
```

## Event Management

Aggregate roots provide built-in event management:

```php
$order = new Order($id, $customerId);
$order->addItem($item); // Records OrderItemAdded event
$order->complete();     // Records OrderCompleted event

// Check for uncommitted events
if ($order->hasUncommittedEvents()) {
    $events = $order->uncommittedEvents();
    
    foreach ($events as $event) {
        $eventBus->publish($event);
    }
    
    // Mark events as processed
    $order->markEventsAsCommitted();
}
```

## Exception Handling

The package includes custom exceptions for domain validation:

```php
<?php

use Lava83\DddFoundation\Exceptions\ValidationException;

class User extends BaseEntity
{
    public function changeEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessage('Invalid email format');
        }
        
        $this->email = $email;
        $this->touch();
    }
}
```

## Architecture

This package follows Domain-Driven Design principles:

- **Entities**: Objects with identity that can change over time
- **Aggregate Roots**: Entities that serve as consistency boundaries
- **Value Objects**: Immutable objects representing descriptive aspects
- **Domain Events**: Capture important business events
- **Repositories**: Provide collection-like interface for aggregates

## Package Structure

```
src/
├── Contracts/           # Interfaces and contracts
│   ├── AggregateRoot.php
│   ├── DomainEvent.php
│   └── Repository.php
├── Entities/            # Base entity implementations
│   ├── BaseAggregateRoot.php
│   └── BaseEntity.php
├── Events/              # Event base classes
│   └── BaseDomainEvent.php
├── Exceptions/          # Domain exceptions
│   └── ValidationException.php
└── ValueObjects/        # Value object implementations
    ├── DateRange.php
    ├── Email.php
    ├── Id.php
    └── Money.php
```

## Requirements

- PHP 8.1 or higher
- Laravel Collections (illuminate/support)
- Carbon for date handling

## Testing

Run the test suite:

```bash
composer test
```

Run static analysis:

```bash
composer analyse
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License

This package is open-sourced software licensed under the MIT license.