<?php

namespace SmartDato\BrtDocumentoDiRitiro\Support;

class Numbers
{
    public static function format(float|int|string|null $value, int $decimalPlaces = 2): string
    {
        $value = sprintf('%.'.$decimalPlaces.'f', $value);

        return str_replace('.', ',', $value);
    }

    public static function filled(float|int|string|null $value, int $integerPlaces = 1, int $decimalPlaces = 2): string
    {
        $value = sprintf('%0'.($integerPlaces + 1 + $decimalPlaces).'.'.$decimalPlaces.'f', $value);

        return str_replace('.', ',', $value);
    }
}
