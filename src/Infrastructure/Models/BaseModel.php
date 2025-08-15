<?php

declare(strict_types=1);

namespace Lava83\DddFoundation\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Lava83\DddFoundation\Infrastructure\Models\Concerns\HasUuids;

/**
 * @property int $version
 * @property-read Carbon $created_at
 * @property-read ?Carbon $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseModel query()
 */
class BaseModel extends Model
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
