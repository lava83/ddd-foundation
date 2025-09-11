<?php

declare(strict_types=1);

namespace Lava83\DddFoundation\Domain\ValueObjects\Data;

use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;
use JsonSerializable;
use Lava83\DddFoundation\Domain\Exceptions\ValidationException;

class Json implements JsonSerializable
{
    private readonly Fluent $data;

    private function __construct(
        private readonly string $value,
    ) {
        $this->validate($value);
        $this->data = fluent(json_decode($value, true));
    }

    public static function fromString(string $json): self
    {
        $trimmed = trim($json);

        return new self($trimmed);
    }

    public static function fromArray(array $data): self
    {
        $jsonString = json_encode($data, JSON_THROW_ON_ERROR);

        return new self($jsonString);
    }

    public static function empty(): self
    {
        return new self('{}');
    }

    public function value(): string
    {
        return $this->value;
    }

    public function data(): Fluent
    {
        return $this->data;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function toArray(): array
    {
        return $this->data->toArray();
    }

    public function toCollection(): Collection
    {
        return $this->data->collect();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data->get($key, $default);
    }

    public function has(string $key): bool
    {
        return $this->data->has($key);
    }

    public function set(string $key, mixed $value): self
    {
        $newData = fluent($this->data->toArray());
        $newData->set($key, $value);

        return self::fromArray($newData->toArray());
    }

    public function merge(self $other): self
    {
        $mergedData = $this->toCollection()->merge($other->toCollection());

        return self::fromArray($mergedData->toArray());
    }

    public function remove(string $key): self
    {
        $newData = $this->data->toArray();

        $this->removeNestedValue($newData, $key);

        return self::fromArray($newData);
    }

    public function isEmpty(): bool
    {
        return $this->data->isEmpty();
    }

    public function isNotEmpty(): bool
    {
        return $this->data->isNotEmpty();
    }

    public function keys(): Collection
    {
        return $this->data->keys();
    }

    public function values(): Collection
    {
        return $this->data->values();
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private function removeNestedValue(array &$array, string $key): void
    {
        $keys = str($key)->explode('.');
        $current = &$array;

        foreach ($keys->take(-1) as $nestedKey) {
            if (! isset($current[$nestedKey]) || ! is_array($current[$nestedKey])) {
                return;
            }
            $current = &$current[$nestedKey];
        }

        unset($current[$keys->last()]);
    }

    private function validate(string $value): void
    {
        if (trim($value) === '') {
            throw new ValidationException('JSON string cannot be empty');
        }

        json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ValidationException(
                'Invalid JSON: '.json_last_error_msg()
            );
        }
    }
}
