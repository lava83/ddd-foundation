<?php

declare(strict_types=1);

namespace Lava83\DddFoundation\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Carbon;
use Lava83\DddFoundation\Infrastructure\Models\Concerns\HasUuids;

/**
 * @property int $version
 *
 * @property-read Carbon $created_at
 * @property-read ?Carbon $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Model newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Model newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Model query()
 */
abstract class Model extends EloquentModel
{
    use HasUuids;

    public function getFillable(): array
    {
        return array_merge(['id', 'version', 'created_at', 'updated_at'], $this->fillable);
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'version' => 'integer',
        ];
    }
}
