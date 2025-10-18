<?php

declare(strict_types=1);

namespace Lava83\DddFoundation\Infrastructure\DTO;

use Spatie\LaravelData\Data as SpatieData;

abstract class Data extends SpatieData
{
    /**
     * @return array<string, mixed>
     */
    abstract public function mapToPersistenceLayerArray(): array;
}
