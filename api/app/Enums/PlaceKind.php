<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Kind of a place on the map; the site picks the marker icon and colour by it. */
enum PlaceKind: string implements HasLabel
{
    case Lake = 'lake';
    case Pass = 'pass';
    case Peak = 'peak';
    case Canyon = 'canyon';
    case YurtCamp = 'yurt_camp';
    case HotSpring = 'hot_spring';
    case Waterfall = 'waterfall';
    case Historical = 'historical';
    case Town = 'town';
    case Park = 'park';
    case Viewpoint = 'viewpoint';

    public function label(): string
    {
        return match ($this) {
            self::Lake => 'Lake',
            self::Pass => 'Mountain pass',
            self::Peak => 'Peak & base camp',
            self::Canyon => 'Canyon & gorge',
            self::YurtCamp => 'Yurt camp',
            self::HotSpring => 'Hot springs',
            self::Waterfall => 'Waterfall',
            self::Historical => 'Historical site',
            self::Town => 'Town & village',
            self::Park => 'National park',
            self::Viewpoint => 'Viewpoint',
        };
    }

    /** Russian label for the admin panel. */
    public function getLabel(): string
    {
        return match ($this) {
            self::Lake => 'Озеро',
            self::Pass => 'Перевал',
            self::Peak => 'Пик / базовый лагерь',
            self::Canyon => 'Каньон / ущелье',
            self::YurtCamp => 'Юрточный лагерь',
            self::HotSpring => 'Горячие источники',
            self::Waterfall => 'Водопад',
            self::Historical => 'Исторический памятник',
            self::Town => 'Город / село',
            self::Park => 'Нацпарк',
            self::Viewpoint => 'Смотровая точка',
        };
    }
}
