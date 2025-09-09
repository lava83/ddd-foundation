<?php

declare(strict_types=1);

namespace Lava83\DddFoundation\Infrastructure\Repositories;

use Lava83\DddFoundation\Domain\Entities\Aggregate;
use Lava83\DddFoundation\Domain\Entities\Entity;
use Lava83\DddFoundation\Infrastructure\Contracts\EntityMapper;
use Lava83\DddFoundation\Infrastructure\Contracts\EntityMapperResolver;
use Lava83\DddFoundation\Infrastructure\Exceptions\CantSaveModel;
use Lava83\DddFoundation\Infrastructure\Exceptions\ConcurrencyException;
use Lava83\DddFoundation\Infrastructure\Models\Model;
use Lava83\DddFoundation\Infrastructure\Services\DomainEventPublisher;

abstract class Repository
{
    /**
     * @property class-string<Aggregate> $aggregateClass
     */
    protected string $aggregateClass;

    public function __construct(private EntityMapperResolver $mapperResolver)
    {
        // @todo implement ensuring of aggregate class being set and is a subclass of Aggregate
    }

    protected function entityMapper(): EntityMapper
    {
        return $this->mapperResolver->resolve($this->aggregateClass);
    }

    protected function saveEntity(Entity|Aggregate $entity): Model
    {
        $model = $this->mapperResolver->resolve($entity::class)->toModel($entity);

        if (
            $entity->isDirty()
            || $model->exists === false
        ) {
            $this->persistDirtyEntity($entity, $model);
        }

        $this->syncEntityFromModel($entity, $model);

        return $model;
    }

    protected function dispatchUncommittedEvents(Aggregate $entity): void
    {
        if ($entity->hasUncommittedEvents()) {
            app(DomainEventPublisher::class)->publishEvents($entity->uncommittedEvents());
            $entity->markEventsAsCommitted();
        }
    }

    protected function handleOptimisticLocking(Model $model, Entity $entity): void
    {
        $expectedDatabaseVersion = $entity->version();

        if ($model->version !== $expectedDatabaseVersion) {
            throw new ConcurrencyException(
                "Entity {$entity->id()->value()} was modified by another process. ".
                "Expected version: {$expectedDatabaseVersion}, ".
                "Actual version: {$model->version}"
            );
        }
    }

    protected function syncEntityFromModel(Entity $entity, Model $model): void
    {
        // Update entity with final database values
        $entity->hydrate($model);
    }

    private function persistDirtyEntity(Entity|Aggregate $entity, Model $model): void
    {
        if ($model->exists) {
            $this->handleOptimisticLocking($model, $entity);
        }

        if (! $model->save()) {
            throw new CantSaveModel('Failed to save entity');
        }

        if ($entity instanceof Aggregate) {
            $this->dispatchUncommittedEvents($entity);
        }
    }
}
