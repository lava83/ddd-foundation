<?php

declare(strict_types=1);

namespace Lava83\DddFoundation\Domain\ValueObjects\Date;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Exception;
use JsonSerializable;
use Lava83\DddFoundation\Domain\Exceptions\ValidationException;

final class DateRange implements JsonSerializable
{
    private CarbonImmutable $startDate;

    private CarbonImmutable $endDate;

    public function __construct(CarbonInterface $startDate, CarbonInterface $endDate)
    {
        $this->startDate = CarbonImmutable::instance($startDate)->startOfDay();
        $this->endDate = CarbonImmutable::instance($endDate)->endOfDay();

        $this->validate();
    }

    public static function fromString(string $startDate, string $endDate): self
    {
        try {
            return new self(
                CarbonImmutable::parse($startDate),
                CarbonImmutable::parse($endDate)
            );
        } catch (Exception $e) {
            throw new ValidationException('Invalid date format provided');
        }
    }

    /**
     * @param  array<string, string>  $dateRange
     */
    public static function fromArray(array $dateRange): self
    {
        if (! isset($dateRange['start_date']) || ! isset($dateRange['end_date'])) {
            throw new ValidationException('Date range must contain start_date and end_date');
        }

        return self::fromString($dateRange['start_date'], $dateRange['end_date']);
    }

    public static function singleDay(CarbonInterface $date): self
    {
        return new self($date, $date);
    }

    public static function currentWeek(): self
    {
        $now = CarbonImmutable::now();

        return new self(
            $now->startOfWeek(Carbon::MONDAY),
            $now->endOfWeek(Carbon::SUNDAY)
        );
    }

    public static function currentMonth(): self
    {
        $now = CarbonImmutable::now();

        return new self(
            $now->startOfMonth(),
            $now->endOfMonth()
        );
    }

    public static function currentYear(): self
    {
        $now = CarbonImmutable::now();

        return new self(
            $now->startOfYear(),
            $now->endOfYear()
        );
    }

    public static function lastNDays(int $days): self
    {
        $now = CarbonImmutable::now();

        return new self(
            $now->subDays($days - 1),
            $now
        );
    }

    public function startDate(): CarbonImmutable
    {
        return $this->startDate;
    }

    public function endDate(): CarbonImmutable
    {
        return $this->endDate;
    }

    public function durationInDays(): float
    {
        return floor($this->startDate->diffInDays($this->endDate) + 1);
    }

    public function durationInWeeks(): int
    {
        return (int) ceil($this->durationInDays() / 7);
    }

    public function durationInMonths(): float
    {
        return floor($this->startDate->diffInMonths($this->endDate));
    }

    public function businessDays(): int
    {
        $businessDays = 0;
        $current = $this->startDate;

        while ($current->lte($this->endDate)) {
            if ($current->isWeekday()) {
                $businessDays++;
            }
            $current = $current->addDay();
        }

        return $businessDays;
    }

    public function contains(CarbonInterface $date): bool
    {
        $checkDate = CarbonImmutable::instance($date);

        return $checkDate->between($this->startDate, $this->endDate);
    }

    public function overlaps(DateRange $other): bool
    {
        return $this->startDate->lte($other->endDate) && $this->endDate->gte($other->startDate);
    }

    public function isWithin(DateRange $other): bool
    {
        return $other->startDate->lte($this->startDate) && $other->endDate->gte($this->endDate);
    }

    public function touches(DateRange $other): bool
    {
        return $this->endDate->addDay()->startOfDay()->eq($other->startDate->startOfDay()) ||
               $this->startDate->startOfDay()->eq($other->endDate->addDay()->startOfDay());
    }

    public function merge(DateRange $other): DateRange
    {
        if (! $this->overlaps($other) && ! $this->touches($other)) {
            throw new ValidationException('Cannot merge non-overlapping and non-touching date ranges');
        }

        return new self(
            $this->startDate->min($other->startDate),
            $this->endDate->max($other->endDate)
        );
    }

    public function intersect(DateRange $other): ?DateRange
    {
        if (! $this->overlaps($other)) {
            return null;
        }

        return new self(
            $this->startDate->max($other->startDate),
            $this->endDate->min($other->endDate)
        );
    }

    /**
     * @return array<int, DateRange>
     */
    public function split(CarbonInterface $splitDate): array
    {
        $split = CarbonImmutable::instance($splitDate);

        if (! $this->contains($split)) {
            throw new ValidationException('Split date must be within the date range');
        }

        if ($split->eq($this->startDate) || $split->eq($this->endDate)) {
            return [$this];
        }

        return [
            new self($this->startDate, $split->subDay()),
            new self($split, $this->endDate),
        ];
    }

    public function extend(int $daysBefore = 0, int $daysAfter = 0): DateRange
    {
        return new self(
            $this->startDate->subDays($daysBefore),
            $this->endDate->addDays($daysAfter)
        );
    }

    public function isCurrentWeek(): bool
    {
        return $this->equals(self::currentWeek());
    }

    public function isCurrentMonth(): bool
    {
        return $this->equals(self::currentMonth());
    }

    public function isCurrentYear(): bool
    {
        return $this->equals(self::currentYear());
    }

    public function equals(DateRange $other): bool
    {
        return $this->startDate->eq($other->startDate) && $this->endDate->eq($other->endDate);
    }

    /**
     * Convert to array representation
     * Useful for serialization or API responses
     *
     * @return array<string, string|float|int>
     */
    public function toArray(): array
    {
        return [
            'start_date' => $this->startDate->toDateString(),
            'end_date' => $this->endDate->toDateString(),
            'duration_days' => $this->durationInDays(),
            'business_days' => $this->businessDays(),
        ];
    }

    /**
     * @return array<string, string|float|int>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __toString(): string
    {
        return sprintf(
            '%s to %s (%d days)',
            $this->startDate->toDateString(),
            $this->endDate->toDateString(),
            $this->durationInDays()
        );
    }

    public function format(string $format = 'Y-m-d'): string
    {
        return sprintf(
            '%s - %s',
            $this->startDate->format($format),
            $this->endDate->format($format)
        );
    }

    /**
     * @return array<string>
     */
    public function allDates(): array
    {
        $dates = [];
        $current = $this->startDate;

        while ($current->lte($this->endDate)) {
            $dates[] = $current->toDateString();
            $current = $current->addDay();
        }

        return $dates;
    }

    /**
     * @return array<string>
     */
    public function businessDates(): array
    {
        $dates = [];
        $current = $this->startDate;

        while ($current->lte($this->endDate)) {
            if ($current->isWeekday()) {
                $dates[] = $current->toDateString();
            }
            $current = $current->addDay();
        }

        return $dates;
    }

    public function spansMultipleMonths(): bool
    {
        return $this->startDate->isSameMonth($this->endDate) === false;
    }

    public function spansMultipleYears(): bool
    {
        return $this->startDate->isSameYear($this->endDate) === false;
    }

    private function validate(): void
    {
        if ($this->startDate->isAfter($this->endDate)) {
            throw new ValidationException('Start date cannot be after end date');
        }

        if ($this->startDate->diffInYears($this->endDate) > 10) {
            throw new ValidationException('Date range cannot exceed 10 years');
        }
    }
}
