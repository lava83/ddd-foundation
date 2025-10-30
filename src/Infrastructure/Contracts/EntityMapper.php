<?php

declare(strict_types=1);

namespace Lava83\DddFoundation\Infrastructure\Contracts;

use Illuminate\Database\Eloquent\Model;
use Lava83\DddFoundation\Domain\Entities\Entity;

interface EntityMapper
{
    public static function toEntity(Model $model, bool $deep = false): Entity;

    public static function toModel(Entity $entity): Model;
}
