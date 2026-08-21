<?php

namespace SmartDato\BrtDocumentoDiRitiro\Support;

class Assets
{
    public static function font(string $key): string
    {
        return config("brt-documento-di-ritiro.fonts.{$key}")
            ?? self::basePath()."/fonts/{$key}.ttf";
    }

    public static function image(string $key): string
    {
        return config("brt-documento-di-ritiro.images.{$key}")
            ?? self::basePath()."/images/{$key}.png";
    }

    private static function basePath(): string
    {
        return dirname(__DIR__, 2).'/resources/assets';
    }
}
