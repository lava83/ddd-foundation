<?php

declare(strict_types=1);

namespace Lava83\DddFoundation\Infrastructure\DTO;

abstract class Data
{
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
