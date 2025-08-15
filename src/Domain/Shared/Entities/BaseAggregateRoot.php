<?php

declare(strict_types=1);

namespace Lava83\DddFoundation\Domain\Shared\Entities;

use DateTimeImmutable;
use Illuminate\Support\Collection;
use Lava83\DddFoundation\Domain\Shared\Contracts\AggregateRoot;
use Lava83\DddFoundation\Domain\Shared\Contracts\DomainEvent;

/**
 * Base class for Aggregate Root entities
 * Extends BaseEntity and adds domain event handling
 */
abstract class BaseAggregateRoot extends BaseEntity implements AggregateRoot
{
    /**
     * @param  Collection<int, DomainEvent>  $domainEvents
     */
    public function __construct(
        private Collection $domainEvents = new Collection,
    ) {
        parent::__construct();
    }

    /**
     * Domain Events Management
     */
    public function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * @return Collection<int, DomainEvent>
     */
    public function uncommittedEvents(): Collection
    {
        // Return a copy of the events to prevent external modification
        return $this->domainEvents->map(fn (DomainEvent $event) => clone $event);
    }

    public function markEventsAsCommitted(): void
    {
        $this->domainEvents = new Collection;
    }

    public function hasUncommittedEvents(): bool
    {
        return ! $this->domainEvents->isEmpty();
    }

    /**
     * Override clone to reset events
     */
    public function __clone()
    {
        parent::__clone();
        // Reset events on clone to prevent event duplication
        // @todo make method resetEvents()
        // or similar to reset events in a more generic way
        // in case we want to clone an aggregate root with events
        // that should not be cloned
        $this->domainEvents = new Collection;
    }

    /**
     * Serialization control - don't serialize uncommitted events
     */
    public function __sleep(): array
    {
        $vars = array_keys(get_object_vars($this));

        return array_diff($vars, ['domainEvents']);
    }

    public function __wakeup(): void
    {
        // Reset events array after unserialization
        $this->domainEvents = new Collection;
    }

    /**
     * Enhanced metadata for aggregate roots
     */
    public function metadata(): array
    {
        return array_merge(parent::metadata(), [
            'is_aggregate_root' => true,
            'has_uncommitted_events' => $this->hasUncommittedEvents(),
            'uncommitted_events_count' => count($this->domainEvents),
            'version' => $this->version(),
            'created_at' => $this->createdAt()->format(DateTimeImmutable::ATOM),
            'updated_at' => $this->updatedAt()->format(DateTimeImmutable::ATOM),
            'class' => static::class,
        ]);
    }

    /**
     * Helper method for aggregate roots to update and record change event
     */
    protected function updateAggregateRoot(?DomainEvent $event = null): void
    {
        $this->updateEntity(); // Update timestamps and version

        if ($event) {
            $this->recordEvent($event);
        }
    }

    /**
     * Get summary of uncommitted events for debugging
     *
     * @return Collection<int, array<string, string>>
     */
    public function eventSummary(): Collection
    {
        return $this->domainEvents->map(function (DomainEvent $event) {
            return [
                'event_name' => $event->eventName(),
                'aggregate_id' => $event->aggregateId()->value(),
                'occurred_on' => $event->occurredOn()->format(DateTimeImmutable::ATOM),
            ];
        });
    }

    public function eventByType(string $eventName): ?DomainEvent
    {
        return $this->domainEvents->first(fn (DomainEvent $event) => $event->eventName() === $eventName) ?? null;
    }

    /**
     * Clear specific event types (useful for testing)
     */
    public function clearEventsOfType(string $eventName): void
    {
        $this->domainEvents = $this->domainEvents->filter(fn (DomainEvent $event) => $event->eventName() !== $eventName);
    }

    /**
     * Count events of specific type
     */
    public function countEventsOfType(string $eventName): int
    {
        return $this->domainEvents->filter(fn (DomainEvent $event) => $event->eventName() === $eventName)->count();
    }
}
