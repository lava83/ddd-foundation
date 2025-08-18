<?php

declare(strict_types=1);

namespace Lava83\DddFoundation\Infrastructure\Contracts;

use Lava83\DddFoundation\Domain\Entities\Aggregate;
use Lava83\DddFoundation\Domain\Entities\Entity;
use Lava83\DddFoundation\Infrastructure\Models\Model;

interface EntityMapper
{
    public static function toEntity(Model $model, bool $deep = false): Aggregate;

    public static function toModel(Entity $entity): Model;
}
