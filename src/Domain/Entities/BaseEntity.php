<?php

declare(strict_types=1);

namespace Lava83\DddFoundation\Domain\Entities;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Lava83\DddFoundation\Domain\ValueObjects\Identity\Id;

/**
 * Base class for all entities (both aggregate roots and child entities)
 * Contains common entity functionality without domain event handling
 */
abstract class BaseEntity
{
    public function __construct(
        protected CarbonImmutable $createdAt = new CarbonImmutable,
        protected ?CarbonImmutable $updatedAt = null,
        protected int $version = 0,
    ) {}

    /**
     * Get the entity's unique identifier
     * Must be implemented by concrete entities
     */
    abstract public function id(): Id;

    /**
     * Compare entities by ID for equality
     */
    public function equals(BaseEntity $other): bool
    {
        if (get_class($this) !== get_class($other)) {
            return false;
        }

        return $this->id()->equals($other->id());
    }

    /**
     * Timestamps Management
     */
    public function createdAt(): CarbonImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): CarbonImmutable
    {
        return $this->updatedAt ?? CarbonImmutable::now();
    }

    protected function touch(): void
    {
        $this->updatedAt = CarbonImmutable::now();
        $this->version++;
    }

    /**
     * Optimistic Locking Support
     */
    public function version(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): void
    {
        $this->version = $version;
    }

    /**
     * Hydration from persistence layer
     * Called when reconstituting from database
     *
     * @param  array{created_at: string, updated_at: ?string, version: int}  $data
     */
    public function hydrate(array $data): void
    {
        $this->createdAt = new CarbonImmutable((string) $data['created_at']);
        $this->updatedAt = isset($data['updated_at']) ? new CarbonImmutable((string) $data['updated_at']) : null;
        $this->version = (int) $data['version'];
    }

    /**
     * Convert entity to array for persistence
     *
     * @return array<string, int|string|null>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id()->value(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'version' => $this->version,
        ];
    }

    /**
     * Clone protection - entities should not be cloned carelessly
     */
    protected function __clone()
    {
        // Keep the same timestamps and version on clone
        // Child classes can override this behavior
    }

    /**
     * String representation for debugging
     */
    public function __toString(): string
    {
        return sprintf(
            '%s[id=%s, version=%d]',
            static::class,
            $this->id()->value(),
            $this->version
        );
    }

    /**
     * Helper method for child entities to update themselves
     */
    protected function updateEntity(): void
    {
        $this->touch();
    }

    /**
     * Check if entity was recently created (within last minute)
     */
    public function isRecentlyCreated(): bool
    {
        $oneMinuteAgo = CarbonImmutable::now()->subMinute();

        return $this->createdAt >= $oneMinuteAgo;
    }

    /**
     * Check if entity was recently updated (within last minute)
     */
    public function isRecentlyUpdated(): bool
    {
        if (! $this->updatedAt) {
            return false;
        }

        $oneMinuteAgo = CarbonImmutable::now()->subMinute();

        return $this->updatedAt >= $oneMinuteAgo;
    }

    /**
     * Get entity age in seconds
     */
    public function ageInSeconds(): int
    {
        return CarbonImmutable::now()->getTimestamp() - $this->createdAt->getTimestamp();
    }

    /**
     * Check if entity is older than specified duration
     */
    public function isOlderThan(string $duration): bool
    {
        $threshold = CarbonImmutable::now()->sub($duration);

        return $this->createdAt < $threshold;
    }

    /**
     * Validate entity state
     * Override in child classes for specific validation
     *
     * @return array<string>
     */
    public function validate(): array
    {
        $errors = [];

        if (! $this->id()->value()) {
            $errors[] = 'Entity must have an ID';
        }

        return $errors;
    }

    /**
     * Check if entity is valid
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    /**
     * Get entity metadata for auditing
     *
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return [
            'entity_type' => static::class,
            'entity_id' => $this->id()->value(),
            'version' => $this->version,
            'created_at' => $this->createdAt()->format(DateTimeImmutable::ATOM),
            'updated_at' => $this->updatedAt()->format(DateTimeImmutable::ATOM),
            'age_seconds' => $this->ageInSeconds(),
        ];
    }
}
