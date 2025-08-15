<?php

declare(strict_types=1);

namespace Lava83\DddFoundation\Infrastructure\Mappers;

use Lava83\DddFoundation\Infrastructure\Contracts\EntityMapper;

abstract class EntityMapperResolver
{
    abstract public function resolve(string $entityClass): EntityMapper;
}
