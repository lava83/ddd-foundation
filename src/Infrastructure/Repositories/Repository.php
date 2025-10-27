<?php

declare(strict_types=1);

namespace Lava83\DddFoundation\Infrastructure\Repositories;

use Illuminate\Support\Collection;
use Lava83\DddFoundation\Domain\Entities\Aggregate;
use Lava83\DddFoundation\Domain\Entities\Entity;
use Lava83\DddFoundation\Infrastructure\Contracts\EntityMapper;
use Lava83\DddFoundation\Infrastructure\Contracts\EntityMapperResolver;
use Lava83\DddFoundation\Infrastructure\Exceptions\CantDeleteModel;
use Lava83\DddFoundation\Infrastructure\Exceptions\CantDeleteRelatedModel;
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

    public function __construct(protected EntityMapperResolver $mapperResolver)
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

    protected function deleteEntity(Entity|Aggregate $entity): void
    {
        $model = $this->mapperResolver->resolve($entity::class)->toModel($entity);

        if (! $model->delete()) {
            throw new CantDeleteModel('Failed to delete entity');
        }
    }

    protected function deleteEntities(Collection $entities): void
    {
        $entities->each(fn (Entity $entity) => $this->deleteEntity($entity));
    }

    protected function deleteRelatedEntity(Entity|Aggregate $entity, string $relation, int|string $relatedId): void
    {
        $model = $this->mapperResolver->resolve($entity::class)->toModel($entity);

        $related = $model->$relation()->find($relatedId);

        if ($related instanceof Model) {
            if (! $related->delete()) {
                throw new CantDeleteRelatedModel("Failed to delete related entity via relation {$relation}");
            }
        } else {
            throw new CantDeleteRelatedModel("Relation {$relation} is not a valid Eloquent relation");
        }
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
