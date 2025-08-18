<?php

declare(strict_types=1);

namespace Lava83\DddFoundation\Domain\Contracts;

use Illuminate\Support\Collection;
use Lava83\DddFoundation\Domain\Entities\BaseAggregateRoot;
use Lava83\DddFoundation\Domain\ValueObjects\Identity\Id;

interface Repository
{
    /**
     * Get next available ID for this entity type
     */
    public function nextId(): Id;

    /**
     * Check if an aggregate exists by ID
     */
    public function exists(Id $id): bool;

    /**
     * Delete an aggregate by ID
     */
    public function delete(Id $id): void;

    /**
     * Get all aggregates
     *
     * @return Collection<int, BaseAggregateRoot>
     */
    public function all(): Collection;

    public function count(): int;
}
