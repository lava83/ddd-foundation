<?php

declare(strict_types=1);

namespace Lava83\DddFoundation\Infrastructure\Contracts;

use Illuminate\Database\Eloquent\Model;
use Lava83\DddFoundation\Domain\Shared\Entities\BaseAggregateRoot;
use Lava83\DddFoundation\Infrastructure\Models\Model;

interface EntityMapper
{
    public static function toEntity(Model $model, bool $deep = false): BaseAggregateRoot;

    public static function toModel(BaseAggregateRoot $entity): Model;
}
