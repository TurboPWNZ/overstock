<?php
namespace Slando\core\i18n;

class Translation
{
    public static function text($text, $placeholders = [])
    {
        foreach ($placeholders as $placeholder => $value) {
            $text = str_replace($placeholder, $value, $text);
        }

        return $text;
    }
}