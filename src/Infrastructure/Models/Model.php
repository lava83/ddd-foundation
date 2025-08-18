<?php

declare(strict_types=1);

namespace Lava83\DddFoundation\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Carbon;
use Lava83\DddFoundation\Infrastructure\Models\Concerns\HasUuids;

/**
 * @property int $version
 * @property-read Carbon $created_at
 * @property-read ?Carbon $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Model newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Model newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Model query()
 */
class Model extends EloquentModel
{
    use HasUuids;

    public function casts(): array
    {
        return [
            'id' => 'uuid',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'version' => 'integer',
        ];
    }
}
