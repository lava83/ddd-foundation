<?php

declare(strict_types=1);

namespace Lava83\DddFoundation\Infrastructure\Mappers;

use Illuminate\Database\Eloquent\Model;
use Lava83\DddFoundation\Domain\Entities\Entity;
use Lava83\DddFoundation\Infrastructure\Contracts\EntityMapper as EntityMapperContract;

abstract class EntityMapper implements EntityMapperContract
{
    /**
     * @param  array<string, mixed>  $data
     */
    protected function findOrCreateModelFillData(Entity $entity, string $modelClass, array $data): Model
    {
        $model = app($modelClass)->findOr($entity->id(), ['*'], fn () => app($modelClass));

        $model->fill($data);

        return $model;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected function findOrCreateModel(Entity $entity, string $modelClass): Model
    {
        return app($modelClass)->findOr($entity->id(), ['*'], fn () => app($modelClass));
    }
}
