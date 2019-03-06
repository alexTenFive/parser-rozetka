<?php
namespace App\Helpers;

class StringHelper
{
    public static function enRussian($string): ?string
    {
        return trim(mb_convert_encoding($string, 'ISO-8859-1', 'utf8'));
    }
}