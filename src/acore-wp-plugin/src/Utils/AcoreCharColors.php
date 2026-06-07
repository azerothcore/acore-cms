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

    public static function getClassName(int $classId): string {
        return self::CLASS_NAMES[$classId] ?? 'Unknown';
    }

    public static function getRaceName(int $raceId): string {
        return self::RACE_NAMES[$raceId] ?? 'Unknown';
    }

    public static function getFaction(int $raceId): string {
        return self::RACE_FACTION[$raceId] ?? 'unknown';
    }

    /**
     * Returns inline style string for a character row.
     * Uses CSS custom properties so dark-mode.css can override via color-mix.
     */
    public static function rowStyle(int $classId, int $raceId): string {
        $cls     = self::CLASS_COLORS[$classId]  ?? ['light' => self::FALLBACK_LIGHT, 'dark' => self::FALLBACK_DARK];
        $faction = self::RACE_FACTION[$raceId]   ?? 'unknown';
        $fLight  = $faction === 'alliance' ? '#3FACF4' : '#FF653D';
        $fDark   = $faction === 'alliance' ? '#3FACF4' : '#FF653D';
        return sprintf(
            '--cls-light:%s; --cls-dark:%s; --faction-color:%s; border-top:2px solid %s; border-right:2px solid %s; border-bottom:2px solid %s; border-left:4px solid %s;',
            $cls['light'], $cls['dark'], $fLight,
            $fLight, $fLight, $fLight, $cls['light']
        );
    }

    /** Returns the expansion slug for a level, used as data-exp attribute. */
    public static function expansionSlug(int $level): string {
        if ($level <= 60) return 'vanilla';
        if ($level <= 70) return 'tbc';
        return 'wrath';
    }

    /** Returns the expansion label for a level, used as title attribute. */
    public static function expansionLabel(int $level): string {
        if ($level <= 60) return 'Classic';
        if ($level <= 70) return 'The Burning Crusade';
        return 'Wrath of the Lich King';
    }
}
