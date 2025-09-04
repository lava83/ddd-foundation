<?php

namespace Lava83\DddFoundation\Application\Resources;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Exception
 */
class ErrorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'error' => $this->getMessage(),
            'code' => $this->getCode(),
        ];
    }
}
