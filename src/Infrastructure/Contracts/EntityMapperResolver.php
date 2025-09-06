<?php

declare(strict_types=1);

namespace Lava83\DddFoundation\Infrastructure\Contracts;

interface EntityMapperResolver
{
    public function resolve(string $entityClass): EntityMapper;
}
