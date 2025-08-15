<?php

declare(strict_types=1);

namespace Lava83\DddFoundation\Infrastructure\Repositories;

use Illuminate\Database\Eloquent\Model;
use Lava83\DddFoundation\Domain\Shared\Entities\BaseAggregateRoot;
use Lava83\DddFoundation\Domain\Shared\Entities\BaseEntity;
use Lava83\DddFoundation\Infrastructure\Exceptions\CantSaveModel;
use Lava83\DddFoundation\Infrastructure\Exceptions\ConcurrencyException;
use Lava83\DddFoundation\Infrastructure\Mappers\EntityMapperResolver;
use Lava83\DddFoundation\Infrastructure\Models\BaseModel;
use Lava83\DddFoundation\Infrastructure\Services\DomainEventPublisher;

class Repository
{
    protected function saveEntity(BaseAggregateRoot $entity): Model
    {
        $model = app(EntityMapperResolver::class)->resolve($entity::class)->toModel($entity);

        if ($model->exists) {
            $this->handleOptimisticLocking($model, $entity);
        }

        if (! $model->save()) {
            throw new CantSaveModel('Failed to save entity');
        }

        $this->dispatchUncommittedEvents($entity);
        $this->syncEntityFromModel($entity, $model);

        return $model;
    }

    protected function dispatchUncommittedEvents(BaseAggregateRoot $entity): void
    {
        if ($entity->hasUncommittedEvents()) {
            app(DomainEventPublisher::class)->publishEvents($entity->uncommittedEvents());
            $entity->markEventsAsCommitted();
        }
    }

    protected function handleOptimisticLocking(BaseModel $model, BaseEntity $entity): void
    {
        if ($model->version !== $entity->version()) {
            throw new ConcurrencyException(
                "Employee {$entity->id()->value()} was modified by another process. ".
                "Expected version: {$entity->version()}, ".
                "Actual version: {$model->version}"
            );
        }

        // Increment version for the update
        $model->version = $entity->version() + 1;
    }

    protected function syncEntityFromModel(BaseEntity $entity, BaseModel $model): void
    {
        // Update entity with final database values
        $entity->hydrate([
            'created_at' => $model->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $model->updated_at?->format('Y-m-d H:i:s'),
            'version' => $model->version,
        ]);
    }
}
