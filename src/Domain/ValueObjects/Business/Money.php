<?php

declare(strict_types=1);

namespace Lava83\DddFoundation\Domain\ValueObjects\Business;

use InvalidArgumentException;
use JsonSerializable;

class Money implements JsonSerializable
{
    private float $amount;

    private string $currency;

    public function __construct(float $amount, string $currency)
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Amount cannot be negative');
        }

        if (! in_array($currency, ['USD', 'EUR', 'GBP'])) {
            throw new InvalidArgumentException('Unsupported currency');
        }

        $this->amount = $amount;
        $this->currency = $currency;
    }

    public static function euros(int $amount): self
    {
        return new self($amount / 100, 'EUR');
    }

    public static function dollars(int $amount): self
    {
        return new self($amount / 100, 'USD');
    }

    public static function pounds(int $amount): self
    {
        return new self($amount / 100, 'GBP');
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function currencySymbol(): string
    {
        return match ($this->currency) {
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            default => throw new InvalidArgumentException('Unsupported currency'),
        };
    }

    /**
     * @return array<string, float|string>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return array<string, float|string>
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'symbol' => $this->currencySymbol(),
        ];
    }

    public function __toString(): string
    {
        return sprintf('%d %s', $this->amount, $this->currencySymbol());
    }

    public function isEqual(Money $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }
}
