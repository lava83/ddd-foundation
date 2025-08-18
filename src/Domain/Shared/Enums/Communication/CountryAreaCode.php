<?php

declare(strict_types=1);

namespace Lava83\DddFoundation\Domain\Shared\Enums\Communication;

use InvalidArgumentException;

enum CountryAreaCode: string
{
    case US = '+1';
    case DE = '+49';
    case FR = '+33';
    case IT = '+39';
    case ES = '+34';
    case UK = '+44';
    case AU = '+61';
    case PL = '+48';
    case BE = '+32';
    case NL = '+31';
    case CH = '+41';
    case LU = '+352';
    case AT = '+43';
    case DK = '+45';
    case NO = '+47';
    case FI = '+358';
    case SE = '+46';
    case LI = '+423';
    case PT = '+351';

    public static function fromName(string $name): self
    {
        return match ($name) {
            'US' => self::US,
            'DE' => self::DE,
            'FR' => self::FR,
            'IT' => self::IT,
            'ES' => self::ES,
            'UK' => self::UK,
            'CA' => self::CA,
            'AU' => self::AU,
            'PL' => self::PL,
            'BE' => self::BE,
            'NL' => self::NL,
            'CH' => self::CH,
            'LU' => self::LU,
            'AT' => self::AT,
            'DK' => self::DK,
            'NO' => self::NO,
            'FI' => self::FI,
            'SE' => self::SE,
            'LI' => self::LI,
            'PT' => self::PT,
            default => throw new InvalidArgumentException("Invalid country area code: {$name}"),
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $code) => $code->value, self::cases());
    }
}
