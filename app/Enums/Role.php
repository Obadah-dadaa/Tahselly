<?php

namespace App\Enums;

enum Role: string
{
    case Developer = 'developer';
    case Platform = 'platform';
    case Owner = 'owner';
    case Manager = 'manager';

    public function label(): string
    {
        return match ($this) {
            self::Developer => __('المبرمج'),
            self::Platform => __('مالك المنصة'),
            self::Owner => __('المالك'),
            self::Manager => __('المدير'),
        };
    }

    public function homeRoute(): string
    {
        return match ($this) {
            self::Developer, self::Platform => 'developer.dashboard',
            self::Owner => 'owner.dashboard',
            self::Manager => 'dashboard',
        };
    }
}
