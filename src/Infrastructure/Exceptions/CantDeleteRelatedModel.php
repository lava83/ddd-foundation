<?php

declare(strict_types=1);

namespace Lava83\DddFoundation\Infrastructure\Exceptions;

use RuntimeException;
use Throwable;

class CantDeleteRelatedModel extends RuntimeException
{}
