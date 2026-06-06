<?php

namespace ACore\Utils;

class AcoreCharColors {

    /**
     * WotLK class colors.
     * light = adjusted for visibility on white backgrounds (Rogue/Priest darkened)
     * dark  = original WoW colors, bright enough for dark backgrounds
     */
    const CLASS_NAMES = [
        1  => 'Warrior',
        2  => 'Paladin',
        3  => 'Hunter',
        4  => 'Rogue',
        5  => 'Priest',
        6  => 'Death Knight',
        7  => 'Shaman',
        8  => 'Mage',
        9  => 'Warlock',
        11 => 'Druid',
    ];

    const CLASS_COLORS = [
        1  => ['light' => '#C69B6D', 'dark' => '#C69B6D'], // Warrior
        2  => ['light' => '#F48CBA', 'dark' => '#F48CBA'], // Paladin
        3  => ['light' => '#AAD372', 'dark' => '#AAD372'], // Hunter
        4  => ['light' => '#C8A800', 'dark' => '#FFF468'], // Rogue (yellow → darkened for light bg)
        5  => ['light' => '#909090', 'dark' => '#E0E0E0'], // Priest (white → grey for light bg)
        6  => ['light' => '#C41E3A', 'dark' => '#FF3355'], // Death Knight
        7  => ['light' => '#0070DD', 'dark' => '#3399FF'], // Shaman
        8  => ['light' => '#3FC7EB', 'dark' => '#3FC7EB'], // Mage
        9  => ['light' => '#8788EE', 'dark' => '#9A9BFF'], // Warlock
        11 => ['light' => '#FF7C0A', 'dark' => '#FF7C0A'], // Druid
    ];

    const FALLBACK_LIGHT = '#646970';
    const FALLBACK_DARK  = '#8b949e';

    public static function getLight(int $classId): string {
        return self::CLASS_COLORS[$classId]['light'] ?? self::FALLBACK_LIGHT;
    }

    public static function getDark(int $classId): string {
        return self::CLASS_COLORS[$classId]['dark'] ?? self::FALLBACK_DARK;
    }

    /**
     * Returns the inline style string to set CSS variables on a char row.
     */
    public static function rowStyle(int $classId): string {
        $light = self::getLight($classId);
        $dark  = self::getDark($classId);
        return "--cls-light:{$light};--cls-dark:{$dark};";
    }
}
