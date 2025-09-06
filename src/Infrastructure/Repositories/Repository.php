<?php

declare(strict_types=1);

namespace Lava83\DddFoundation\Infrastructure\Repositories;

use Lava83\DddFoundation\Domain\Entities\Aggregate;
use Lava83\DddFoundation\Domain\Entities\Entity;
use Lava83\DddFoundation\Infrastructure\Exceptions\CantSaveModel;
use Lava83\DddFoundation\Infrastructure\Exceptions\ConcurrencyException;
use Lava83\DddFoundation\Infrastructure\Mappers\EntityMapperResolver;
use Lava83\DddFoundation\Infrastructure\Models\Model;
use Lava83\DddFoundation\Infrastructure\Services\DomainEventPublisher;
use LogicException;

abstract class Repository
{
    /**
     * @property class-string<Aggregate> $aggregate
     */
    protected string $aggregate;

    public function __construct(private EntityMapperResolver $mapperResolver)
    {
        if (! isset($this->aggregate) || ! is_subclass_of(app($this->aggregate), Aggregate::class)) {
            throw new LogicException('Repository must define a valid aggregate class');
        }
    }

    protected function mapperResolver(): EntityMapperResolver
    {
        return $this->mapperResolver;
    }

    protected function saveEntity(Entity|Aggregate $entity): Model
    {
        $model = $this->mapperResolver->resolve($entity::class)->toModel($entity);

        if ($model->exists) {
            $this->handleOptimisticLocking($model, $entity);
        }

        if (! $model->save()) {
            throw new CantSaveModel('Failed to save entity');
        }

        if ($entity instanceof Aggregate) {
            $this->dispatchUncommittedEvents($entity);
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
        if ($model->version !== $entity->version()) {
            throw new ConcurrencyException(
                "Entity {$entity->id()->value()} was modified by another process. ".
                "Expected version: {$entity->version()}, ".
                "Actual version: {$model->version}"
            );
        }
    }

    protected function syncEntityFromModel(Entity $entity, Model $model): void
    {
        // Update entity with final database values
        $entity->hydrate([
            'created_at' => $model->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $model->updated_at?->format('Y-m-d H:i:s'),
            'version' => $model->version,
        ]);
    }
}
