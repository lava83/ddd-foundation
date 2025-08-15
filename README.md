# DDD Foundation

A robust Domain-Driven Design foundation package for Laravel applications, providing essential building blocks including aggregate roots, domain events, value objects, and shared contracts for clean domain modeling.

## Badges

[![Tests](https://github.com/lava83/ddd-foundation/actions/workflows/tests.yml/badge.svg)](https://github.com/lava83/ddd-foundation/actions/workflows/tests.yml)
[![PHPStan](https://github.com/lava83/ddd-foundation/actions/workflows/phpstan.yml/badge.svg)](https://github.com/lava83/ddd-foundation/actions/workflows/phpstan.yml)
[![Latest Stable Version](https://poser.pugx.org/lava83/ddd-foundation/v/stable)](https://packagist.org/packages/lava83/ddd-foundation)
[![License](https://poser.pugx.org/lava83/ddd-foundation/license)](https://packagist.org/packages/lava83/ddd-foundation)

## Overview

This package provides foundational building blocks for implementing Domain-Driven Design (DDD) patterns in Laravel 12+ applications with strict layer separation and API-first architecture.

### Key Features

- **Clean Architecture**: Strict DDD layer separation with minimal framework coupling
- **Aggregate Roots**: Complete implementation with domain event handling
- **Rich Value Objects**: Type-safe value objects with business validation (Id, Email, Money, DateRange)
- **Entity Mappers**: Seamless domain ↔ infrastructure transformation
- **Optimistic Locking**: Built-in concurrency control with versioning
- **Domain Events**: Event sourcing with automatic publishing
- **Repository Pattern**: Clean data access abstraction
- **Enterprise Validation**: Business rule enforcement in value objects

## Architecture

```
src/
├── Domain/
│   └── Shared/
│       ├── Contracts/          # Domain interfaces
│       │   ├── DomainEvent.php
│       │   └── Repository.php
│       ├── Entities/           # Domain entities
│       │   ├── BaseEntity.php
│       │   └── BaseAggregateRoot.php
│       ├── Events/             # Domain events
│       │   └── BaseDomainEvent.php
│       ├── Exceptions/         # Domain exceptions
│       │   └── ValidationException.php
│       └── ValueObjects/       # Value objects
│           ├── Id.php
│           ├── Email.php
│           ├── Money.php
│           └── DateRange.php
└── Infrastructure/
    ├── Contracts/              # Infrastructure interfaces
    │   └── EntityMapper.php
    ├── Models/                 # Eloquent models
    │   ├── BaseModel.php
    │   └── Concerns/
    │       └── HasUuids.php
    ├── Repositories/           # Repository implementations
    │   └── Repository.php
    ├── Mappers/                # Entity ↔ Model mappers
    │   └── EntityMapperResolver.php
    ├── Services/               # Infrastructure services
    │   └── DomainEventPublisher.php
    └── Exceptions/             # Infrastructure exceptions
        ├── CantSaveModel.php
        └── ConcurrencyException.php
```

## Prerequisites

- PHP 8.2 or later
- Laravel 12.22 or later
- Composer

## Installation

### Step 1: Install Laravel Installer

```bash
composer global require laravel/installer
```

### Step 2: Create New Laravel Project

```bash
laravel new your-project-name
cd your-project-name
```

### Step 3: Install DDD Foundation Package

```bash
composer require lava83/ddd-foundation
```

### Step 4: Register Package Services

Create a DDD service provider:

```bash
php artisan make:provider DddServiceProvider
```

Configure your service provider:

```php
<?php
// app/Providers/DddServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Lava83\DddFoundation\Infrastructure\Mappers\EntityMapperResolver;
use Lava83\DddFoundation\Infrastructure\Contracts\EntityMapper;

class DddServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register entity mappers
        $this->app->singleton(EntityMapperResolver::class, function () {
            return new class extends EntityMapperResolver {
                public function resolve(string $entityClass): EntityMapper
                {
                    return match($entityClass) {
                        // Register your entity mappers here
                        default => throw new \InvalidArgumentException("No mapper found for {$entityClass}"),
                    };
                }
            };
        });

        // Register repository interfaces
        // $this->app->bind(YourRepositoryInterface::class, YourRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
```

Register the service provider in `config/app.php`:

```php
'providers' => [
    // ... other providers
    App\Providers\DddServiceProvider::class,
],
```

## Usage

### Creating Entities

```php
<?php

namespace App\Domain\User\Entities;

use Lava83\DddFoundation\Domain\Shared\Entities\BaseEntity;
use Lava83\DddFoundation\Domain\Shared\ValueObjects\Id;

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

    // ... other methods
}
```

### Creating Aggregate Roots

```php
<?php

namespace App\Domain\Order\Entities;

use Lava83\DddFoundation\Domain\Shared\Entities\BaseAggregateRoot;
use Lava83\DddFoundation\Domain\Shared\ValueObjects\Id;

class Order extends BaseAggregateRoot
{
    public function __construct(
        private Id $id,
        private Id $customerId,
        private array $items = [],
    ) {
        parent::__construct();
        $this->recordEvent(new OrderCreated($this->id, $this->customerId));
    }

    public function addItem(OrderItem $item): void
    {
        $this->items[] = $item;
        $this->recordEvent(new OrderItemAdded($this->id, $item));
        $this->touch();
    }

    // ... other methods
}
```

### Working with Value Objects

#### Id Value Objects

```php
use Lava83\DddFoundation\Domain\Shared\ValueObjects\Id;

// Custom ID with prefix
class OrderId extends Id
{
    protected string $prefix = 'order';
}

// Usage
$orderId = OrderId::generate(); // Creates UUID v7
$customId = OrderId::fromString('01234567-89ab-cdef-0123-456789abcdef');
```

#### Email Value Objects

```php
use Lava83\DddFoundation\Domain\Shared\ValueObjects\Email;

$email = Email::fromString('user@example.com');

// Access parts
echo $email->localPart(); // 'user'
echo $email->domain(); // 'example.com'

// Business methods
if ($email->isValidForNotifications()) {
    // Send notifications
}
```

#### Money Value Objects

```php
use Lava83\DddFoundation\Domain\Shared\ValueObjects\Money;

$price = Money::fromAmount(1000, 'USD'); // $10.00 USD
$discount = Money::fromAmount(200, 'USD');

$total = $price->subtract($discount); // $8.00 USD
$doubled = $price->multiply(2); // $20.00 USD
```

#### DateRange Value Objects

```php
use Lava83\DddFoundation\Domain\Shared\ValueObjects\DateRange;

$campaign = DateRange::fromString('2025-01-01', '2025-12-31');
$week = DateRange::currentWeek();

if ($campaign->contains(now())) {
    echo "Campaign is active!";
}

echo $campaign->durationInDays(); // 365
```

### Entity Mappers

```php
<?php

namespace App\Infrastructure\Mappers;

use Illuminate\Database\Eloquent\Model;
use Lava83\DddFoundation\Infrastructure\Contracts\EntityMapper;
use Lava83\DddFoundation\Domain\Shared\Entities\BaseAggregateRoot;
use Lava83\DddFoundation\Infrastructure\Models\BaseModel;

class OrderMapper implements EntityMapper
{
    public static function toEntity(Model $model, bool $deep = false): BaseAggregateRoot
    {
        $order = new Order(
            OrderId::fromString($model->id),
            CustomerId::fromString($model->customer_id)
        );

        // Restore entity state
        $order->hydrate([
            'created_at' => $model->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $model->updated_at?->format('Y-m-d H:i:s'),
            'version' => $model->version,
        ]);

        return $order;
    }

    public static function toModel(BaseAggregateRoot $entity): BaseModel
    {
        $model = new OrderModel();
        $model->id = $entity->id()->value();
        $model->customer_id = $entity->customerId()->value();
        $model->status = $entity->status();
        $model->version = $entity->version();

        return $model;
    }
}
```

### Repository Implementation

```php
<?php

namespace App\Infrastructure\Repositories;

use Lava83\DddFoundation\Infrastructure\Repositories\Repository as BaseRepository;
use App\Domain\Order\Contracts\OrderRepositoryInterface;

class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
    public function save(Order $order): void
    {
        $this->saveEntity($order); // Handles events and optimistic locking
    }

    public function findById(OrderId $id): ?Order
    {
        $model = OrderModel::find($id->value());
        
        if (!$model) {
            return null;
        }

        return OrderMapper::toEntity($model);
    }

    public function nextId(): OrderId
    {
        return OrderId::generate();
    }

    // ... other repository methods
}
```

### Domain Events

```php
<?php

namespace App\Domain\Order\Events;

use Lava83\DddFoundation\Domain\Shared\Events\BaseDomainEvent;

class OrderCompleted extends BaseDomainEvent
{
    public function __construct(
        private OrderId $orderId,
    ) {
        parent::__construct();
    }

    public function aggregateId(): OrderId
    {
        return $this->orderId;
    }

    public function eventType(): string
    {
        return 'order.completed';
    }

    public function eventData(): array
    {
        return [
            'order_id' => $this->orderId->value(),
        ];
    }
}
```

### Event Listeners

```php
<?php
// app/Providers/EventServiceProvider.php

protected $listen = [
    OrderCompleted::class => [
        SendOrderConfirmationEmail::class,
        UpdateCustomerStatistics::class,
    ],
];
```

## Database Setup

### Migration Example

```php
Schema::create('orders', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('customer_id');
    $table->string('status');
    $table->json('items');
    $table->integer('version')->default(0); // For optimistic locking
    $table->timestamps();
    
    $table->index(['customer_id']);
    $table->index(['status']);
});
```

### Model Example

```php
<?php

namespace App\Infrastructure\Models;

use Lava83\DddFoundation\Infrastructure\Models\BaseModel;

class OrderModel extends BaseModel
{
    protected $table = 'orders';

    protected $fillable = [
        'customer_id',
        'status',
        'items',
    ];

    public function casts(): array
    {
        return array_merge(parent::casts(), [
            'customer_id' => 'uuid',
            'items' => 'array',
        ]);
    }
}
```

## Framework Integration

### Service Provider Registration

Register your entity mappers in the DddServiceProvider:

```php
$this->app->singleton(EntityMapperResolver::class, function () {
    return new class extends EntityMapperResolver {
        public function resolve(string $entityClass): EntityMapper
        {
            return match($entityClass) {
                Order::class => new OrderMapper(),
                User::class => new UserMapper(),
                Product::class => new ProductMapper(),
                default => throw new \InvalidArgumentException("No mapper found for {$entityClass}"),
            };
        }
    };
});
```

### Repository Binding

```php
$this->app->bind(
    \App\Domain\Order\Contracts\OrderRepositoryInterface::class,
    \App\Infrastructure\Repositories\OrderRepository::class
);
```

## Testing

### Unit Tests

```php
<?php

namespace Tests\Domain\Order;

use Tests\TestCase;
use App\Domain\Order\Entities\Order;

class OrderTest extends TestCase
{
    public function test_can_create_order(): void
    {
        $order = new Order(OrderId::generate(), CustomerId::generate());
        
        $this->assertEquals('pending', $order->status());
        $this->assertTrue($order->isEmpty());
    }

    public function test_can_add_items(): void
    {
        $order = new Order(OrderId::generate(), CustomerId::generate());
        $item = new OrderItem(ProductId::generate(), 'Product', 2, new Money(1000, 'USD'));
        
        $order->addItem($item);
        
        $this->assertFalse($order->isEmpty());
        $this->assertEquals(2000, $order->calculateTotal()->amount);
    }
}
```

### Feature Tests

```php
<?php

namespace Tests\Feature\Order;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_order_via_api(): void
    {
        $customer = CustomerModel::factory()->create();
        $product = ProductModel::factory()->create(['stock_quantity' => 10]);

        $response = $this->postJson('/api/orders', [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('orders', ['customer_id' => $customer->id]);
    }
}
```

## Development

### Code Quality Tools

```bash
# Static analysis
composer stan
./vendor/bin/phpstan analyse

# Code formatting
composer pint-fix
./vendor/bin/pint

# Run tests
composer test
./vendor/bin/pest
```

### Build Scripts

```json
{
    "scripts": {
        "stan": [
            "./vendor/bin/phpstan analyse --memory-limit=5G --configuration=phpstan.neon --no-interaction"
        ],
        "pint": [
            "./vendor/bin/pint --test"
        ],
        "pint-fix": [
            "./vendor/bin/pint"
        ],
        "test": [
            "./vendor/bin/pest"
        ]
    }
}
```

## Configuration

### PHPStan Configuration

```yaml
# phpstan.neon
includes:
    - vendor/phpstan/phpstan/conf/bleedingEdge.neon
    - vendor/phpstan/phpstan-deprecation-rules/rules.neon
parameters:
    level: 8
    phpVersion: 80200
    paths:
        - src
    reportUnmatchedIgnoredErrors: false
```

## Best Practices

### Domain Layer Guidelines

- **Minimal Laravel Dependencies**: Only use Laravel helpers like `collect()`, `str()`
- **Pure Business Logic**: No infrastructure concerns in domain layer
- **Rich Domain Models**: Entities should contain business behavior
- **Value Objects**: Use for all domain concepts (IDs, emails, money)
- **Domain Events**: Record all significant business events

### Infrastructure Layer Guidelines

- **Mappers**: Always use entity mappers for domain ↔ model transformation
- **Repository Pattern**: Implement repository contracts in infrastructure
- **Event Publishing**: Let the base repository handle event publishing
- **Optimistic Locking**: Always check version in mappers

### API-First Architecture

- **Thin Controllers**: Controllers should only orchestrate use cases
- **Use Cases**: Business logic goes in application services
- **DTOs**: Use Data Transfer Objects for API request/response
- **Validation**: Business validation in domain, input validation in requests

## Troubleshooting

### Common Issues

**"No mapper found for entity"**
```php
// Solution: Register mapper in DddServiceProvider
return match($entityClass) {
    YourEntity::class => new YourMapper(),
    // ...
};
```

**"Prefixed ID must contain underscore separator"**
```php
// Solution: Set prefix in ID value objects
class OrderId extends Id
{
    protected string $prefix = 'order';
}
```

**Domain events not firing**
- Register listeners in `EventServiceProvider`
- Save through repository (which publishes events)
- Ensure events extend `BaseDomainEvent`

**Concurrency Exception on save**
```php
// This is optimistic locking working correctly
try {
    $repository->save($entity);
} catch (ConcurrencyException $e) {
    // Handle conflict appropriately
}
```

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Follow DDD principles and existing code style
4. Add tests for new functionality
5. Run quality checks (`composer stan && composer pint`)
6. Commit your changes (`git commit -m 'Add amazing feature'`)
7. Push to the branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Support

- **Issues**: [GitHub Issues](https://github.com/lava83/ddd-foundation/issues)
- **Documentation**: [Full documentation and examples](docs/)

---

**Built for enterprise Laravel applications following Domain-Driven Design principles.**