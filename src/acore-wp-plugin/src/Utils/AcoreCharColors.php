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

    const RACE_NAMES = [
        1  => 'Human',
        2  => 'Orc',
        3  => 'Dwarf',
        4  => 'Night Elf',
        5  => 'Undead',
        6  => 'Tauren',
        7  => 'Gnome',
        8  => 'Troll',
        10 => 'Blood Elf',
        11 => 'Draenei',
    ];

    /** Maps race ID → faction. */
    const RACE_FACTION = [
        1  => 'alliance', // Human
        2  => 'horde',    // Orc
        3  => 'alliance', // Dwarf
        4  => 'alliance', // Night Elf
        5  => 'horde',    // Undead
        6  => 'horde',    // Tauren
        7  => 'alliance', // Gnome
        8  => 'horde',    // Troll
        10 => 'horde',    // Blood Elf
        11 => 'alliance', // Draenei
    ];

    const FACTION_COLORS = [
        'alliance' => '#3FACF4',
        'horde'    => '#FF653D',
        'neutral'  => '#8b949e',
    ];

    public static function getLight(int $classId): string {
        return self::CLASS_COLORS[$classId]['light'] ?? self::FALLBACK_LIGHT;
    }

    public static function getDark(int $classId): string {
        return self::CLASS_COLORS[$classId]['dark'] ?? self::FALLBACK_DARK;
    }

    public static function getRaceName(int $raceId): string {
        return self::RACE_NAMES[$raceId] ?? 'Unknown';
    }

    public static function getClassName(int $classId): string {
        return self::CLASS_NAMES[$classId] ?? 'Unknown';
    }

    public static function factionColor(int $raceId): string {
        $faction = self::RACE_FACTION[$raceId] ?? 'neutral';
        return self::FACTION_COLORS[$faction];
    }

    /**
     * Returns the inline style string to set CSS variables on a char row.
     * Includes class color (light+dark) and faction border color.
     */
    public static function rowStyle(int $classId, int $raceId = 0): string {
        $light   = self::getLight($classId);
        $dark    = self::getDark($classId);
        $faction = self::factionColor($raceId);
        return "--cls-light:{$light};--cls-dark:{$dark};--faction-color:{$faction};";
    }

    /**
     * Returns the expansion slug for a given level.
     * Used to color level badges: Vanilla (1-60), TBC (61-70), Wrath (71-80).
     */
    public static function expansionSlug(int $level): string {
        if ($level <= 60) return 'vanilla';
        if ($level <= 70) return 'tbc';
        return 'wrath';
    }

    /**
     * Returns a human-readable expansion bracket label for use as a tooltip.
     */
    public static function expansionLabel(int $level): string {
        if ($level <= 60) return 'Vanilla Bracket (1–60)';
        if ($level <= 70) return 'The Burning Crusade Bracket (61–70)';
        return 'Wrath of the Lich King Bracket (71–80)';
    }
}
