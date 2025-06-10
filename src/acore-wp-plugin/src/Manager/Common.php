<?php

namespace ACore\Manager;

class Common
{

    const EXPANSION_WOTLK = 2;
    const EXPANSION_TBC = 1;
    const EXPANSION_CLASSIC = 0;

    const EXPANSIONS = [
        "EXPANSION_WOTLK" => self::EXPANSION_WOTLK,
        "EXPANSION_TBC" => self::EXPANSION_TBC,
        "EXPANSION_CLASSIC" => self::EXPANSION_CLASSIC,
    ];


    const ACCOUNT_LVL_PLAYER = 0;
    const ACCOUNT_LVL_MODERATOR = 1;
    const ACCOUNT_LVL_GAME_MASTER = 2;
    const ACCOUNT_LVL_ADMIN = 3;
    const ACCOUNT_LVL_CONSOLE = 4;

    const ACCOUNT_LEVELS = [
        "ACCOUNT_LVL_PLAYER" => self::ACCOUNT_LVL_PLAYER,
        "ACCOUNT_LVL_MODERATOR" => self::ACCOUNT_LVL_MODERATOR,
        "ACCOUNT_LVL_GAME_MASTER" => self::ACCOUNT_LVL_GAME_MASTER,
        "ACCOUNT_LVL_ADMIN" => self::ACCOUNT_LVL_ADMIN,
        "ACCOUNT_LVL_CONSOLE" => self::ACCOUNT_LVL_CONSOLE,
    ];

    /**
     * Static-only class.
     */
    private function __construct() {}
}
