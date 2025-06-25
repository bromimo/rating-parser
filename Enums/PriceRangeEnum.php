<?php

namespace Enums;

enum PriceRangeEnum: string
{
    case Economy = 'Р';
    case Moderately = 'РР';
    case Expensive = 'РРР';
    case VeryExpensive = 'РРРР';

    public function label(): string
    {
        return match ($this) {
            self::Economy => 'до 3 млн руб.',
            self::Moderately => 'от 3 до 10 млн руб.',
            self::Expensive => 'от 10 до 30 млн руб.',
            self::VeryExpensive => 'от 30 млн руб.',
        };
    }
}
