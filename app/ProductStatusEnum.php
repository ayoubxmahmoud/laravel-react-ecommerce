<?php

namespace App;

enum ProductStatusEnum: string
{
    case Draft = 'draft';
    case Published = 'published';

    public static function labels()
    {
        return [
            self::Draft->value => __('Draft'), // Localized label for Draft status
            self::Published->value => __('Published'),
        ];
    }

    public static function colors()
    {
        return [
            'gray' => self::Draft->value, // Draft status represented by gray color
            'success' => self::Published->value
        ];
    }
}
